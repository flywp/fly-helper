<?php

namespace FlyWP\Tests;

use FlyWP\MagicLoginToken;
use PHPUnit\Framework\TestCase;

/**
 * The token format is a contract with the FlyWP control plane, and the two implementations live
 * in different repositories — so alongside the behavioural cases this pins one fixed vector that
 * the control plane's own suite asserts byte for byte. If either side drifts on encoding, key
 * derivation or claim order, that test fails on both sides.
 */
class MagicLoginTokenTest extends TestCase {

    const API_KEY = 'site_key_0123456789abcdefghijklmnopqrstuvwxyzABCD';

    const NOW = 1770000000;

    const JTI = '0123456789abcdef0123456789abcdef';

    /**
     * The shared vector. Regenerating this to make the test pass means changing the format,
     * which means the control plane has to change with it.
     */
    public function test_it_matches_the_control_plane_vector() {
        $token = 'flywp1.eyJzdWIiOiJhZG1pbiIsInNpZCI6NDIsImlhdCI6MTc3MDAwMDAwMCwiZXhwIjoxNzcwMDAwMTIwLCJqdGkiOiIwMTIzNDU2Nzg5YWJjZGVmMDEyMzQ1Njc4OWFiY2RlZiJ9.DlFguVn96Ajiw3cI2n7aFDpwwuSVMKtVuPZnrN1PKA0';

        $claims = MagicLoginToken::parse( $token, self::API_KEY, self::NOW );

        $this->assertSame( $this->claims(), $claims );
    }

    public function test_it_accepts_a_well_formed_token() {
        $claims = MagicLoginToken::parse( $this->token(), self::API_KEY, self::NOW );

        $this->assertSame( 'admin', $claims['sub'] );
        $this->assertSame( 42, $claims['sid'] );
    }

    public function test_it_rejects_a_tampered_payload() {
        $parts = explode( '.', $this->token() );

        $parts[1] = $this->encode( array_merge( $this->claims(), [ 'sub' => 'someone-else' ] ) );

        $this->assertNull( MagicLoginToken::parse( implode( '.', $parts ), self::API_KEY, self::NOW ) );
    }

    public function test_it_rejects_a_tampered_signature() {
        $parts = explode( '.', $this->token() );

        $parts[2] = strrev( $parts[2] );

        $this->assertNull( MagicLoginToken::parse( implode( '.', $parts ), self::API_KEY, self::NOW ) );
    }

    public function test_it_rejects_a_token_signed_with_another_key() {
        $token = $this->token( [], 'site_key_a-different-site-entirely-000000000000' );

        $this->assertNull( MagicLoginToken::parse( $token, self::API_KEY, self::NOW ) );
    }

    /**
     * Tokens are signed with a key derived from the API key, so a signature made with the raw
     * key — what a naive implementation on either side would produce — must not verify.
     */
    public function test_it_rejects_a_signature_made_with_the_underived_key() {
        $signing_input = MagicLoginToken::VERSION . '.' . $this->encode( $this->claims() );
        $signature     = hash_hmac( 'sha256', $signing_input, self::API_KEY, true );

        $token = $signing_input . '.' . $this->base64url( $signature );

        $this->assertNull( MagicLoginToken::parse( $token, self::API_KEY, self::NOW ) );
    }

    public function test_it_rejects_an_expired_token() {
        $this->assertNull( MagicLoginToken::parse( $this->token(), self::API_KEY, self::NOW + 121 + 60 ) );
    }

    public function test_it_accepts_a_token_inside_the_clock_drift_grace() {
        $this->assertNotNull( MagicLoginToken::parse( $this->token(), self::API_KEY, self::NOW + 120 + 60 ) );
    }

    public function test_it_rejects_a_token_claiming_an_implausible_lifetime() {
        $token = $this->token( [ 'exp' => self::NOW + MagicLoginToken::MAX_LIFETIME + 1 ] );

        $this->assertNull( MagicLoginToken::parse( $token, self::API_KEY, self::NOW ) );
    }

    public function test_it_rejects_a_token_that_expires_before_it_was_issued() {
        $token = $this->token( [ 'exp' => self::NOW - 1 ] );

        $this->assertNull( MagicLoginToken::parse( $token, self::API_KEY, self::NOW - 60 ) );
    }

    public function test_it_rejects_another_version_prefix() {
        $parts = explode( '.', $this->token() );

        $parts[0] = 'flywp2';

        $this->assertNull( MagicLoginToken::parse( implode( '.', $parts ), self::API_KEY, self::NOW ) );
    }

    public function test_it_rejects_a_token_that_has_not_started_yet() {
        // A site whose clock runs behind sees every token as expiring comfortably in the future,
        // so without a lower bound an expired token stays valid for as long as the drift.
        $token = $this->token( [ 'iat' => self::NOW + 3600, 'exp' => self::NOW + 3720 ] );

        $this->assertNull( MagicLoginToken::parse( $token, self::API_KEY, self::NOW ) );
    }

    public function test_it_accepts_a_token_from_just_inside_the_drift_grace() {
        $token = $this->token( [ 'iat' => self::NOW + 60, 'exp' => self::NOW + 180 ] );

        $this->assertNotNull( MagicLoginToken::parse( $token, self::API_KEY, self::NOW ) );
    }

