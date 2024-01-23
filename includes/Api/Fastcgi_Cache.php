<?php

namespace FlyWP\Api;

class Fastcgi_Cache {

    /**
     * API constructor.
     */
    public function __construct() {
        flywp()->router->post( 'fastcgi-cache', [ $this, 'handle_cache_setting' ] );
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
}
