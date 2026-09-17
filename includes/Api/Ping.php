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
     * `has_key` is the field that answers "does this integration work". The route itself
     * no longer does: it is registered whether or not the plugin found an API key, so a
     * 200 here proves only that the plugin loaded. A caller deciding whether a site is
     * healthy has to read this field, not the status code.
     *
     * @return void
     */
    public function handle_ping() {
        $response = [
            'message'        => 'pong',
            'wp_version'     => get_bloginfo( 'version' ),
            'php_version'    => PHP_VERSION,
            'plugin_version' => FLYWP_VERSION,
            'has_key'        => flywp()->has_key(),
        ];

        wp_send_json( $response );
    }
}
