<?php

namespace FlyWP;

/**
 * API class.
 *
 * @since 1.0.0
 */
class Api {

    /**
     * API constructor.
     */
    public function __construct() {
        $router = flywp()->router;
        $router->init();

        new Api\Ping();

        if ( ! $this->has_valid_key() ) {
            return;
        }

        new Api\Plugins();
        new Api\Themes();
        new Api\Updates();
        new Api\Cache();
        new Api\Health();
        new Api\UpdatesData();
    }

    /**
     * Check if API key is valid.
     *
     * @return bool
     */
    public function has_valid_key() {
        $token = $this->get_bearer_token();

        if ( ! flywp()->has_key() || ! is_string( $token ) ) {
            return false;
        }

        return hash_equals( flywp()->get_key(), $token );
    }

    /**
     * Get bearer token from Authorization header.
     *
     * @return string|bool
     */
    public function get_bearer_token() {
        if ( ! isset( $_SERVER['HTTP_AUTHORIZATION'] ) ) {
            return false;
        }

        $auth_header = sanitize_text_field( wp_unslash( $_SERVER['HTTP_AUTHORIZATION'] ) );

        if ( ! preg_match( '/Bearer\s(\S+)/', $auth_header, $matches ) ) {
            return false;
        }

        return $matches[1];
    }
}
