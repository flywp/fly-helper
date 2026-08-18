<?php

namespace FlyWP\Tests;

use FlyWP\MagicLoginToken;
use PHPUnit\Framework\TestCase;

/**
 * The token format is a contract with the FlyWP control plane, and the two implementations live
 * in different repositories — so alongside the behavioural cases this pins one fixed vector that
 * the control plane's own suite asserts byte for byte. If either side drifts on encoding, key
 * material or claim order, that test fails on both sides.
 */
class MagicLoginTokenTest extends TestCase {

    const PUBLIC_KEY = 'IMOCUs37RkBOXqbUoMzASCZgByLMIJOeUsw6V5K7uRA=';

    const PRIVATE_KEY = 'rOHcyX5bufXJWAgQ0V30TMLPKpzHMKxmQgWb8yA3jIUgw4JSzftGQE5eptSgzMBIJmAHIswgk55SzDpXkru5EA==';

    const NOW = 1770000000;

    const JTI = '0123456789abcdef0123456789abcdef';

    /**
     * The shared vector. Regenerating this to make the test pass means changing the format,
     * which means the control plane has to change with it.
     */
    public function test_it_matches_the_control_plane_vector() {
        $token = 'flywp-ed25519.eyJzdWIiOiJhZG1pbiIsInNpZCI6NDIsImlhdCI6MTc3MDAwMDAwMCwiZXhwIjoxNzcwMDAwMDkwLCJqdGkiOiIwMTIzNDU2Nzg5YWJjZGVmMDEyMzQ1Njc4OWFiY2RlZiIsImZseXdwX3VzZXJfaWQiOjd9.pRON6XIm0djixhkDMlsuTaIiP19R1dBcIPxxRNhY6F-iM2jkDhMQukVOny6JlJ5YvVu1Y6d9DWov5oF_wEpbCg';

        $claims = MagicLoginToken::parse( $token, self::PUBLIC_KEY, self::NOW );

        $this->assertSame( $this->claims(), $claims );
    }

    public function test_it_accepts_a_well_formed_token() {
        $claims = MagicLoginToken::parse( $this->token(), self::PUBLIC_KEY, self::NOW );

        $this->assertSame( 'admin', $claims['sub'] );
        $this->assertSame( 42, $claims['sid'] );
        $this->assertSame( 7, $claims['flywp_user_id'] );
    }

    public function test_it_rejects_a_tampered_payload() {
        $parts = explode( '.', $this->token() );

        $parts[1] = $this->encode( array_merge( $this->claims(), [ 'sub' => 'someone-else' ] ) );

        $this->assertNull( MagicLoginToken::parse( implode( '.', $parts ), self::PUBLIC_KEY, self::NOW ) );
    }

    public function test_it_rejects_a_tampered_signature() {
        $parts = explode( '.', $this->token() );

        $parts[2] = strrev( $parts[2] );

        $this->assertNull( MagicLoginToken::parse( implode( '.', $parts ), self::PUBLIC_KEY, self::NOW ) );
    }

    public function test_it_rejects_a_token_signed_with_another_key() {
        $other = sodium_crypto_sign_keypair();
        $token = $this->sign( $this->claims(), sodium_crypto_sign_secretkey( $other ) );

        $this->assertNull( MagicLoginToken::parse( $token, self::PUBLIC_KEY, self::NOW ) );
    }

    public function test_it_rejects_an_expired_token() {
        $this->assertNull( MagicLoginToken::parse( $this->token(), self::PUBLIC_KEY, self::NOW + 91 + 60 ) );
    }

    public function test_it_accepts_a_token_inside_the_clock_drift_grace() {
        $this->assertNotNull( MagicLoginToken::parse( $this->token(), self::PUBLIC_KEY, self::NOW + 90 + 60 ) );
    }

    public function test_it_rejects_a_token_claiming_an_implausible_lifetime() {
        $token = $this->token( [ 'exp' => self::NOW + MagicLoginToken::MAX_LIFETIME + 1 ] );

        $this->assertNull( MagicLoginToken::parse( $token, self::PUBLIC_KEY, self::NOW ) );
    }

    public function test_it_rejects_a_token_that_expires_before_it_was_issued() {
        $token = $this->token( [ 'exp' => self::NOW - 1 ] );

        $this->assertNull( MagicLoginToken::parse( $token, self::PUBLIC_KEY, self::NOW - 60 ) );
    }

    public function test_it_rejects_another_version_prefix() {
        $parts = explode( '.', $this->token() );

        $parts[0] = 'flywp2';

        $this->assertNull( MagicLoginToken::parse( implode( '.', $parts ), self::PUBLIC_KEY, self::NOW ) );
    }

    public function test_it_rejects_a_token_that_has_not_started_yet() {
        $token = $this->token( [ 'iat' => self::NOW + 3600, 'exp' => self::NOW + 3690 ] );

        $this->assertNull( MagicLoginToken::parse( $token, self::PUBLIC_KEY, self::NOW ) );
    }

    public function test_it_accepts_a_token_from_just_inside_the_drift_grace() {
        $token = $this->token( [ 'iat' => self::NOW + 60, 'exp' => self::NOW + 150 ] );

        $this->assertNotNull( MagicLoginToken::parse( $token, self::PUBLIC_KEY, self::NOW ) );
    }

    public function test_it_rejects_an_empty_public_key() {
        $this->assertNull( MagicLoginToken::parse( $this->token(), '', self::NOW ) );
    }