    /**
     * Signed with the key it is handed, so only the empty-key guard itself can refuse it.
     */
    public function test_it_rejects_an_empty_api_key() {
        $this->assertNull( MagicLoginToken::parse( $this->token( [], '' ), '', self::NOW ) );
    }

    /**
     * Correctly signed and over the limit, so only the length guard can refuse it. Without that
     * guard this parses fine.
     */
    public function test_it_rejects_a_correctly_signed_token_that_is_too_long() {
        $token = $this->token( [ 'sub' => str_repeat( 'a', MagicLoginToken::MAX_LENGTH ) ] );

        $this->assertGreaterThan( MagicLoginToken::MAX_LENGTH, strlen( $token ) );
        $this->assertNull( MagicLoginToken::parse( $token, self::API_KEY, self::NOW ) );
    }

    /**
     * The signature segment rewritten from base64url into standard base64. It decodes to exactly
     * the same 32 bytes, so the signature still matches and only the alphabet guard can refuse it
     * — remove that guard and this token verifies. The jti is chosen so the signature actually
     * contains characters the two alphabets disagree on; payload segments cannot, since base64 of
     * ASCII JSON never produces `+` or `/`.
     */
    public function test_it_rejects_a_signature_segment_that_is_not_base64url() {
        $token = $this->token( [ 'jti' => '00000000000000000000000000000001' ] );
        $parts = explode( '.', $token );

        $this->assertNotNull( MagicLoginToken::parse( $token, self::API_KEY, self::NOW ) );
        $this->assertMatchesRegularExpression( '/[-_]/', $parts[2] );

        $parts[2] = strtr( $parts[2], '-_', '+/' );

        $this->assertNull( MagicLoginToken::parse( implode( '.', $parts ), self::API_KEY, self::NOW ) );
    }

    /**
     * @dataProvider malformed_tokens
     *
     * @param mixed $token Token that must be refused.
     */
    public function test_it_rejects_malformed_input( $token ) {
        $this->assertNull( MagicLoginToken::parse( $token, self::API_KEY, self::NOW ) );
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function malformed_tokens() {
        return [
            'empty'           => [ '' ],
            'not a string'    => [ null ],
            'no segments'     => [ 'flywp1' ],
            'two segments'    => [ 'flywp1.abc' ],
            'four segments'   => [ 'flywp1.abc.def.ghi' ],
            'not base64url'   => [ 'flywp1.not base64!.also+not/base64url' ],
            'over max length' => [ 'flywp1.' . str_repeat( 'a', MagicLoginToken::MAX_LENGTH ) . '.aaa' ],
        ];
    }

    /**
     * Correctly signed, so these exercise the claim checks rather than the signature check.
     *
     * @dataProvider unusable_claims
     *
     * @param array $claims Claims that must be refused.
     */
    public function test_it_rejects_incomplete_or_mistyped_claims( array $claims ) {
        $this->assertNull( MagicLoginToken::parse( $this->sign( $claims ), self::API_KEY, self::NOW ) );
    }

    /**
     * @return array<string, array<int, array>>
     */
    public function unusable_claims() {
        $valid = [
            'sub' => 'admin',
            'sid' => 42,
            'iat' => self::NOW,
            'exp' => self::NOW + 120,
            'jti' => self::JTI,
        ];

        return [
            'no sub'          => [ array_diff_key( $valid, [ 'sub' => '' ] ) ],
            'no exp'          => [ array_diff_key( $valid, [ 'exp' => '' ] ) ],
            'no jti'          => [ array_diff_key( $valid, [ 'jti' => '' ] ) ],
            'empty sub'       => [ array_merge( $valid, [ 'sub' => '' ] ) ],
            'sub not string'  => [ array_merge( $valid, [ 'sub' => 1 ] ) ],
            'exp not int'     => [ array_merge( $valid, [ 'exp' => '1770000120' ] ) ],
            'jti not hex'     => [ array_merge( $valid, [ 'jti' => 'not-hexadecimal-at-all-no-really' ] ) ],
            'jti wrong width' => [ array_merge( $valid, [ 'jti' => '0123456789abcdef' ] ) ],
        ];
    }

    /**
     * @param array  $overrides Claims to override.
     * @param string $key       API key to derive the signing key from.
     *
     * @return string
     */
    private function token( array $overrides = [], $key = self::API_KEY ) {
        return $this->sign( array_merge( $this->claims(), $overrides ), $key );
    }

    /**
     * @param array  $claims Claims to sign.
     * @param string $key    API key to derive the signing key from.
     *
     * @return string
     */
    private function sign( array $claims, $key = self::API_KEY ) {
        $signing_input = MagicLoginToken::VERSION . '.' . $this->encode( $claims );
        $signature     = hash_hmac( 'sha256', $signing_input, MagicLoginToken::signing_key( $key ), true );

        return $signing_input . '.' . $this->base64url( $signature );
    }

    /**
     * @return array
     */
    private function claims() {
        return [
            'sub' => 'admin',
            'sid' => 42,
            'iat' => self::NOW,
            'exp' => self::NOW + 120,
            'jti' => self::JTI,
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
