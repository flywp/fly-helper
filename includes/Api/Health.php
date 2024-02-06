<?php

namespace FlyWP\Api;

use WP_Debug_Data;

class Health {

    /**
     * Class constructor.
     */
    public function __construct() {
        flywp()->router->get( 'health', [ $this, 'respond' ] );
    }

    /**
     * Respond to health check request.
     *
     * @return void
     */
    public function respond() {
        if ( ! class_exists( 'WP_Debug_Data' ) ) {
            require_once ABSPATH . 'wp-admin/includes/class-wp-debug-data.php';
        }

        if ( ! function_exists( 'get_core_updates' ) ) {
            require_once ABSPATH . 'wp-admin/includes/update.php';
            require_once ABSPATH . 'wp-admin/includes/misc.php';
        }

        WP_Debug_Data::check_for_updates();

        $response = WP_Debug_Data::debug_data();

        wp_send_json( $response );
    }
}
