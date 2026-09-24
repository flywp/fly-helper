<?php

namespace FlyWP\StaticSite;

use FlyWP\StaticSite\Engine\Cli\ChangesCommand;
use FlyWP\StaticSite\Engine\Cli\SiteCommand;
use FlyWP\StaticSite\Engine\Incremental\ChangeLog;
use WP_CLI;

/**
 * Starts the static site parts of the plugin. The FlyWP app builds the static copy. The plugin gives it the site data.
 *
 * - register_cli(): the WP-CLI commands `flywp static site` and `flywp static changes clear`. It runs also
 *   when no FLYWP_API_KEY is set.
 * - The constructor (site with an API key): records content changes when the option flywp_static_enabled is 1.
 * - The change log table: installed on plugin activation, and on a version change in wp-admin, REST requests
 *   and WP-CLI. Never on a normal front-end request.
 *
 * @since 1.8.0
 */
class Bootstrap {

    /**
     * Option that holds the installed schema version.
     */
    const SCHEMA_VERSION_OPTION = 'flywp_static_schema_version';

    /**
     * Start the change log of a site with an API key.
     */
    public function __construct() {
        ChangeLog::register_hooks();

        add_action( 'admin_init', [ self::class, 'maybe_install' ] );
        add_action( 'rest_api_init', [ self::class, 'maybe_install' ] );
    }

    /**
     * Register the WP-CLI commands. Does nothing outside WP-CLI.
     *
     * @return void
     */
    public static function register_cli() {
        if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
            return;
        }

        self::quietly( [ self::class, 'maybe_install' ] );

        WP_CLI::add_command( 'flywp static site', new SiteCommand() );
        WP_CLI::add_command( 'flywp static changes clear', new ChangesCommand() );
    }

    /**
     * Run a callback and send its output (for example dbDelta text) to STDERR, so stdout keeps only JSON lines.
     *
     * @param callable      $callback Callback.
     * @param resource|null $stream   Stream for the output. STDERR when null.
     *
     * @return void
     */
    public static function quietly( callable $callback, $stream = null ) {
        ob_start();

        try {
            call_user_func( $callback );
        } finally {
            $noise = (string) ob_get_clean();

            if ( $noise !== '' ) {
                fwrite( $stream ? $stream : STDERR, $noise ); // phpcs:ignore WordPress.WP.AlternativeFunctions -- a CLI stream, not a file.
            }
        }
    }

    /**
     * Install or update the change log table when the plugin version changes.
     *
     * @return void
     */
    public static function maybe_install() {
        if ( get_option( self::SCHEMA_VERSION_OPTION ) !== FLYWP_VERSION ) {
            self::install();
        }
    }

    /**
     * Create or update the change log table.
     *
     * @return void
     */
    public static function install() {
        ChangeLog::install();

        update_option( self::SCHEMA_VERSION_OPTION, FLYWP_VERSION, true );
    }
}
