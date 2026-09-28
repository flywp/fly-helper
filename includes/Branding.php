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
        add_filter( 'auto_plugin_theme_update_email', [ $this, 'update_email' ], 10, 4 );
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
     * Core's plugin update email, with the brand on this plugin's line and without its wp.org link.
     *
     * The line is rebuilt the way core builds it, with core's own translated format, so it is
     * found in any language and only for this plugin, never for another one named "FlyWP …".
     *
     * @param array $email
     * @param string $type
     * @param array $successful
     * @param array $failed
     *
     * @return array
     */
    public function update_email( $email, $type = '', $successful = [], $failed = [] ) {
        if ( ! isset( $email['body'] ) || ! is_string( $email['body'] ) ) {
            return $email;
        }

        foreach ( [ $successful, $failed ] as $updates ) {
            foreach ( isset( $updates['plugin'] ) && is_array( $updates['plugin'] ) ? $updates['plugin'] : [] as $item ) {
                if ( ! isset( $item->item->plugin ) || $item->item->plugin !== FLYWP_PLUGIN_BASENAME ) {
                    continue;
                }

                $url  = empty( $item->item->url ) ? '' : ' : ' . esc_url( $item->item->url );
                $name = flywp()->brand()->name();

                // phpcs:disable WordPress.WP.I18n.MissingArgDomain -- core's own strings, to match core's line.
                if ( ! empty( $item->item->current_version ) ) {
                    /* translators: 1: Plugin name, 2: Current version number, 3: New version number, 4: Plugin URL. */
                    $format = __( '- %1$s (from version %2$s to %3$s)%4$s' );
                    $core   = sprintf( $format, html_entity_decode( $item->name ), $item->item->current_version, $item->item->new_version, $url );
                    $brand  = sprintf( $format, $name, $item->item->current_version, $item->item->new_version, '' );
                } else {
                    /* translators: 1: Plugin name, 2: Version number, 3: Plugin URL. */
                    $format = __( '- %1$s version %2$s%3$s' );
                    $core   = sprintf( $format, html_entity_decode( $item->name ), $item->item->new_version, $url );
                    $brand  = sprintf( $format, $name, $item->item->new_version, '' );
                }
                // phpcs:enable

                $email['body'] = str_replace( $core, $brand, $email['body'] );
            }
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
