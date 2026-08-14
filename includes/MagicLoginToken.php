<?php

namespace FlyWP;

/**
 * Verifier for the signed, single-use tokens the FlyWP control plane mints for magic login.
 *
 * The token format is a contract shared with the control plane, so it lives in one place with
 * no WordPress dependency at all — it can be reasoned about, and tested, on its own.
 *
 *     flywp1.<b64url(payload_json)>.<b64url(hmac_sha256)>
 *
 * Two properties of that shape are deliberate. The version prefix is part of the signed
 * material, so the scheme cannot be downgraded by rewriting it. And the signature is verified
 * against the payload bytes exactly as they arrived, never against a re-encoding of the decoded
 * claims — the control plane runs PHP 8.3 and sites run anything from 7.4 up, and JSON
 * canonicalisation is not worth betting a login on.
 *
 * @since 1.6.0
 */
class MagicLoginToken {

    /**
     * Token version. Part of the signed material.
     */
    const VERSION = 'flywp1';

    /**
     * Longest token accepted, in bytes. Bounds the work done before the signature check.
     */
    const MAX_LENGTH = 4096;

    /**
     * Longest lifetime a token may claim for itself, in seconds.
     */
    const MAX_LIFETIME = 600;

    /**
     * Clock drift tolerated between the control plane and this site, in seconds.
     */
    const DEFAULT_SKEW = 60;

    /**
     * Verify a token and return the claims it carries.
     *
     * @param string $token   Raw token as it arrived in the request.
     * @param string $api_key This site's FLYWP_API_KEY.
     * @param int    $now     Current unix timestamp.
     * @param int    $skew    Clock drift to tolerate, in seconds.
     *
     * @return array|null The claims, or null when the token is not acceptable for any reason.
     */
    public static function parse( $token, $api_key, $now, $skew = self::DEFAULT_SKEW ) {
        if ( ! is_string( $token ) || ! is_string( $api_key ) || $api_key === '' ) {
            return null;
        }

        if ( $token === '' || strlen( $token ) > self::MAX_LENGTH ) {
            return null;
        }

        $parts = explode( '.', $token );

        if ( count( $parts ) !== 3 || $parts[0] !== self::VERSION ) {
            return null;
        }

        $signature = self::base64url_decode( $parts[2] );

        if ( $signature === null ) {
            return null;
        }

        $expected = hash_hmac( 'sha256', $parts[0] . '.' . $parts[1], self::signing_key( $api_key ), true );

        if ( ! hash_equals( $expected, $signature ) ) {
            return null;
        }

        $payload = self::base64url_decode( $parts[1] );

        if ( $payload === null ) {
            return null;
        }

        $claims = json_decode( $payload, true );

        if ( ! is_array( $claims ) || ! self::has_valid_claims( $claims ) ) {
            return null;
        }

        if ( ! self::is_fresh( $claims, (int) $now, (int) $skew ) ) {
            return null;
        }

        return $claims;
    }

    /**
     * The key tokens are actually signed with, derived from the site's API key.
     *
     * The API key authenticates the REST API and the site's calls back to the control plane;
     * deriving a subkey keeps a magic-login signature from being usable as anything else.
     *
     * @param string $api_key This site's FLYWP_API_KEY.
     *
     * @return string Raw binary key.
     */
    public static function signing_key( $api_key ) {
        return hash_hmac( 'sha256', 'flywp-magic-login-v1', $api_key, true );
    }

    /**
     * Whether every claim is present and of the right type.
     *
     * @param array $claims Decoded claims.
     *
     * @return bool
     */
    private static function has_valid_claims( array $claims ) {
        foreach ( [ 'sub', 'sid', 'iat', 'exp', 'jti' ] as $claim ) {
            if ( ! isset( $claims[ $claim ] ) ) {
                return false;
            }
        }

        if ( ! is_string( $claims['sub'] ) || $claims['sub'] === '' ) {
            return false;
        }

        if ( ! is_int( $claims['sid'] ) || ! is_int( $claims['iat'] ) || ! is_int( $claims['exp'] ) ) {
            return false;
        }

        return is_string( $claims['jti'] ) && preg_match( '/^[a-f0-9]{32}$/', $claims['jti'] ) === 1;
    }

    /**
     * Whether the token is inside its stated lifetime, and that lifetime is plausible.
     *
     * @param array $claims Decoded claims.
     * @param int   $now    Current unix timestamp.
     * @param int   $skew   Clock drift to tolerate, in seconds.
     *
     * @return bool
     */
    private static function is_fresh( array $claims, $now, $skew ) {
        $lifetime = $claims['exp'] - $claims['iat'];

        if ( $lifetime <= 0 || $lifetime > self::MAX_LIFETIME ) {
            return false;
        }

        // Both ends, not just expiry. Checking only the upper bound means a site whose clock runs
        // behind accepts a token for as long as the drift lasts rather than for its lifetime: to
        // such a site every token looks like it expires comfortably in the future. A site drifted
        // further than the grace now refuses tokens outright, which is the safe direction to fail.
        if ( $now + $skew < $claims['iat'] ) {
            return false;
        }

        return $now <= ( $claims['exp'] + $skew );
    }

    /**
     * Decode base64url, rejecting anything outside the alphabet.
     *
     * @param string $value Encoded segment.
     *
     * @return string|null Raw bytes, or null when the segment is not base64url.
     */
    private static function base64url_decode( $value ) {
        if ( ! is_string( $value ) || preg_match( '/^[A-Za-z0-9\-_]+$/', $value ) !== 1 ) {
            return null;
        }

        // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
        $decoded = base64_decode( strtr( $value, '-_', '+/' ), true );

        return $decoded === false ? null : $decoded;
    }
}
