<?php

namespace FlyWP;

class Litespeed {

    public const PLUGIN_SLUG  = 'litespeed-cache/litespeed-cache.php';
    public const OPTION_KEY   = 'litespeed.conf.cache';

    /**
     * Check if LiteSpeed Cache plugin is enabled.
     *
     * @return bool
     */
    public function is_plugin_active() {
        return is_plugin_active( self::PLUGIN_SLUG );
    }

    /**
     * Check if LiteSpeed Cache plugin is installed.
     *
     * @return bool
     */
    public function is_plugin_installed() {
        if ( ! function_exists( 'get_plugins' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $plugins = get_plugins();

        return isset( $plugins[self::PLUGIN_SLUG] );
    }

    /**
     * Check if LiteSpeed Cache is enabled.
     *
     * @return bool
     */
    public function cache_enabled() {
        return $this->is_plugin_active() && get_option( self::OPTION_KEY ) === '1';
    }

    /**
     * Get cache purge URL.
     *
     * @return string
     */
    public function purge_cache_url() {
        if ( ! $this->is_plugin_active() ) {
            return '';
        }

        return \LiteSpeed\Utility::build_url(
            \LiteSpeed\Router::ACTION_PURGE,
            \LiteSpeed\Purge::TYPE_PURGE_ALL_LSCACHE,
            false,
        );
    }

    /**
     * Get settings URL.
     *
     * @return array
     */
    public function settings_url() {
        if ( $this->is_plugin_active() ) {
            return [
                'url'  => admin_url( 'admin.php?page=litespeed-cache#cache' ),
                'text' => __( 'Settings', 'flywp' ),
            ];
        } elseif ( $this->is_plugin_installed() ) {
            return [
                'url' => wp_nonce_url(
                    admin_url( 'plugins.php?action=activate&plugin=' . self::PLUGIN_SLUG ),
                    'activate-plugin_' . self::PLUGIN_SLUG
                ),
                'text' => __( 'Activate Plugin', 'flywp' ),
            ];
        }

        return [
            'url' => wp_nonce_url(
                admin_url( 'update.php?action=install-plugin&plugin=litespeed-cache' ),
                'install-plugin_litespeed-cache'
            ),
            'text' => __( 'Install Plugin', 'flywp' ),
        ];
    }
}
