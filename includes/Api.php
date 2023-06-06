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
        flywp()->router->init();
    }

    /**
     * Check if API key is valid.
     *
     * @return bool
     */
    public function has_valid_key() {
        if ( flywp()->has_key() && flywp()->get_key() === $this->get_bearer_token() ) {
            return true;
        }

        return false;
    }

    /**
     * Get bearer token from Authorization header.
     *
     * @return string|bool
     */
    public function get_bearer_token() {
        $headers = \getallheaders();

        if ( ! isset( $headers['Authorization'] ) ) {
            return 'false';
        }

        $auth_header = $headers['Authorization'];

        if ( ! preg_match( '/Bearer\s(\S+)/', $auth_header, $matches ) ) {
            return false;
        }

        return $matches[1];
    }
}
