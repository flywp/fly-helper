<?php
// phpcs:disable WordPress.WP.AlternativeFunctions -- WP-CLI command: STDERR and one local output file, no WP_Filesystem.

namespace FlyWP\StaticSite\Engine\Cli;

use FlyWP\StaticSite\Bootstrap;
use FlyWP\StaticSite\Engine\Discovery\RedirectSourceUrls;
use FlyWP\StaticSite\Engine\Discovery\Url;
use FlyWP\StaticSite\Engine\Discovery\UrlDiscovery;
use FlyWP\StaticSite\Engine\Discovery\WordPressGateway;
use FlyWP\StaticSite\Engine\Discovery\WpGateway;
use FlyWP\StaticSite\Engine\Incremental\ChangeSetBuilder;
use FlyWP\StaticSite\Engine\Incremental\ChangeStore;
use FlyWP\StaticSite\Engine\Incremental\WpChangeStore;
use Throwable;

/**
 * WP-CLI command `wp flywp static site`: write the site data for the FlyWP static engine (site.json, contract N1).
 *
 * - The engine runs outside WordPress and reads this file.
 * - The URLs are on the origin URL that the engine fetches (--origin-url, default the home URL), as in the old engine.
 *   The change log keeps retry URLs in this form, so the origin must be the same for each build.
 * - A URL that is not valid UTF-8 cannot be written to JSON. The command drops it and writes a warning to STDERR.
 * - max_change_id is the highest change row that the file includes. `wp flywp static changes clear` deletes
 *   the rows up to it after the build.
 *
 * @since 1.8.0
 */
class SiteCommand extends JsonCommand {

    /**
     * Version of the site.json format.
     */
    const VERSION = 1;

    const MODES = [ 'full', 'incremental' ];

    /**
     * @var callable function( string $mode, string|null $origin_url ): array. The site.json data.
     */
    private $snapshot;

    /**
     * @var callable function( string $message ). Writes one warning.
     */
    private $warning;

    /**
     * @param callable|null $snapshot Data factory. self::wordpress_snapshot() when null.
     * @param callable|null $output   Line writer. STDOUT when null.
     * @param callable|null $warning  Warning writer. STDERR when null.
     */
    public function __construct( $snapshot = null, $output = null, $warning = null ) {
        parent::__construct( $output );

        $this->snapshot = $snapshot ? $snapshot : [ self::class, 'wordpress_snapshot' ];
        $this->warning  = $warning ? $warning : function ( $message ) {
            fwrite( STDERR, 'Warning: ' . $message . "\n" );
        };
    }

    /**
     * Write the site data for a static build.
     *
     * ## OPTIONS
     *
     * [--output=<file>]
     * : The JSON file to write. Necessary.
     *
     * [--mode=<mode>]
     * : full or incremental. An incremental file also has the change set.
     * ---
     * default: full
     * ---
     *
     * [--origin-url=<url>]
     * : The URL that the engine fetches. The URLs in the file are on this URL. Default: the home URL.
     *
     * ## EXAMPLES
     *
     *     wp flywp static site --output=/tmp/flywp-static-12.json --mode=incremental --origin-url=https://example.com
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
        $file   = isset( $assoc_args['output'] ) ? (string) $assoc_args['output'] : '';
        $mode   = isset( $assoc_args['mode'] ) ? (string) $assoc_args['mode'] : 'full';
        $origin = isset( $assoc_args['origin-url'] ) ? rtrim( (string) $assoc_args['origin-url'], '/' ) : null;
        $site   = null;

        if ( $file === '' ) {
            return $this->result( 'Set --output.' );
        }

        if ( ! in_array( $mode, self::MODES, true ) ) {
            return $this->result( sprintf( 'Unknown mode "%s". Use full or incremental.', $mode ) );
        }

        if ( $origin !== null && ! Url::is_http( $origin ) ) {
            return $this->result( 'The --origin-url value must be an absolute http or https URL.' );
        }

        try {
            // Plugin hooks and filters run during discovery. Their output must not go to stdout.
            Bootstrap::quietly(
                function () use ( $mode, $origin, &$site ) {
                    $site = call_user_func( $this->snapshot, $mode, $origin );
                }
            );
        } catch ( Throwable $e ) {
            return $this->result( $e->getMessage() );
        }

        $json = json_encode( $this->without_invalid_urls( $site ), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );

        if ( $json === false ) {
            return $this->result( sprintf( 'Unable to encode the site data: %s.', json_last_error_msg() ) );
        }

        if ( file_put_contents( $file, $json ) === false ) {
            return $this->result( sprintf( 'Unable to write %s.', $file ) );
        }

        return $this->result();
    }

    /**
     * The site data of this WordPress site.
     *
     * @param string      $mode       full or incremental.
     * @param string|null $origin_url Origin URL that the engine fetches, without trailing slash. Null for the home URL.
     *
     * @return array
     */
    public static function wordpress_snapshot( $mode, $origin_url = null ) {
        $origin_url = $origin_url ? $origin_url : rtrim( home_url(), '/' );
        $site_urls  = [ home_url(), site_url() ];

        return self::snapshot(
            $mode,
            $origin_url,
            [
                'plugin_version'     => FLYWP_VERSION,
                'home_url'           => home_url(),
                'site_url'           => site_url(),
                'respect_noindex'    => (string) get_option( 'blog_public' ) !== '0',
                'pretty_permalinks'  => (string) get_option( 'permalink_structure' ) !== '',
                'pagination_base'    => isset( $GLOBALS['wp_rewrite']->pagination_base ) ? (string) $GLOBALS['wp_rewrite']->pagination_base : 'page',
                'redirect_sources'   => RedirectSourceUrls::all( $origin_url, $site_urls ),
                'redirect_wildcards' => RedirectSourceUrls::wildcard_rules( $origin_url, $site_urls ),
            ],
            new WpGateway(),
            new WpChangeStore()
        );
    }

