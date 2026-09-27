<?php

namespace FlyWP;

/**
 * Puts a reseller's brand on the WordPress screens and emails that read this plugin's header.
 *
 * Loaded ahead of the API-key gate: the Plugins list, core's update email (sent from cron) and
 * Site Health read the header whether or not the key is set. Without a brand, no hook is added,
 * so the output is the same as before brands existed.
 */
class Branding {

    /**
     * Class constructor.
     */
    public function __construct() {
        if ( ! flywp()->brand()->is_set() ) {
            return;
        }

        add_filter( 'all_plugins', [ $this, 'all_plugins' ] );
        add_filter( 'plugin_row_meta', [ $this, 'plugin_row_meta' ], 10, 2 );
        add_filter( 'auto_plugin_theme_update_email', [ $this, 'update_email' ] );
        add_filter( 'debug_information', [ $this, 'site_health' ] );
    }

    /**
     * This plugin's row in the Plugins list, under the brand.
     *
     * @param array $plugins
     *
     * @return array
     */
    public function all_plugins( $plugins ) {
        if ( isset( $plugins[ FLYWP_PLUGIN_BASENAME ] ) ) {
            $plugins[ FLYWP_PLUGIN_BASENAME ] = self::row( $plugins[ FLYWP_PLUGIN_BASENAME ] );
        }

        return $plugins;
    }

    /**
     * "View details" opens FlyWP's page on wp.org: not under a brand.
     *
     * @param array  $meta
     * @param string $file
     *
     * @return array
     */
    public function plugin_row_meta( $meta, $file ) {
        if ( $file !== FLYWP_PLUGIN_BASENAME ) {
            return $meta;
        }

        return array_values(
            array_filter(
                $meta,
                function ( $link ) {
                    return strpos( (string) $link, 'plugin-install.php?tab=plugin-information' ) === false;
                }
            )
        );
    }

    /**
     * Core's plugin update email, with the brand on this plugin's lines.
     *
     * @param array $email
     *
     * @return array
     */
    public function update_email( $email ) {
        if ( isset( $email['body'] ) && is_string( $email['body'] ) ) {
            $email['body'] = flywp()->brand()->update_email_body( $email['body'] );
        }

        return $email;
    }

    /**
     * Site Health → Info, with the brand on this plugin's entry.
     *
     * @param array $info
     *
     * @return array
     */
    public function site_health( $info ) {
        return is_array( $info ) ? flywp()->brand()->site_health( $info ) : $info;
    }

    /**
     * This plugin's header fields under the brand. Also used for the plugin lists this plugin
     * sends to the dashboard, which read `get_plugins()` directly.
     *
     * @param array $row
     *
     * @return array
     */
    public static function row( array $row ) {
        $brand = flywp()->brand();

        /* translators: %s: the name of the business that hosts the site */
        return $brand->plugin_row( $row, sprintf( __( 'Caching, email and performance tools from %s.', 'flywp' ), $brand->name() ) );
    }
}
