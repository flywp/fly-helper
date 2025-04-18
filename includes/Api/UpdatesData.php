<?php

namespace FlyWP\Api;

class UpdatesData {
    public const CRON_HOOK = 'flywp_send_updates_data';
    public const CRON_INTERVAL = 'twicedaily';

    /**
     * UpdatesData constructor.
     */
    public function __construct() {
        $this->initialize_routes();
        $this->initialize_cron_job();
    }

    /**
     * Initialize API routes.
     */
    private function initialize_routes(): void {
        flywp()->router->get( 'updates-data', [ $this, 'respond' ] );
    }

    /**
     * Initialize cron job for sending updates data.
     */
    private function initialize_cron_job(): void {
        add_action( self::CRON_HOOK, [ $this, 'send_updates_data_to_api' ] );

        if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
            wp_schedule_event( time(), self::CRON_INTERVAL, self::CRON_HOOK );
        }
    }

    /**
     * Send updates data to the remote API.
     */
    public function send_updates_data_to_api(): void {
        $updates_data = $this->get_updates_data();
        flywp()->flyapi->post( '/updates-data', $updates_data );
    }

    /**
     * Handle the API request.
     */
    public function respond(): void {
        wp_send_json( $this->get_updates_data() );
    }

    /**
     * Get updates data.
     *
     * @return array
     */
    private function get_updates_data(): array {
        return [
            'wp_version' => get_bloginfo( 'version' ),
            'updates'    => $this->get_formatted_updates_data(),
        ];
    }

    /**
     * Get formatted updates data.
     *
     * @return array
     */
    private function get_formatted_updates_data(): array {
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
    private function get_formatted_core_updates(): array {
        $core_data = $this->get_core_updates();

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
    private function get_formatted_plugin_updates(): array {
        $this->load_required_files();

        $all_plugins    = get_plugins();
        $plugin_updates = get_plugin_updates();

        $formatted_plugins = [];

        foreach ( $plugin_updates as $plugin_file => $plugin_data ) {
            $plugin_info = $all_plugins[ $plugin_file ];
            $slug        = dirname( $plugin_file );

            $formatted_plugins[] = $this->format_plugin_data( $plugin_info, $plugin_data, $plugin_file, $slug );
        }

        return $formatted_plugins;
    }

    /**
     * Get formatted theme updates data.
     *
     * @return array
     */
    private function get_formatted_theme_updates(): array {
        $theme_updates = get_theme_updates();

        $formatted_themes = [];

        foreach ( $theme_updates as $theme_slug => $theme_data ) {
            $theme              = wp_get_theme( $theme_slug );
            $formatted_themes[] = $this->format_theme_data( $theme, $theme_data, $theme_slug );
        }

        return $formatted_themes;
    }

    /**
     * Check if WordPress core has an update available.
     *
     * @return array
     */
    private function get_core_updates(): array {
        $current = get_bloginfo( 'version' );

        $this->load_required_files();

        $update = get_preferred_from_update_core();

        $response = [
            'version'          => $current,
            'update_available' => false,
            'new_version'      => null,
        ];

        if ( ! isset( $update->response ) || $update->response !== 'upgrade' ) {
            return $response;
        }

        $response['update_available'] = true;
        $response['new_version']      = $update->current;

        return $response;
    }

    /**
     * Format plugin data.
     *
     * @param array $plugin_info
     * @param object $plugin_data
     * @param string $plugin_file
     * @param string $slug
     *
     * @return array
     */
    private function format_plugin_data( array $plugin_info, object $plugin_data, string $plugin_file, string $slug ): array {
        $formatted_plugin = [
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
            $formatted_plugin['extra'] = array_filter( $extra );
        }

        return $formatted_plugin;
    }

    /**
     * Format theme data.
     *
     * @param \WP_Theme $theme
     * @param object $theme_data
     * @param string $theme_slug
     *
     * @return array
     */
    private function format_theme_data( \WP_Theme $theme, object $theme_data, string $theme_slug ): array {
        $formatted_theme = [
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
            $formatted_theme['extra'] = array_filter( $extra );
        }

        return $formatted_theme;
    }

    /**
     * Load required WordPress files.
     */
    private function load_required_files(): void {
        if ( ! function_exists( 'get_plugins' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        if ( ! function_exists( 'get_plugin_updates' ) ) {
            require_once ABSPATH . 'wp-admin/includes/update.php';
        }
    }
}
