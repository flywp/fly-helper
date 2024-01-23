<?php

namespace FlyWP\Api;

class Fastcgi_Cache {

    /**
     * API constructor.
     */
    public function __construct() {
        flywp()->router->get( 'fastcgi-cache', [ $this, 'handle_cache_setting' ] );
    }

    /**
     * Handle request.
     *
     * @return void
     */
    public function handle_cache_setting( $args ) {
        if ( ! isset( $args['cache'] ) ) {
            wp_send_json_error(
                [
					'message' => 'Missing cache.',
				]
            );
        }

        $valid_types = [ 'fastcgi', 'none' ];
        $cache       = isset( $args['cache'] ) && in_array( $args['cache'], $valid_types ) ? $args['cache'] : 'none';

        $settings            = flywp()->fastcgi->settings();
        $settings['enabled'] = $cache === 'fastcgi' ? true : false;

        update_option( flywp()->fastcgi::SETTINGS_KEY, $settings );

        wp_send_json_success(
            [
				'message' => 'Updated successfully.',
			]
        );
    }
}
