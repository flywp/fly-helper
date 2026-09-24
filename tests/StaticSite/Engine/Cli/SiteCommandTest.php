<?php

namespace FlyWP\Tests\StaticSite\Engine\Cli;

use FlyWP\StaticSite\Engine\Cli\SiteCommand;
use FlyWP\StaticSite\Engine\Discovery\DiscoveredUrl;
use FlyWP\Tests\StaticSite\Engine\Discovery\FakeWordPressGateway;
use FlyWP\Tests\StaticSite\Engine\Incremental\FakeChangeStore;
use PHPUnit\Framework\TestCase;

class SiteCommandTest extends TestCase {

    /**
     * @var FakeWordPressGateway
     */
    private $wp;

    /**
     * @var FakeChangeStore
     */
    private $store;

    /**
     * @var string[]
     */
    private $lines = [];

    /**
     * @var string[]
     */
    private $warnings = [];

    /**
     * @var string
     */
    private $file;

    protected function setUp(): void {
        $this->wp    = new FakeWordPressGateway();
        $this->store = new FakeChangeStore();
        $this->file  = sys_get_temp_dir() . '/flywp-site-' . uniqid() . '.json';

        $this->wp->add_post( 1, [ 'slug' => 'hello', 'modified_gmt' => '2026-09-01 10:00:00' ] );
        $this->wp->add_post( 2, [ 'slug' => 'secret', 'password' => 'x' ] );
    }

    protected function tearDown(): void {
        if ( is_file( $this->file ) ) {
            unlink( $this->file );
        }
    }

    public function test_full_snapshot_has_the_discovery_and_the_highest_change_row() {
        $this->store->add( 'global', null, null, null, 'switch_theme' );
        $this->store->add( 'url', 'post', 1, 'https://wp.test/hello/' );

        $site = $this->snapshot( 'full' );

        $this->assertSame( 1, $site['version'] );
        $this->assertSame( '1.8.0', $site['plugin_version'] );
        $this->assertSame( 'https://wp.test', $site['home_url'] );
        $this->assertTrue( $site['respect_noindex'] );
        $this->assertSame( 'page', $site['pagination_base'] );
        $this->assertSame( [ 'https://wp.test/old/' ], $site['redirect_sources'] );
        $this->assertSame( 2, $site['max_change_id'] );
        $this->assertNull( $site['change_set'] );
        $this->assertSame( [ 'https://wp.test/secret/' ], $site['excluded_urls'] );

        $hello = $this->find_url( $site, 'https://wp.test/hello/' );

        $this->assertSame(
            [
                'url'          => 'https://wp.test/hello/',
                'kind'         => 'post',
                'object_type'  => 'post',
                'object_id'    => 1,
                'modified_gmt' => '2026-09-01 10:00:00',
            ],
            $hello
        );
        $this->assertNull( $this->find_url( $site, 'https://wp.test/secret/' ) );
    }

    public function test_incremental_snapshot_has_the_change_set_and_the_full_discovery() {
        $this->store->add( 'url', 'post', 1, 'https://wp.test/hello/' );
        $this->store->add( 'delete', 'post', 3, 'https://wp.test/gone/' );

        $site = $this->snapshot( 'incremental' );

        $this->assertSame( 2, $site['max_change_id'] );
        $this->assertSame( [ 'full_reason', 'urls', 'deleted_urls', 'lists' ], array_keys( $site['change_set'] ) );
        $this->assertNull( $site['change_set']['full_reason'] );
        $this->assertContains( 'https://wp.test/hello/', $site['change_set']['urls'] );
        $this->assertSame( [ 'https://wp.test/gone/' ], $site['change_set']['deleted_urls'] );
        $this->assertContains( [ 'url' => 'https://wp.test/', 'pages' => 1 ], $site['change_set']['lists'] );
        $this->assertNotNull( $this->find_url( $site, 'https://wp.test/hello/' ) );
    }

