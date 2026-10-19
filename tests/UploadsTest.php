<?php

namespace FlyWP\Tests;

use FlyWP\Brand;
use FlyWP\Uploads;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/stubs/wordpress.php';

/**
 * Uploads refused while a White Label customer's disk total is full (FLYWP_UPLOADS_BLOCKED).
 * The suite runs with no constant, so each test sets the environment instead.
 */
class UploadsTest extends TestCase {

    protected function setUp(): void {
        $GLOBALS['flywp_test_filters'] = [];
        $GLOBALS['flywp_test_plugin']  = $this->plugin( Brand::decode( bin2hex( json_encode( [ 'name' => 'Acme Cloud', 'url' => 'https://acme.flywp.xyz' ] ) ) ) );
    }

    protected function tearDown(): void {
        putenv( 'FLYWP_UPLOADS_BLOCKED' );
        unset( $_ENV['FLYWP_UPLOADS_BLOCKED'] );
    }

    public function test_it_adds_no_hook_while_uploads_are_allowed() {
        putenv( 'FLYWP_UPLOADS_BLOCKED=0' );

        new Uploads();

        $this->assertSame( [], $GLOBALS['flywp_test_filters'] );
    }

    public function test_it_refuses_uploads_and_sideloads_with_the_brands_name() {
        putenv( 'FLYWP_UPLOADS_BLOCKED=1' );

        $uploads = new Uploads();
        $file    = $uploads->refuse( [ 'name' => 'photo.jpg', 'error' => 0 ] );

        $this->assertSame( [ 'wp_handle_upload_prefilter' => 1, 'wp_handle_sideload_prefilter' => 1 ], $GLOBALS['flywp_test_filters'] );
        $this->assertSame( 'Your storage is full. Remove the files you do not need, or ask Acme Cloud for more space.', $file['error'] );
        $this->assertSame( 'photo.jpg', $file['name'] );
    }

    public function test_it_reads_a_bedrock_env_that_has_no_putenv() {
        $_ENV['FLYWP_UPLOADS_BLOCKED'] = 'true';

        $this->assertTrue( Uploads::blocked() );
    }

    private function plugin( Brand $brand ) {
        return new class( $brand ) {
            private $brand;

            public function __construct( $brand ) {
                $this->brand = $brand;
            }

            public function brand() {
                return $this->brand;
            }
        };
    }
}
