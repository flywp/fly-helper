<?php

namespace FlyWP\Api;

class Ping {

    /**
     * Ping constructor.
     */
    public function __construct() {
        flywp()->router->get( 'ping', [ $this, 'handle_ping' ] );
    }

    /**
     * Handle ping request.
     *
     * @return void
     */
    public function handle_ping() {
        $response = [
            'message'        => 'pong',
            'wp_version'     => get_bloginfo( 'version' ),
            'php_version'    => PHP_VERSION,
            'plugin_version' => FLYWP_VERSION,
        ];

        wp_send_json( $response );
    }
}
