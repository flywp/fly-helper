<?php

namespace FlyWP\Tests\StaticSite\Engine\Cli;

use FlyWP\StaticSite\Engine\Cli\ChangesCommand;
use FlyWP\Tests\StaticSite\Engine\Incremental\FakeChangeStore;
use PHPUnit\Framework\TestCase;

class ChangesCommandTest extends TestCase {

    /**
     * @var FakeChangeStore
     */
    private $store;

    /**
     * @var string[]
     */
    private $lines = [];

    /**
     * @var string
     */
    private $file;

    protected function setUp(): void {
        $this->store = new FakeChangeStore();
        $this->file  = (string) tempnam( sys_get_temp_dir(), 'flywp-changes-' );
    }

    protected function tearDown(): void {
        if ( is_file( $this->file ) ) {
            unlink( $this->file );
        }
    }

    public function test_clears_the_used_rows_and_keeps_newer_rows() {
        $this->store->add( 'global', null, null, null, 'switch_theme' );
        $this->store->add( 'url', 'post', 1, 'https://wp.test/a/' );
        $this->store->add( 'url', 'post', 2, 'https://wp.test/b/' );

        $this->assertSame( 0, $this->run_with( [ 'max_change_id' => 2, 'retry_urls' => [] ] ) );
        $this->assertSame( [ [ 'url', 'post', 2, 'https://wp.test/b/' ] ], $this->store->summary() );
        $this->assertSame( [ '{"event":"result","status":"success","message":null}' ], $this->lines );
    }

    public function test_retry_rows_get_ids_above_the_cleared_rows() {
        $this->store->add( 'url', 'post', 1, 'https://wp.test/a/' );
        $this->store->add( 'url', 'post', 2, 'https://wp.test/b/' );

        $this->assertSame( 0, $this->run_with( [ 'max_change_id' => 2, 'retry_urls' => [ 'https://wp.test/a/', 'https://wp.test/feed/' ] ] ) );
        $this->assertSame(
            [
                [ 'url', null, null, 'https://wp.test/a/' ],
                [ 'url', null, null, 'https://wp.test/feed/' ],
            ],
            $this->store->summary()
        );
        $this->assertSame( [ 3, 4 ], array_keys( $this->store->rows ) );
        $this->assertSame( 'build_error', $this->store->rows[3]['reason'] );
    }

    public function test_zero_max_change_id_clears_nothing() {
        $this->store->add( 'url', 'post', 1, 'https://wp.test/a/' );

        $this->assertSame( 0, $this->run_with( [ 'max_change_id' => 0 ] ) );
        $this->assertCount( 1, $this->store->rows );
    }

    public function test_an_insert_error_fails_but_the_rows_are_still_cleared() {
        $this->store->add( 'url', 'post', 1, 'https://wp.test/a/' );
        $this->store->failing_inserts = 1;

        $this->assertSame( 1, $this->run_with( [ 'max_change_id' => 1, 'retry_urls' => [ 'https://wp.test/x/', 'https://wp.test/y/' ] ] ) );
        $this->assertSame( [], $this->store->summary() );
        $this->assertStringContainsString( '"status":"failed"', $this->lines[0] );
        $this->assertStringContainsString( 'Some failed URLs were not added to the change log.', $this->lines[0] );
    }

    public function test_a_clear_error_fails() {
        $this->store = new class() extends FakeChangeStore {
            public function clear_up_to( $change_id ) {
                return false;
            }
        };

        $this->assertSame( 1, $this->run_with( [ 'max_change_id' => 3 ] ) );
        $this->assertStringContainsString( 'The change log was not cleared up to row 3.', $this->lines[0] );
    }

    public function test_an_unreadable_file_fails() {
        $this->store->add( 'url', 'post', 1, 'https://wp.test/a/' );

        file_put_contents( $this->file, 'not json' );

        $this->assertSame( 1, $this->command()->run( [ 'file' => $this->file ] ) );
        $this->assertSame( 1, $this->command()->run( [ 'file' => $this->file . '-missing' ] ) );
        $this->assertSame( 1, $this->command()->run( [] ) );
        $this->assertCount( 1, $this->store->rows );
        $this->assertStringContainsString( 'Unable to read the change file', $this->lines[0] );
    }

    public function test_reads_the_change_file_from_stdin() {
        $this->store->add( 'url', 'post', 1, 'https://wp.test/a/' );

        file_put_contents( $this->file, (string) json_encode( [ 'max_change_id' => 1, 'retry_urls' => [ 'https://wp.test/x/' ] ] ) );

        $this->assertSame( 0, $this->command( $this->file )->run( [ 'file' => '-' ] ) );
        $this->assertSame( [ [ 'url', null, null, 'https://wp.test/x/' ] ], $this->store->summary() );
    }

    public function test_empty_stdin_fails() {
        $this->assertSame( 1, $this->command( $this->file )->run( [ 'file' => '-' ] ) );
        $this->assertSame( [ '{"event":"result","status":"failed","message":"Unable to read the change file \\"-\\"."}' ], $this->lines );
    }

    /**
     * @param array $changes Change file data.
     *
     * @return int Exit code.
     */
    private function run_with( array $changes ) {
        file_put_contents( $this->file, (string) json_encode( $changes ) );

        return $this->command()->run( [ 'file' => $this->file ] );
    }

    /**
     * @param string $stdin The file that `--file=-` reads.
     *
     * @return ChangesCommand
     */
    private function command( $stdin = 'php://memory' ) {
        return new ChangesCommand(
            $this->store,
            function ( $line ) {
                $this->lines[] = $line;
            },
            $stdin
        );
    }
}
