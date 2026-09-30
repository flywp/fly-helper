<?php

/*
 * The suite runs without WordPress. PHP looks for an unqualified function in the current
 * namespace first, so these replace the WordPress functions for the classes under test.
 * Keep every such stub in this file: a second declaration is a fatal error.
 */

namespace FlyWP {

    function flywp() {
        return \FlyWP\Tests\ApiTest::$plugin;
    }

    function add_action( $hook, $callback ) {
        \FlyWP\Tests\ApiTest::$actions[ $hook ] = $callback;
    }

    function sanitize_text_field( $value ) {
        return $value;
    }

    function wp_unslash( $value ) {
        return $value;
    }
}

namespace FlyWP\Api {

    function flywp() {
        return \FlyWP\flywp();
    }

    function add_action( $hook, $callback ) {
        \FlyWP\add_action( $hook, $callback );
    }

    function wp_next_scheduled( $hook ) {
        return false;
    }

    function wp_schedule_event( $timestamp, $recurrence, $hook ) {
        \FlyWP\Tests\ApiTest::$scheduled[ $hook ] = $recurrence;
    }
}

namespace FlyWP\Tests {

    use FlyWP\Api;
    use FlyWP\Api\UpdatesData;
    use FlyWP\Router;
    use PHPUnit\Framework\TestCase;

    class ApiTest extends TestCase {

        const KEY = 'site-key';

        /** @var object */
        public static $plugin;

        /** @var array */
        public static $actions = [];

        /** @var array */
        public static $scheduled = [];

        protected function setUp(): void {
            self::$plugin = new class( self::KEY ) {
                public $router;

                private $key;

                public function __construct( $key ) {
                    $this->router = new Router();
                    $this->key    = $key;
                }

                public function has_key() {
                    return true;
                }

                public function get_key() {
                    return $this->key;
                }
            };

            self::$actions   = [];
            self::$scheduled = [];
        }

        protected function tearDown(): void {
            unset( $_SERVER['HTTP_AUTHORIZATION'] );
        }

        public function test_routes_without_a_token() {
            new Api();

            $this->assertSame( [ 'ping' ], $this->routes() );
        }

        public function test_routes_with_a_wrong_token() {
            $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer wrong-key';

            new Api();

            $this->assertSame( [ 'ping' ], $this->routes() );
        }

        public function test_routes_with_a_valid_token() {
            $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . self::KEY;

            new Api();

            $this->assertContains( 'updates-data', $this->routes() );
        }

        public function test_register_cron_registers_only_the_cron() {
            $updates_data = new UpdatesData();

            $updates_data->register_cron();

            $this->assertSame( [], $this->routes() );
            $this->assertSame( [ $updates_data, 'send_updates_data_to_api' ], self::$actions[ UpdatesData::CRON_HOOK ] );
            $this->assertSame( UpdatesData::CRON_INTERVAL, self::$scheduled[ UpdatesData::CRON_HOOK ] );
        }

        private function routes() {
            return array_keys( self::$plugin->router->get_routes() );
        }
    }
}
