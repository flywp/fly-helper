<?php

namespace FlyWP\Api;

class Fastcgi_Cache {
    
    /**
     * API constructor.
     */
    public function __construct()
    {
        flywp()->router->post( 'fastcgi-cache', [ $this, 'handle_cache_setting' ] );
    }

    /**
     * Handle request.
     *
     * @return void
     */
    public function handle_cache_setting($args)
    {
        if ( !isset( $args['status'] ) ) {
            wp_send_json_error( [
                'message' => 'Missing status.',
            ] );
        }

        $valid_types = [ 'enable', 'disable' ];
        $type        = isset( $_GET['type'] ) && in_array( $_GET['type'], $valid_types ) ? $_GET['type'] : 'enable';

        $settings            = flywp()->fastcgi->settings();
        $settings['enabled'] = $type === 'enable' ? true : false;

        update_option( flywp()->fastcgi::SETTINGS_KEY, $settings );

        wp_send_json_success( [
            'message' => 'Updated successfully.',
        ] );
    }
}
