<?php

namespace FlyWP\Api;

class UpdatesData {
    /**
     * UpdatesData constructor.
     */
    public function __construct() {
        flywp()->router->get( 'updates-data', [ $this, 'respond' ] );

        // Register the cron event
        add_action( 'flywp_send_updates_data', [ $this, 'send_updates_data_to_api' ] );

        // Schedule the cron event if not already scheduled
        if ( ! wp_next_scheduled( 'flywp_send_updates_data' ) ) {
            wp_schedule_event( time(), 'twicedaily', 'flywp_send_updates_data' );
        }
    }

    /**
     * Send updates data to the remote API.
     *
     * @return void
     */
    public function send_updates_data_to_api() {
        $updates_data = [
            'wp_version' => get_bloginfo( 'version' ),
            'updates'    => $this->get_formatted_updates_data(),
        ];

        flywp()->flyapi->post( '/updates-data', $updates_data );
    }

    /**
     * Handle the request.
     *
     * @return void
     */
    public function respond() {
        $response = [
            'wp_version' => get_bloginfo( 'version' ),
            'updates'    => $this->get_formatted_updates_data(),
        ];

        wp_send_json( $response );
    }

    /**
     * Get formatted updates data.
     *
     * @return array
     */
    private function get_formatted_updates_data() {
        return [
            'core'    => $this->get_formatted_core_updates(),
            'plugins' => $this->get_formatted_plugin_updates(),
            'themes'  => $this->get_formatted_theme_updates(),
        ];
    }

    /**
     * Get formatted core updates data.
     *
     * @return array
     */
    private function get_formatted_core_updates() {
        $core_data = $this->core_updates();

        if ( ! $core_data['update_available'] ) {
            return [];
        }

        return [
            'installed_version' => $core_data['version'],
            'latest_version'    => $core_data['new_version'],
        ];
    }

    /**
     * Get formatted plugin updates data.
     *
     * @return array
     */
    private function get_formatted_plugin_updates() {
        if ( ! function_exists( 'get_plugins' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        if ( ! function_exists( 'get_plugin_updates' ) ) {
            require_once ABSPATH . 'wp-admin/includes/update.php';
        }

        $all_plugins    = get_plugins();
        $plugin_updates = get_plugin_updates();

        $formatted_plugins = [];

        foreach ( $plugin_updates as $plugin_file => $plugin_data ) {
            $plugin_info = $all_plugins[ $plugin_file ];
            $slug        = dirname( $plugin_file );

            $plugin_info = [
                'slug'              => $slug,
                'name'              => $plugin_info['Name'],
                'installed_version' => $plugin_info['Version'],
                'latest_version'    => $plugin_data->update->new_version,
                'is_active'         => is_plugin_active( $plugin_file ),
            ];

            $extra = [
                'url'         => $plugin_info['PluginURI'] ?? '',
                'author'      => $plugin_info['Author'] ?? '',
                'file'        => $plugin_file,
                'textdomain'  => $plugin_info['TextDomain'] ?? '',
                'description' => $plugin_info['Description'] ?? '',
                'php'         => $plugin_info['RequiresPHP'] ?? '',
            ];

            if ( ! empty( array_filter( $extra ) ) ) {
                $plugin_info['extra'] = array_filter( $extra );
            }

            $formatted_plugins[] = $plugin_info;
        }

        return $formatted_plugins;
    }

    /**
     * Get formatted theme updates data.
     *
     * @return array
     */
    private function get_formatted_theme_updates() {
        $theme_updates = get_theme_updates();

        $formatted_themes = [];

        foreach ( $theme_updates as $theme_slug => $theme_data ) {
            $theme = wp_get_theme( $theme_slug );

            $theme_info = [
                'slug'              => $theme_slug,
                'name'              => $theme->get( 'Name' ),
                'installed_version' => $theme->get( 'Version' ),
                'latest_version'    => $theme_data->update['new_version'],
                'is_active'         => ( get_stylesheet() === $theme_slug ),
            ];

            $extra = [
                'url' => $theme->get( 'ThemeURI' ),
            ];

            if ( ! empty( array_filter( $extra ) ) ) {
                $theme_info['extra'] = array_filter( $extra );
            }

            $formatted_themes[] = $theme_info;
        }

        return $formatted_themes;
    }

    /**
     * Check if WordPress core has an update available.
     *
     * @return array
     */
    private function core_updates() {
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
     * Deactivate the scheduler when the plugin is deactivated.
     */
    public function deactivate() {
        $timestamp = wp_next_scheduled( 'flywp_send_updates_data' );
        if ( $timestamp ) {
            wp_unschedule_event( $timestamp, 'flywp_send_updates_data' );
        }
    }
}
