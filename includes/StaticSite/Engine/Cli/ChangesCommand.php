<?php

namespace FlyWP\StaticSite\Engine\Cli;

use FlyWP\StaticSite\Engine\Incremental\ChangeStore;
use FlyWP\StaticSite\Engine\Incremental\WpChangeStore;

/**
 * WP-CLI command `wp flywp static changes clear` (contract N2). The FlyWP deploy script runs it after
 * a "success" or "partial" build, full or incremental.
 *
 * The change file of the engine: `{"max_change_id": 42, "retry_urls": ["https://..."]}`.
 *
 * - Adds a "url" change row (reason "build_error") for each retry URL. The engine puts only the provided URLs
 *   that failed for a temporary cause (no HTTP answer, 429 or 5xx) in retry_urls.
 * - Then deletes the change rows up to max_change_id. A full build covers each change, so it also clears
 *   the "global" rows. The retry rows get IDs above max_change_id, so the clear does not remove them.
 *
 * @since 1.8.0
 */
class ChangesCommand extends JsonCommand {

    /**
     * @var ChangeStore
     */
    private $store;

    /**
     * @var string The file that `--file=-` reads.
     */
    private $stdin;

    /**
     * @param ChangeStore|null $store  Change rows. The database table when null.
     * @param callable|null    $output Line writer. STDOUT when null.
     * @param string           $stdin  The file that `--file=-` reads.
     */
    public function __construct( ChangeStore $store = null, $output = null, $stdin = 'php://stdin' ) {
        parent::__construct( $output );

        $this->store = $store ? $store : new WpChangeStore();
        $this->stdin = $stdin;
    }

    /**
     * Clear the change rows that a static build used, and add rows for the URLs to try again.
     *
     * ## OPTIONS
     *
     * [--file=<file>]
     * : The change file that the engine wrote, or "-" for STDIN. Necessary.
     *
     * ## EXAMPLES
     *
     *     wp flywp static changes clear --file=- < changes.json
     *
     * @param array $args       Positional arguments.
     * @param array $assoc_args Named arguments.
     *
     * @return void
     */
    public function __invoke( $args, $assoc_args ) {
        $this->execute( $assoc_args );
    }

    /**
     * {@inheritdoc}
     */
    public function run( array $assoc_args ) {
        $file    = isset( $assoc_args['file'] ) ? (string) $assoc_args['file'] : '';
        $changes = json_decode( $this->read( $file ), true );

        if ( ! is_array( $changes ) || ! isset( $changes['max_change_id'] ) || ! is_int( $changes['max_change_id'] ) ) {
            return $this->result( sprintf( 'Unable to read the change file "%s".', $file ) );
        }

        $errors = [];

        foreach ( isset( $changes['retry_urls'] ) ? (array) $changes['retry_urls'] : [] as $url ) {
            if ( ! $this->store->insert( 'url', null, null, (string) $url, 'build_error' ) ) {
                $errors[] = 'Some failed URLs were not added to the change log. The next incremental build does not try them again.';
                break;
            }
        }

        if ( $changes['max_change_id'] > 0 && ! $this->store->clear_up_to( $changes['max_change_id'] ) ) {
            $errors[] = sprintf( 'The change log was not cleared up to row %d. The next incremental build does these changes again.', $changes['max_change_id'] );
        }

        return $this->result( $errors ? implode( ' ', $errors ) : null );
    }

    /**
     * Read the change file. The FlyWP deploy script sends it through STDIN: the build folder is on the host,
     * and the site container has no mount of it.
     *
     * @param string $file A path, or "-" for STDIN.
     *
     * @return string The file content, or an empty string when the file cannot be read.
     */
    private function read( $file ) {
        if ( $file === '-' ) {
            return (string) file_get_contents( $this->stdin );
        }

        return $file !== '' && is_readable( $file ) ? (string) file_get_contents( $file ) : '';
    }
}
