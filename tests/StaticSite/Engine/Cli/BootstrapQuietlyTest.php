<?php

namespace FlyWP\Tests\StaticSite\Engine\Cli;

use FlyWP\StaticSite\Bootstrap;
use PHPUnit\Framework\TestCase;

class BootstrapQuietlyTest extends TestCase {

    public function test_install_output_goes_to_the_stream_and_not_to_stdout() {
        $stream = fopen( 'php://memory', 'w+b' );

        ob_start();
        Bootstrap::quietly(
            function () {
                echo 'Created table wp_flywp_static_changes';
            },
            $stream
        );
        $stdout = (string) ob_get_clean();

        rewind( $stream );

        $this->assertSame( '', $stdout );
        $this->assertSame( 'Created table wp_flywp_static_changes', stream_get_contents( $stream ) );
    }

    public function test_output_goes_to_the_stream_when_the_callback_throws() {
        $stream = fopen( 'php://memory', 'w+b' );

        ob_start();

        try {
            Bootstrap::quietly(
                function () {
                    echo 'half';
                    throw new \RuntimeException( 'db down' );
                },
                $stream
            );
        } catch ( \RuntimeException $e ) {
            $this->assertSame( 'db down', $e->getMessage() );
        }

        $this->assertSame( '', (string) ob_get_clean() );

        rewind( $stream );
        $this->assertSame( 'half', stream_get_contents( $stream ) );
    }
}
