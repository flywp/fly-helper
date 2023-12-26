<?php

namespace FlyWP\Api;

use Core_Upgrader;
use Exception;
use Plugin_Upgrader;
use Theme_Upgrader;
use Throwable;
use WP_Ajax_Upgrader_Skin;

class Updates {

    /**
     * Updates constructor.
     */
    public function __construct() {
        flywp()->router->get( 'updates', [ $this, 'respond' ] );
        flywp()->router->post( 'update-plugins', [ $this, 'update_plugins' ] );
        flywp()->router->post( 'update-themes', [ $this, 'update_themes' ] );
        flywp()->router->post( 'update-core', [ $this, 'update_core' ] );
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
    public function update_plugins( $args ) {
        if ( ! isset( $args['plugins'] ) || ! is_array( $args['plugins'] ) ) {
            wp_send_json_error( 'Missing plugin name(s)' );
        }

        $plugins = array_map( 'sanitize_text_field', wp_unslash( $args['plugins'] ) );

        try {
            $this->update_item( $plugins, 'plugin' );
        } catch ( Throwable $th ) {
            wp_send_json_error( [
                'message' => $th->getMessage(),
            ], 500 );
        }

        wp_send_json_success( [
            'message' => 'Updated successfully.',
        ] );
    }

    /**
     * Update a theme.
     *
     * @param array $args
     *
     * @return void
     */
    public function update_theme( $args ) {
        if ( ! isset( $args['themes'] ) || ! is_array( $args['themes'] ) ) {
            wp_send_json_error( 'Missing theme name(s)' );
        }

        $themes = array_map( 'sanitize_text_field', wp_unslash( $args['themes'] ) );

        try {
            $this->update_item( $themes, 'theme' );
        } catch ( Throwable $th ) {
            wp_send_json_error( [
                'message' => $th->getMessage(),
            ], 500 );
        }

        wp_send_json_success( [
            'message' => 'Updated successfully.',
        ] );
    }

    /**
     * Update WordPress core.
     *
     * @param array $args
     *
     * @return void
     */
    public function update_core( $args ) {
        $version = isset( $args['version'] ) ? sanitize_text_field( $args['version'] ) : false;
        $locale  = isset( $args['locale'] ) ? sanitize_text_field( $args['locale'] ) : get_locale();

        if ( ! function_exists( 'find_core_update' ) ) {
            require_once ABSPATH . 'wp-admin/includes/update.php';
        }

        if ( ! $version ) {
            $updates = get_core_updates();
            $version = $updates[0]->current;
        }

        $update  = find_core_update( $version, $locale );

        if ( ! $update ) {
            wp_send_json_error( [
                'message' => 'Update not found.',
            ] );
        }

        $this->include_files();
        $this->extend_time_limit();

        $upgrader = new Core_Upgrader( new WP_Ajax_Upgrader_Skin() );
        $result   = $upgrader->upgrade( $update );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( [
                'message' => $result->get_error_message(),
            ], 500 );
        }

        wp_send_json_success( [
            'message' => 'Updated successfully.',
        ] );
    }

    /**
     * Include required files.
     *
     * @return void
     */
    private function include_files() {
        require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
        require_once ABSPATH . 'wp-admin/includes/class-automatic-upgrader-skin.php';
        require_once ABSPATH . 'wp-admin/includes/class-wp-ajax-upgrader-skin.php';
        require_once ABSPATH . 'wp-admin/includes/update.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
    }

    /**
     * Increase execution time limit.
     *
     * @return void
     */
    public function extend_time_limit() {
        set_time_limit( 300 );
    }

    /**
     * Update plugins or themes.
     *
     * @param string $type the type of update ('plugin' or 'theme')
     * @param array  $args the update arguments
     *
     * @return void
     */
    private function update_item( $items, $type = 'plugin' ) {
        $this->include_files();

        $skin = new WP_Ajax_Upgrader_Skin();

        if ( $type === 'plugin' ) {
            $upgrader  = new Plugin_Upgrader( $skin );
        } elseif ( $type === 'theme' ) {
            $upgrader  = new Theme_Upgrader( $skin );
        } else {
            throw new Exception( 'Invalid update type' );
        }

        $this->extend_time_limit();
        $result = $upgrader->bulk_upgrade( $items );

        if ( false === $result ) {
            throw new Exception( 'Update failed' );
        }

        return true;
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
