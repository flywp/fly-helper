<?php

namespace FlyWP\Api;

class Updates {

    /**
     * Updates constructor.
     */
    public function __construct() {
        flywp()->router->get( 'updates', [ $this, 'respond' ] );
    }

    /**
     * Handle the request.
     *
     * @return void
     */
    public function respond() {
        $response = [
            'core'    => $this->core_updates(),
            'plugins' => $this->has_plugin_update(),
            'themes'  => $this->has_theme_update(),
        ];

        wp_send_json( $response );
    }

    private function core_updates() {
        $update  = get_site_transient( 'update_core' );
        $current = get_bloginfo( 'version' );

        if ( ! function_exists( 'get_preferred_from_update_core' ) ) {
            require_once ABSPATH . 'wp-admin/includes/update.php';
        }

        $update = get_preferred_from_update_core();

        $response = [
            'version'          => $current,
            'update_available' => false,
            'new_version'      => null,
        ];

        if ( ! isset( $update->response ) || 'upgrade' !== $update->response ) {
            return $response;
        }

        $response['update_available'] = true;
        $response['new_version']      = $update->current;

        return $response;
    }

    /**
     * Check if a plugin has an update available.
     *
     * @return bool
     */
    private function has_plugin_update() {
        if ( ! function_exists( 'get_plugins' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        if ( ! function_exists( 'get_plugin_updates' ) ) {
            require_once ABSPATH . 'wp-admin/includes/update.php';
        }

        $updates  = get_plugin_updates();

        if ( $updates && is_array( $updates ) && count( $updates ) > 0 ) {
            return true;
        }

        return false;
    }

    /**
     * Check if a theme has an update available.
     *
     * @return bool
     */
    private function has_theme_update() {
        $updates = get_site_transient( 'update_themes' );

        if ( $updates && is_object( $updates ) && count( $updates->response ) > 0 ) {
            return true;
        }

        return false;
    }
}
