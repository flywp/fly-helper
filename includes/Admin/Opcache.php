<?php

namespace FlyWP\Admin;

use FlyWP\Admin;

class Opcache {

    /**
     * Class constructor.
     */
    public function __construct() {
        add_action( 'load-' . Admin::SCREEN_NAME, [ $this, 'handle_cleanup' ] );
    }

    /**
     * Handle cache cleanup.
     *
     * @return void
     */
    public function handle_cleanup() {
        if ( ! isset( $_GET['flywp-action'] ) || 'purge-opcache' !== $_GET['flywp-action'] ) {
            return;
        }

        if ( isset( $_GET['_wpnonce'] ) && ! wp_verify_nonce( $_GET['_wpnonce'], 'fly-opcache-purge' ) ) {
            wp_die( __( 'Nonce verification failed.', 'flywp' ) );
        }

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'You do not have permission to purge the opcache.', 'flywp' ) );
        }

        flywp()->opcache->clear();

        wp_safe_redirect( admin_url( 'index.php?page=flywp&fly-notice=opcache-purged' ) );
        exit;
    }
}
