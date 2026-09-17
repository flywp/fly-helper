<?php

namespace FlyWP\Tests;

use FlyWP\KeyResolver;
use PHPUnit\Framework\TestCase;

/**
 * The resolver is what keeps the plugin alive on a Bedrock site whose repository never
 * declared `Config::define( 'FLYWP_API_KEY', ... )`. Before it existed, `has_key()` read the
 * empty constant, `init_plugin()` returned early, `fly-api` was never registered as a query
 * variable, and `/fly-api/*` answered with the theme front page and a 200.
 */
class KeyResolverTest extends TestCase {

    const NAME = 'FLYWP_TEST_KEY';

    const VALUE = 'site_key_42_abcdefghijklmnopqrstuvwxyz0123456789';

    protected function setUp(): void {
        parent::setUp();

        $this->clearEnvironment();
    }

    protected function tearDown(): void {
        $this->clearEnvironment();

        parent::tearDown();
    }

    public function test_it_prefers_the_constant_when_one_is_set() {
        putenv( self::NAME . '=from-getenv' );
        $_ENV[ self::NAME ]    = 'from-env';
        $_SERVER[ self::NAME ] = 'from-server';

        $this->assertSame( self::VALUE, KeyResolver::resolve( self::VALUE, self::NAME ) );
    }

    public function test_it_falls_back_to_getenv_when_the_constant_is_empty() {
        putenv( self::NAME . '=' . self::VALUE );

        $this->assertSame( self::VALUE, KeyResolver::resolve( '', self::NAME ) );
    }

    /**
     * A repository loading its `.env` with `createImmutable` never calls `putenv()`.
     */
    public function test_it_falls_back_to_the_env_superglobal() {
        $_ENV[ self::NAME ] = self::VALUE;

        $this->assertSame( self::VALUE, KeyResolver::resolve( '', self::NAME ) );
    }

    public function test_it_falls_back_to_the_server_superglobal() {
        $_SERVER[ self::NAME ] = self::VALUE;

        $this->assertSame( self::VALUE, KeyResolver::resolve( '', self::NAME ) );
    }

    public function test_it_returns_an_empty_string_when_no_source_has_the_key() {
        $this->assertSame( '', KeyResolver::resolve( '', self::NAME ) );
    }

    /**
     * `getenv()` returns false for an unset name, and an unset `.env` line can leave an empty
     * string behind. Neither is a key, and neither may win over a later source that has one.
     */
    public function test_it_skips_empty_sources_and_keeps_looking() {
        putenv( self::NAME . '=' );
        $_ENV[ self::NAME ]    = '';
        $_SERVER[ self::NAME ] = self::VALUE;

        $this->assertSame( self::VALUE, KeyResolver::resolve( '', self::NAME ) );
    }

    /**
     * The login public key is base64 and carries `+`, `/` and `=`. It is returned verbatim:
     * a resolver that trimmed or escaped it would break signature verification rather than
     * fail loudly.
     */
    public function test_it_returns_a_base64_value_unaltered() {
        $public_key = 'IMOCUs37RkBOXqbUoMzASCZgByLMIJOeUsw6V5K7uRA=';

        putenv( self::NAME . '=' . $public_key );

        $this->assertSame( $public_key, KeyResolver::resolve( '', self::NAME ) );
    }

    /**
     * `wp_magic_quotes()` runs `add_magic_quotes()` over `$_SERVER` before any plugin loads.
     * A key read from there arrives escaped, while the bearer token it is compared against is
     * unslashed by `Api::get_bearer_token()`. Without the same treatment here, `hash_equals()`
     * could never match for a key holding a quote or a backslash.
     */
    public function test_it_unslashes_a_key_read_from_the_server_superglobal() {
        $_SERVER[ self::NAME ] = "a\\'quoted\\\\key";

        $this->assertSame( "a'quoted\\key", KeyResolver::resolve( '', self::NAME ) );
    }

    /**
     * `$_ENV` is the one superglobal `wp_magic_quotes()` leaves alone, so it is taken verbatim.
     */
    public function test_it_does_not_unslash_the_env_superglobal() {
        $_ENV[ self::NAME ] = "a\\'value";

        $this->assertSame( "a\\'value", KeyResolver::resolve( '', self::NAME ) );
    }

    /**
     * A constant that is not a string cannot be a key, and must not stop the fallback.
     */
    public function test_a_non_string_constant_falls_through_to_the_environment() {
        putenv( self::NAME . '=' . self::VALUE );

        $this->assertSame( self::VALUE, KeyResolver::resolve( false, self::NAME ) );
    }

    /**
     * An array in `$_SERVER` is not a key. It must be stepped over rather than reaching
     * `stripslashes()`, which raises a TypeError on anything but a string.
     */
    public function test_it_steps_over_a_non_string_in_the_server_superglobal() {
        $_SERVER[ self::NAME ] = [ 'not', 'a', 'key' ];

        $this->assertSame( '', KeyResolver::resolve( '', self::NAME ) );
    }

    private function clearEnvironment() {
        putenv( self::NAME );
        unset( $_ENV[ self::NAME ], $_SERVER[ self::NAME ] );
    }
}
