<?php

namespace FlyWP\Api;

class UpdatesData {
    private const CRON_HOOK = 'flywp_send_updates_data';
    private const CRON_INTERVAL = 'twicedaily';

    /**
     * UpdatesData constructor.
     */
    public function __construct() {
        $this->initializeRoutes();
        $this->initializeCronJob();
    }

    /**
     * Initialize API routes.
     */
    private function initializeRoutes(): void {
        flywp()->router->get( 'updates-data', [ $this, 'handleApiRequest' ] );
    }

    /**
     * Initialize cron job for sending updates data.
     */
    private function initializeCronJob(): void {
        add_action( self::CRON_HOOK, [ $this, 'sendUpdatesDataToApi' ] );

        if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
            wp_schedule_event( time(), self::CRON_INTERVAL, self::CRON_HOOK );
        }
    }

    /**
     * Send updates data to the remote API.
     */
    public function sendUpdatesDataToApi(): void {
        $updatesData = $this->getUpdatesData();
        flywp()->flyapi->post( '/updates-data', $updatesData );
    }

    /**
     * Handle the API request.
     */
    public function handleApiRequest(): void {
        wp_send_json( $this->getUpdatesData() );
    }

    /**
     * Get updates data.
     *
     * @return array
     */
    private function getUpdatesData(): array {
        return [
            'wp_version' => get_bloginfo( 'version' ),
            'updates'    => $this->getFormattedUpdatesData(),
        ];
    }

    /**
     * Get formatted updates data.
     *
     * @return array
     */
    private function getFormattedUpdatesData(): array {
        return [
            'core'    => $this->getFormattedCoreUpdates(),
            'plugins' => $this->getFormattedPluginUpdates(),
            'themes'  => $this->getFormattedThemeUpdates(),
        ];
    }

    /**
     * Get formatted core updates data.
     *
     * @return array
     */
    private function getFormattedCoreUpdates(): array {
        $coreData = $this->getCoreUpdates();

        if ( ! $coreData['update_available'] ) {
            return [];
        }

        return [
            'installed_version' => $coreData['version'],
            'latest_version'    => $coreData['new_version'],
        ];
    }

    /**
     * Get formatted plugin updates data.
     *
     * @return array
     */
    private function getFormattedPluginUpdates(): array {
        $this->loadRequiredFiles();

        $allPlugins    = get_plugins();
        $pluginUpdates = get_plugin_updates();

        $formattedPlugins = [];

        foreach ( $pluginUpdates as $pluginFile => $pluginData ) {
            $pluginInfo = $allPlugins[ $pluginFile ];
            $slug       = dirname( $pluginFile );

            $formattedPlugins[] = $this->formatPluginData( $pluginInfo, $pluginData, $pluginFile, $slug );
        }

        return $formattedPlugins;
    }

    /**
     * Get formatted theme updates data.
     *
     * @return array
     */
    private function getFormattedThemeUpdates(): array {
        $themeUpdates = get_theme_updates();

        $formattedThemes = [];

        foreach ( $themeUpdates as $themeSlug => $themeData ) {
            $theme             = wp_get_theme( $themeSlug );
            $formattedThemes[] = $this->formatThemeData( $theme, $themeData, $themeSlug );
        }

        return $formattedThemes;
    }

    /**
     * Check if WordPress core has an update available.
     *
     * @return array
     */
    private function getCoreUpdates(): array {
        $current = get_bloginfo( 'version' );

        $this->loadRequiredFiles();

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
     * @param array $pluginInfo
     * @param object $pluginData
     * @param string $pluginFile
     * @param string $slug
     *
     * @return array
     */
    private function formatPluginData( array $pluginInfo, object $pluginData, string $pluginFile, string $slug ): array {
        $formattedPlugin = [
            'slug'              => $slug,
            'name'              => $pluginInfo['Name'],
            'installed_version' => $pluginInfo['Version'],
            'latest_version'    => $pluginData->update->new_version,
            'is_active'         => is_plugin_active( $pluginFile ),
        ];

        $extra = [
            'url'         => $pluginInfo['PluginURI'] ?? '',
            'author'      => $pluginInfo['Author'] ?? '',
            'file'        => $pluginFile,
            'textdomain'  => $pluginInfo['TextDomain'] ?? '',
            'description' => $pluginInfo['Description'] ?? '',
            'php'         => $pluginInfo['RequiresPHP'] ?? '',
        ];

        if ( ! empty( array_filter( $extra ) ) ) {
            $formattedPlugin['extra'] = array_filter( $extra );
        }

        return $formattedPlugin;
    }

    /**
     * Format theme data.
     *
     * @param \WP_Theme $theme
     * @param object $themeData
     * @param string $themeSlug
     *
     * @return array
     */
    private function formatThemeData( \WP_Theme $theme, object $themeData, string $themeSlug ): array {
        $formattedTheme = [
            'slug'              => $themeSlug,
            'name'              => $theme->get( 'Name' ),
            'installed_version' => $theme->get( 'Version' ),
            'latest_version'    => $themeData->update['new_version'],
            'is_active'         => ( get_stylesheet() === $themeSlug ),
        ];

        $extra = [
            'url' => $theme->get( 'ThemeURI' ),
        ];

        if ( ! empty( array_filter( $extra ) ) ) {
            $formattedTheme['extra'] = array_filter( $extra );
        }

        return $formattedTheme;
    }

    /**
     * Load required WordPress files.
     */
    private function loadRequiredFiles(): void {
        if ( ! function_exists( 'get_plugins' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        if ( ! function_exists( 'get_plugin_updates' ) ) {
            require_once ABSPATH . 'wp-admin/includes/update.php';
        }
    }

    /**
     * Deactivate the scheduler when the plugin is deactivated.
     */
    public function deactivate(): void {
        $timestamp = wp_next_scheduled( self::CRON_HOOK );
        if ( $timestamp ) {
            wp_unschedule_event( $timestamp, self::CRON_HOOK );
        }
    }
}