    public function test_incremental_snapshot_with_a_global_row_asks_for_a_full_build() {
        $this->store->add( 'url', 'post', 1, 'https://wp.test/hello/' );
        $this->store->add( 'global', null, null, null, 'switch_theme' );

        $site = $this->snapshot( 'incremental' );

        $this->assertSame( 'global_change', $site['change_set']['full_reason'] );
        $this->assertSame( 2, $site['max_change_id'] );
    }

    public function test_incremental_snapshot_without_rows() {
        $site = $this->snapshot( 'incremental' );

        $this->assertSame( 0, $site['max_change_id'] );
        $this->assertNull( $site['change_set']['full_reason'] );
        $this->assertSame( [], $site['change_set']['urls'] );
    }

    public function test_snapshot_puts_the_urls_on_the_origin_and_keeps_retry_rows_on_the_origin_host() {
        // The home is https://wp.test. The engine fetches https://www.wp.test and writes retry URLs on it.
        $this->store->add( 'url', null, null, 'https://www.wp.test/retry/', 'build_error' );
        $this->store->add( 'url', 'post', 1, 'https://wp.test/hello/' );

        $site = $this->snapshot( 'incremental', 'https://www.wp.test' );

        $this->assertContains( 'https://www.wp.test/retry/', $site['change_set']['urls'] );
        $this->assertContains( 'https://www.wp.test/hello/', $site['change_set']['urls'] );
        $this->assertContains( [ 'url' => 'https://www.wp.test/', 'pages' => 1 ], $site['change_set']['lists'] );
        $this->assertNotNull( $this->find_url( $site, 'https://www.wp.test/hello/' ) );
        $this->assertNull( $this->find_url( $site, 'https://wp.test/hello/' ) );
        $this->assertSame( [ 'https://www.wp.test/secret/' ], $site['excluded_urls'] );
    }

    public function test_run_gives_the_origin_url_to_the_snapshot() {
        $origins = [];
        $command = $this->command(
            function ( $mode, $origin_url ) use ( &$origins ) {
                $origins[] = $origin_url;

                return $this->snapshot( $mode, $origin_url );
            }
        );

        $this->assertSame( 0, $command->run( [ 'output' => $this->file ] ) );
        $this->assertSame( 0, $command->run( [ 'output' => $this->file, 'origin-url' => 'https://www.wp.test/' ] ) );
        $this->assertSame( 1, $command->run( [ 'output' => $this->file, 'origin-url' => 'ftp://wp.test' ] ) );
        $this->assertSame( [ null, 'https://www.wp.test' ], $origins );
        $this->assertSame( '{"event":"result","status":"failed","message":"The --origin-url value must be an absolute http or https URL."}', $this->lines[2] );
    }

    public function test_run_drops_urls_that_are_not_valid_utf8_and_succeeds() {
        $bad     = "https://wp.test/caf\xE9/";
        $command = $this->command(
            function ( $mode ) use ( $bad ) {
                $site                          = $this->snapshot( $mode );
                $site['urls'][]                = new DiscoveredUrl( $bad, 'file' );
                $site['excluded_urls'][]       = $bad;
                $site['redirect_sources'][]    = $bad;
                $site['redirect_wildcards'][]  = [ 'from' => 'https://wp.test/a/*', 'to' => $bad, 'status' => 301 ];
                $site['change_set']['urls'][]  = $bad;
                $site['change_set']['lists'][] = [ 'url' => $bad, 'pages' => 1 ];

                return $site;
            }
        );

        $this->assertSame( 0, $command->run( [ 'output' => $this->file, 'mode' => 'incremental' ] ) );
        $this->assertSame( [ '{"event":"result","status":"success","message":null}' ], $this->lines );
        $this->assertSame( array_fill( 0, 6, 'Skipped a URL that is not valid UTF-8: "https://wp.test/caf\ufffd/"' ), $this->warnings );

        $site = json_decode( (string) file_get_contents( $this->file ), true );

        $this->assertNotNull( $this->find_url( $site, 'https://wp.test/hello/' ) );
        $this->assertSame( [ 'https://wp.test/secret/' ], $site['excluded_urls'] );
        $this->assertSame( [ 'https://wp.test/old/' ], $site['redirect_sources'] );
        $this->assertCount( 1, $site['redirect_wildcards'] );
        $this->assertNotContains( [ 'url' => $bad, 'pages' => 1 ], $site['change_set']['lists'] );
    }

