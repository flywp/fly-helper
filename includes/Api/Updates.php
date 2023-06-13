<?php

namespace FlyWP\Api;

use Plugin_Upgrader;
use WP_Ajax_Upgrader_Skin;

class Updates {

    /**
     * Updates constructor.
     */
    public function __construct() {
        flywp()->router->get( 'updates', [ $this, 'respond' ] );
        flywp()->router->post( 'update-plugin', [ $this, 'update_plugin' ] );
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

    /**
     * Update a plugin.
     *
     * @param array $args
     *
     * @return void
     */
    public function update_plugin( $args ) {
        if ( ! isset( $args['plugin'] ) ) {
            wp_send_json_error( 'Missing plugin name' );
        }

        $plugin_file = sanitize_text_field( wp_unslash( $args['plugin'] ) );
        $plugin      = plugin_basename( $plugin_file );

        require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
        require_once ABSPATH . 'wp-admin/includes/class-automatic-upgrader-skin.php';
        require_once ABSPATH . 'wp-admin/includes/class-wp-ajax-upgrader-skin.php';
        require_once ABSPATH . 'wp-admin/includes/update.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/plugin.php';

        $plugin_info = get_plugin_updates();

        if ( isset( $plugin_info[$plugin_file] ) && isset( $plugin_info[$plugin_file]->update ) ) {
            $skin     = new WP_Ajax_Upgrader_Skin();
            $upgrader = new Plugin_Upgrader( $skin );
            $result   = $upgrader->bulk_upgrade( [$plugin_file] );

            if ( is_wp_error( $skin->result ) ) {
                wp_send_json_error( [
                    'code'    => $skin->result->get_error_code(),
                    'message' => $skin->result->get_error_message(),
                ] );
            } elseif ( $skin->get_errors()->has_errors() ) {
                wp_send_json_error( [
                    'message' => $skin->result->get_error_message(),
                ] );
            } elseif ( is_array( $result ) && !empty( $result[ $plugin ] ) ) {
                if ( true === $result[ $plugin ] ) {
                    wp_send_json_error( [
                        'message' => $upgrader->strings['up_to_date'],
                    ] );
                }

                wp_send_json_success( [
                    'message' => 'Plugin updated successfully.',
                ] );
            }
        }

        wp_send_json_error( [
            'message' => 'Plugin update failed.',
        ] );
    }

    /**
     * Check if WordPress core has an update available.
     *
     * @return array
     */
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
            return count( $updates );
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
            return count( $updates->response );
        }

        return false;
    }
}
