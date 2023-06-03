<?php

namespace FlyWP;

use WP_Error;

class Fastcgi_Cache {

    /**
     * Cache path.
     *
     * @var string
     */
    const CACHE_PATH = '/var/run/nginx-cache';

    /**
     * Cache option settings key.
     *
     * @var string
     */
    const SETTINGS_KEY = 'flywp_fastcgi_cache';

    public function __construct() {
        add_action( 'save_post', [ $this, 'purge_post_cache' ], 10, 2 );
        add_action( 'edited_term', [ $this, 'purge_taxonomy_cache' ], 10, 2 );
        add_action( 'wp_update_nav_menu', [ $this, 'purge_home_cache' ], 10, 2 );
    }

    /**
     * Check if the cache is enabled.
     *
     * @return bool
     */
    public function enabled() {
        return $this->settings() && $this->settings()['enabled'];
    }

    /**
     * Get all cache settings.
     *
     * @return array
     */
    public function settings() {
        $default = [
            'enabled'         => true,
            'home_created'    => true,
            'home_deleted'    => true,
            'single_modified' => true,
            'single_comment'  => true,
        ];

        return get_option( self::SETTINGS_KEY, $default );
    }

    /**
     * Get a cache setting.
     *
     * @param string $key Setting key
     *
     * @return mixed
     */
    public function get_setting( $key ) {
        return isset( $this->settings()[$key] ) ? $this->settings()[$key] : false;
    }

    /**
     * Purge all cache.
     *
     * @return void
     */
    public function clear_cache() {
        global $wp_filesystem;

        if ( ! is_object( $wp_filesystem ) ) {
            require_once ABSPATH . '/wp-admin/includes/file.php';
            WP_Filesystem();
        }

        if ( ! $wp_filesystem->exists( self::CACHE_PATH ) ) {
            return new WP_Error( 'cache_not_exists', __( 'Cache directory does not exist.', 'flywp' ) );
        }

        if ( ! $wp_filesystem->is_dir( self::CACHE_PATH ) ) {
            return new WP_Error( 'cache_not_dir', __( 'Cache directory is not a directory.', 'flywp' ) );
        }

        if ( ! $wp_filesystem->is_writable( self::CACHE_PATH ) ) {
            return new WP_Error( 'cache_not_writable', __( 'Cache directory is not writable.', 'flywp' ) );
        }

        $wp_filesystem->rmdir( self::CACHE_PATH, true );
        $wp_filesystem->mkdir( self::CACHE_PATH );

        return true;
    }

    /**
     * Purge cache by URL.
     *
     * @param string $url URL to purge
     *
     * @return void
     */
    public function purge_cache_by_url( $url ) {
        $parsed = wp_parse_url( $url );
        $path   = isset( $parsed['path'] ) ? $parsed['path'] : '';

        // cache key format (without space): $protocal $method $host $path
        // because of nginx-proxy, all request will be http, so we don't need to check protocal
        // $method is always GET as we only cache GET request
        $hash   = md5( 'httpGET' . $parsed['host'] . $path );

        $cache_path = self::CACHE_PATH . '/' . substr( $hash, -1 ) . '/' . substr( $hash, -3, 2 ) . '/' . $hash;

        if ( file_exists( $cache_path ) ) {
            unlink( $cache_path );
        }
    }

    public function purge_home_cache() {
    }

    public function purge_post_cache( $post_id, $post ) {
    }

    public function purge_taxonomy_cache( $term_id, $taxonomy ) {
    }

    public function purge_archive_cache() {
    }
}
