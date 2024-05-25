<?php

namespace FlyWP;

class Opcache {

    /**
     * Opcache constructor.
     */
    public function clear() {
        return opcache_reset();
    }

    /**
     * Check if opcache is available.
     *
     * @return bool
     */
    public function has_opcache() {
        return function_exists( 'opcache_get_status' );
    }

    /**
     * Get opcache status.
     *
     * @return array
     */
    public function get_status() {
        return opcache_get_status();
    }

    /**
     * Get opcache config.
     *
     * @return array
     */
    public function get_config() {
        return opcache_get_configuration();
    }

    /**
     * Check if opcache is enabled.
     *
     * @return bool
     */
    public function enabled() {
        if ( ! $this->has_opcache() ) {
            return false;
        }

        $status = $this->get_status();

        return isset( $status['opcache_enabled'] ) && $status['opcache_enabled'] === true;
    }

    /**
     * Get OPcache purge URL.
     *
     * @return string
     */
    public function purge_cache_url() {
        return wp_nonce_url(
            add_query_arg(
                [ 'flywp-action' => 'purge-opcache' ],
                admin_url( 'index.php?page=flywp' ),
            ),
            'fly-opcache-purge'
        );
    }
}