    public function test_run_writes_the_file_and_prints_one_result_line() {
        $command = $this->command(
            function ( $mode ) {
                return $this->snapshot( $mode );
            }
        );

        $this->assertSame( 0, $command->run( [ 'output' => $this->file, 'mode' => 'incremental' ] ) );
        $this->assertSame( [ '{"event":"result","status":"success","message":null}' ], $this->lines );

        $site = json_decode( (string) file_get_contents( $this->file ), true );

        $this->assertSame( 'https://wp.test', $site['home_url'] );
        $this->assertSame( [ [ 'from' => 'https://wp.test/old/*', 'to' => 'https://wp.test/new/*', 'status' => 301 ] ], $site['redirect_wildcards'] );
        $this->assertStringContainsString( '"url":"https://wp.test/hello/"', (string) file_get_contents( $this->file ) );
    }

    public function test_run_refuses_a_missing_output_and_an_unknown_mode() {
        $command = $this->command(
            function () {
                $this->fail( 'The snapshot must not run.' );
            }
        );

        $this->assertSame( 1, $command->run( [] ) );
        $this->assertSame( 1, $command->run( [ 'output' => $this->file, 'mode' => 'fast' ] ) );
        $this->assertSame(
            [
                '{"event":"result","status":"failed","message":"Set --output."}',
                '{"event":"result","status":"failed","message":"Unknown mode \"fast\". Use full or incremental."}',
            ],
            $this->lines
        );
    }

    public function test_run_fails_when_the_snapshot_throws() {
        $command = $this->command(
            function () {
                throw new \RuntimeException( 'Database error.' );
            }
        );

        $this->assertSame( 1, $command->run( [ 'output' => $this->file ] ) );
        $this->assertSame( [ '{"event":"result","status":"failed","message":"Database error."}' ], $this->lines );
        $this->assertFileDoesNotExist( $this->file );
    }

    /**
     * @param callable $snapshot Snapshot factory.
     *
     * @return SiteCommand
     */
    private function command( $snapshot ) {
        return new SiteCommand(
            $snapshot,
            function ( $line ) {
                $this->lines[] = $line;
            },
            function ( $message ) {
                $this->warnings[] = $message;
            }
        );
    }

    /**
     * @param string      $mode       full or incremental.
     * @param string|null $origin_url Origin URL. Null for the home URL.
     *
     * @return array
     */
    private function snapshot( $mode, $origin_url = null ) {
        return SiteCommand::snapshot(
            $mode,
            $origin_url ? $origin_url : 'https://wp.test',
            [
                'plugin_version'     => '1.8.0',
                'home_url'           => 'https://wp.test',
                'site_url'           => 'https://wp.test',
                'respect_noindex'    => true,
                'pretty_permalinks'  => true,
                'pagination_base'    => 'page',
                'redirect_sources'   => [ 'https://wp.test/old/' ],
                'redirect_wildcards' => [ [ 'from' => 'https://wp.test/old/*', 'to' => 'https://wp.test/new/*', 'status' => 301 ] ],
            ],
            $this->wp,
            $this->store
        );
    }

    /**
     * @param array  $site Snapshot.
     * @param string $url  URL.
     *
     * @return array|null The URL as JSON data.
     */
    private function find_url( array $site, $url ) {
        foreach ( json_decode( (string) json_encode( $site['urls'] ), true ) as $item ) {
            if ( $item['url'] === $url ) {
                return $item;
            }
        }

        return null;
    }
}
