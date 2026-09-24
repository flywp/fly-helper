<?php

namespace FlyWP\StaticSite\Engine\Cli;

/**
 * A WP-CLI command that prints one JSON result line to stdout:
 * `{"event":"result","status":"success|failed","message":null}`.
 *
 * - PHP notices and other output go to STDERR, so the result line is the only stdout line.
 * - Exit code 0 on success, 1 on failure.
 *
 * @since 1.8.0
 */
abstract class JsonCommand {

    /**
     * @var callable function( string $line ). Writes one stdout line.
     */
    private $output;

    /**
     * @param callable|null $output Line writer. STDOUT when null.
     */
    public function __construct( $output = null ) {
        $this->output = $output ? $output : function ( $line ) {
            fwrite( STDOUT, $line . "\n" );
        };
    }

    /**
     * Run the command.
     *
     * @param array $assoc_args Named arguments.
     *
     * @return int Exit code.
     */
    abstract public function run( array $assoc_args );

    /**
     * Run the command from WP-CLI and exit with its code. Each command calls it from __invoke(),
     * because WP-CLI reads the options from the __invoke() docblock.
     *
     * @param array $assoc_args Named arguments.
     *
     * @return void
     */
    protected function execute( array $assoc_args ) {
        ini_set( 'display_errors', 'stderr' ); // phpcs:ignore WordPress.PHP.IniSet -- stdout keeps only the JSON result line.

        \WP_CLI::halt( $this->run( $assoc_args ) );
    }

    /**
     * Print the result line.
     *
     * @param string|null $error Error message. Null on success.
     *
     * @return int Exit code.
     */
    protected function result( $error = null ) {
        $line = [
            'event'   => 'result',
            'status'  => $error === null ? 'success' : 'failed',
            'message' => $error,
        ];

        call_user_func( $this->output, (string) json_encode( $line, JSON_UNESCAPED_SLASHES ) );

        return $error === null ? 0 : 1;
    }
}
