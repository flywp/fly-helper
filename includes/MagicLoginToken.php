<?php

namespace FlyWP;

/**
 * Verifier for the signed, single-use tokens the FlyWP control plane mints for magic login.
 *
 * The format is a contract shared with the control plane, so it lives in one place with no
 * WordPress dependency and can be tested on its own.
 *
 *     flywp2.<b64url(payload_json)>.<b64url(ed25519_signature)>
 *
 * Two rules when changing this: the prefix is part of the signed material, and the signature is
 * verified against the payload bytes as received, never against a re-encoding of the claims.
 *
 * @since 1.7.0
 */
class MagicLoginToken {

    /**
     * Token version. Part of the signed material.
     */
    const VERSION = 'flywp2';

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
     * @param string $token      Raw token as it arrived in the request.
     * @param string $public_key This site's FLYWP_LOGIN_PUBLIC_KEY (base64).
     * @param int    $now        Current unix timestamp.
     * @param int    $skew       Clock drift to tolerate, in seconds.
     *
     * @return array|null The claims, or null when the token is not acceptable for any reason.
     */
    public static function parse( $token, $public_key, $now, $skew = self::DEFAULT_SKEW ) {
        if ( ! is_string( $token ) || ! is_string( $public_key ) || $public_key === '' ) {
            return null;
        }

        if ( $token === '' || strlen( $token ) > self::MAX_LENGTH ) {
            return null;
        }

        if ( ! function_exists( 'sodium_crypto_sign_verify_detached' ) ) {
            return null;
        }

        $parts = explode( '.', $token );

        if ( count( $parts ) !== 3 || $parts[0] !== self::VERSION ) {
            return null;
        }

        $signature = self::base64url_decode( $parts[2] );
        $raw_public_key = self::raw_public_key( $public_key );

        if ( $signature === null || $raw_public_key === null ) {
            return null;
        }

        if ( ! sodium_crypto_sign_verify_detached( $signature, $parts[0] . '.' . $parts[1], $raw_public_key ) ) {
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
     * Whether every claim is present and of the right type.
     *
     * @param array $claims Decoded claims.
     *
     * @return bool
     */
    private static function has_valid_claims( array $claims ) {
        foreach ( [ 'sub', 'sid', 'iat', 'exp', 'jti', 'flywp_user_id' ] as $claim ) {
            if ( ! isset( $claims[ $claim ] ) ) {
                return false;
            }
        }

        if ( ! is_string( $claims['sub'] ) || $claims['sub'] === '' ) {
            return false;
        }

        if ( ! is_int( $claims['sid'] ) || ! is_int( $claims['iat'] ) || ! is_int( $claims['exp'] ) || ! is_int( $claims['flywp_user_id'] ) ) {
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

        if ( $now + $skew < $claims['iat'] ) {
            return false;
        }

        return $now <= ( $claims['exp'] + $skew );
    }

    /**
     * @param string $encoded Base64 public key.
     *
     * @return string|null 32 raw bytes, or null.
     */
    private static function raw_public_key( $encoded ) {
        $raw = base64_decode( $encoded, true );

        if ( $raw === false || strlen( $raw ) !== SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES ) {
            return null;
        }

        return $raw;
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

        $padded = strtr( $value, '-_', '+/' );
        $remainder = strlen( $padded ) % 4;

        if ( $remainder !== 0 ) {
            $padded .= str_repeat( '=', 4 - $remainder );
        }

        // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
        $decoded = base64_decode( $padded, true );

        return $decoded === false ? null : $decoded;
    }
}
