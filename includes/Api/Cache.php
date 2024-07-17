<?php

namespace FlyWP\Api;

use FlyWP\LiteSpeed;

class Cache {

    /**
     * API constructor.
     */
    public function __construct() {
        flywp()->router->post( 'fastcgi-cache', [ $this, 'fastcgi_setting' ] );
        flywp()->router->post( 'ls-cache', [ $this, 'lscache_setting' ] );
    }

    /**
     * Handle request.
     *
     * @return void
     */
    public function handle_cache_setting( $args ) {
        if ( ! isset( $args['status'] ) ) {
            wp_send_json_error(
                [
                    'message' => 'Missing status.',
                ]
            );
        }

        $enabled = isset( $args['status'] ) && $args['status'] === 'enabled' ? true : false;

        $settings            = flywp()->fastcgi->settings();
        $settings['enabled'] = $enabled;

        update_option( flywp()->fastcgi::SETTINGS_KEY, $settings );

        wp_send_json_success(
            [
                'message' => 'Updated successfully.',
            ]
        );
    }

    /**
     * Handle request.
     *
     * @return void
     */
    public function lscache_setting( $args ) {
        print_r( $args );

        if ( ! isset( $args['status'] ) ) {
            wp_send_json_error(
                [
                    'message' => 'Missing status.',
                ]
            );
        }

        $status = isset( $args['status'] ) && $args['status'] === 'enabled' ? '1' : '0';

        update_option( LiteSpeed::SETTINGS_KEY, $status );

        wp_send_json_success(
            [
                'message' => 'Updated successfully.',
            ]
        );
    }
}