    /**
     * The site.json data.
     *
     * - The URLs are on the origin URL. The discovery and the change set also accept URLs on the home URL.
     * - The change rows are read before the URL discovery. A change during the discovery stays for the next build.
     * - Incremental: max_change_id is the highest row of the change set, so the clear does not delete a newer row.
     *
     * @param string           $mode       full or incremental.
     * @param string           $origin_url Origin URL that the engine fetches.
     * @param array            $site       Site values: plugin_version, home_url, site_url, respect_noindex,
     *                                     pretty_permalinks, pagination_base, redirect_sources, redirect_wildcards.
     * @param WordPressGateway $wp         Gateway.
     * @param ChangeStore      $changes    Change rows.
     *
     * @return array
     */
    public static function snapshot( $mode, $origin_url, array $site, WordPressGateway $wp, ChangeStore $changes ) {
        $discovery  = new UrlDiscovery( $wp, $origin_url, UrlDiscovery::default_sources( $wp, $origin_url ) );
        $change_set = null;

        if ( $mode === 'incremental' ) {
            $set           = ( new ChangeSetBuilder( $changes, $wp, $origin_url ) )->build( 0 );
            $max_change_id = $set->max_change_id;
            $change_set    = [
                'full_reason'  => $set->full_reason,
                'urls'         => $set->urls,
                'deleted_urls' => $set->deleted_urls,
                'lists'        => $set->lists,
            ];
        } else {
            $max_change_id = $changes->max_id();
        }

        return array_merge(
            [ 'version' => self::VERSION ],
            $site,
            [
                'urls'          => iterator_to_array( $discovery->urls(), false ),
                'excluded_urls' => iterator_to_array( $discovery->excluded_urls(), false ),
                'max_change_id' => (int) $max_change_id,
                'change_set'    => $change_set,
            ]
        );
    }

    /**
     * Remove the URLs that are not valid UTF-8, because json_encode() cannot write them.
     *
     * @param array $site The site.json data.
     *
     * @return array
     */
    private function without_invalid_urls( array $site ) {
        $site['urls']               = $this->valid_items( $site['urls'], 'url' );
        $site['excluded_urls']      = $this->valid_items( $site['excluded_urls'] );
        $site['redirect_sources']   = $this->valid_items( $site['redirect_sources'] );
        $site['redirect_wildcards'] = $this->valid_items( $site['redirect_wildcards'], 'from', 'to' );

        if ( is_array( $site['change_set'] ) ) {
            $site['change_set']['urls']         = $this->valid_items( $site['change_set']['urls'] );
            $site['change_set']['deleted_urls'] = $this->valid_items( $site['change_set']['deleted_urls'] );
            $site['change_set']['lists']        = $this->valid_items( $site['change_set']['lists'], 'url' );
        }

        return $site;
    }

    /**
     * The items whose URLs are valid UTF-8. Writes one warning for each item that it removes.
     *
     * @param array  $items   URLs, or arrays or objects with URLs.
     * @param string ...$keys The URL keys of an item. None when the item is the URL.
     *
     * @return array
     */
    private function valid_items( array $items, ...$keys ) {
        $valid = [];

        foreach ( $items as $item ) {
            $urls    = $keys ? array_intersect_key( (array) $item, array_flip( $keys ) ) : [ $item ];
            $invalid = array_filter(
                $urls,
                function ( $url ) {
                    return is_string( $url ) && preg_match( '//u', $url ) !== 1;
                }
            );

            if ( $invalid ) {
                call_user_func( $this->warning, sprintf( 'Skipped a URL that is not valid UTF-8: %s', json_encode( reset( $invalid ), JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE ) ) );
                continue;
            }

            $valid[] = $item;
        }

        return $valid;
    }
}
