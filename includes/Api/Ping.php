<?php

namespace FlyWP\Api;

class Ping {

    public function __construct() {
        flywp()->api()->get( 'ping', [ $this, 'handle_ping' ] );
    }

    public function handle_ping() {
        $response = [
            'message'     => 'Pong',
            'wp_version'  => get_bloginfo( 'version' ),
            'php_version' => PHP_VERSION,
        ];

        wp_send_json( $response );
    }
}