    public function test_it_rejects_a_public_key_of_the_wrong_length() {
        $this->assertNull( MagicLoginToken::parse( $this->token(), base64_encode( 'not-32-bytes' ), self::NOW ) );
    }

    /**
     * libsodium throws on a signature that is not exactly SODIUM_CRYPTO_SIGN_BYTES, and this
     * endpoint is unauthenticated — so a hand-written token must come back null, not fatal.
     */
    public function test_it_rejects_a_signature_of_the_wrong_length() {
        $parts = explode( '.', $this->token() );

        $parts[2] = 'aaaa';

        $this->assertNull( MagicLoginToken::parse( implode( '.', $parts ), self::PUBLIC_KEY, self::NOW ) );
    }

    public function test_it_rejects_a_correctly_signed_token_that_is_too_long() {
        $token = $this->token( [ 'sub' => str_repeat( 'a', MagicLoginToken::MAX_LENGTH ) ] );

        $this->assertGreaterThan( MagicLoginToken::MAX_LENGTH, strlen( $token ) );
        $this->assertNull( MagicLoginToken::parse( $token, self::PUBLIC_KEY, self::NOW ) );
    }

    public function test_it_rejects_a_signature_segment_that_is_not_base64url() {
        $token = $this->token( [ 'jti' => '00000000000000000000000000000001' ] );
        $parts = explode( '.', $token );

        $this->assertNotNull( MagicLoginToken::parse( $token, self::PUBLIC_KEY, self::NOW ) );

        $parts[2] = strtr( $parts[2], '-_', '+/' );

        $this->assertNull( MagicLoginToken::parse( implode( '.', $parts ), self::PUBLIC_KEY, self::NOW ) );
    }

    /**
     * @dataProvider malformed_tokens
     *
     * @param mixed $token Token that must be refused.
     */
    public function test_it_rejects_malformed_input( $token ) {
        $this->assertNull( MagicLoginToken::parse( $token, self::PUBLIC_KEY, self::NOW ) );
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function malformed_tokens() {
        return [
            'empty'           => [ '' ],
            'not a string'    => [ null ],
            'no segments'     => [ 'flywp-ed25519' ],
            'two segments'    => [ 'flywp-ed25519.abc' ],
            'four segments'   => [ 'flywp-ed25519.abc.def.ghi' ],
            'not base64url'   => [ 'flywp-ed25519.not base64!.also+not/base64url' ],
            'over max length' => [ 'flywp-ed25519.' . str_repeat( 'a', MagicLoginToken::MAX_LENGTH ) . '.aaa' ],
            'stub segments'   => [ 'flywp-ed25519.abc.aaa' ],
        ];
    }

    /**
     * @dataProvider unusable_claims
     *
     * @param array $claims Claims that must be refused.
     */
    public function test_it_rejects_incomplete_or_mistyped_claims( array $claims ) {
        $this->assertNull( MagicLoginToken::parse( $this->sign( $claims ), self::PUBLIC_KEY, self::NOW ) );
    }

    /**
     * @return array<string, array<int, array>>
     */
    public function unusable_claims() {
        $valid = $this->claims();

        return [
            'no sub'               => [ array_diff_key( $valid, [ 'sub' => '' ] ) ],
            'no exp'               => [ array_diff_key( $valid, [ 'exp' => '' ] ) ],
            'no jti'               => [ array_diff_key( $valid, [ 'jti' => '' ] ) ],
            'no flywp_user_id'     => [ array_diff_key( $valid, [ 'flywp_user_id' => '' ] ) ],
            'empty sub'            => [ array_merge( $valid, [ 'sub' => '' ] ) ],
            'sub not string'       => [ array_merge( $valid, [ 'sub' => 1 ] ) ],
            'exp not int'          => [ array_merge( $valid, [ 'exp' => '1770000090' ] ) ],
            'flywp_user_id string' => [ array_merge( $valid, [ 'flywp_user_id' => '7' ] ) ],
            'jti not hex'          => [ array_merge( $valid, [ 'jti' => 'not-hexadecimal-at-all-no-really' ] ) ],
            'jti wrong width'      => [ array_merge( $valid, [ 'jti' => '0123456789abcdef' ] ) ],
        ];
    }

    /**
     * @param array $overrides Claims to override.
     *
     * @return string
     */
    private function token( array $overrides = [] ) {
        return $this->sign( array_merge( $this->claims(), $overrides ) );
    }

    /**
     * @param array       $claims     Claims to sign.
     * @param string|null $secret_key Raw 64-byte secret key.
     *
     * @return string
     */
    private function sign( array $claims, $secret_key = null ) {
        $signing_input = MagicLoginToken::VERSION . '.' . $this->encode( $claims );
        $key           = $secret_key === null ? base64_decode( self::PRIVATE_KEY, true ) : $secret_key;
        $signature     = sodium_crypto_sign_detached( $signing_input, $key );

        return $signing_input . '.' . $this->base64url( $signature );
    }

    /**
     * @return array
     */
    private function claims() {
        return [
            'sub'           => 'admin',
            'sid'           => 42,
            'iat'           => self::NOW,
            'exp'           => self::NOW + 90,
            'jti'           => self::JTI,
            'flywp_user_id' => 7,
        ];
    }

    /**
     * @param array $claims Claims to encode.
     *
     * @return string
     */
    private function encode( array $claims ) {
        return $this->base64url( json_encode( $claims ) );
    }

    /**
     * @param string $value Raw bytes.
     *
     * @return string
     */
    private function base64url( $value ) {
        return rtrim( strtr( base64_encode( $value ), '+/', '-_' ), '=' );
    }
}
