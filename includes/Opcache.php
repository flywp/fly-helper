<?php

namespace FlyWP;

class Opcache {

    public function clear() {
        return opcache_reset();
    }

    public function has_opcache() {
        return function_exists( 'opcache_get_status' );
    }

    public function get_status() {
        return opcache_get_status();
    }

    public function get_config() {
        return opcache_get_configuration();
    }

    public function enabled() {
        if ( ! $this->has_opcache() ) {
            return false;
        }

        $status = $this->get_status();

        return $status['opcache_enabled'] === true;
    }

    public function purge_cache_url() {
        return wp_nonce_url(
            add_query_arg(
                [ 'flywp-action' => 'purge-opcache' ],
                flywp()->admin->page_url()
            ),
            'fly-opcache-purge'
        );
    }
}
