<?php

namespace FlyWP\Admin;

use FlyWP\Admin;
use FlyWP\LiteSpeed as FlyWPLiteSpeed;

/**
 * LiteSpeed class.
 */
class Litespeed {

    /**
     * Class constructor.
     */
    public function __construct() {
        add_action( 'load-' . Admin::SCREEN_NAME, [ $this, 'handle_enable_disable' ] );
    }

    /**
     * Get enable/disable url.
     *
     * @param string $action
     *
     * @return string
     */
    public function enable_disable_url( $action = 'enable' ) {
        return wp_nonce_url(
            add_query_arg(
                [
                    'flywp-action' => 'toggle-lscache',
                    'type'         => $action,
                ],
                flywp()->admin->page_url()
            ),
            'flywp-litespeed-nonce'
        );
    }

    /**
     * Handle enable/disable action.
     *
     * @return void
     */
    public function handle_enable_disable() {
        if ( ! isset( $_GET['flywp-action'] ) || 'toggle-lscache' !== $_GET['flywp-action'] ) {
            return;
        }

        if ( isset( $_GET['_wpnonce'] ) && ! wp_verify_nonce( wp_unslash( $_GET['_wpnonce'] ), 'flywp-litespeed-nonce' ) ) {
            return;
        }

        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $valid_types = [ 'enable', 'disable' ];
        $type        = isset( $_GET['type'] ) && in_array( wp_unslash( $_GET['type'] ), $valid_types, true ) ? wp_unslash( $_GET['type'] ) : 'enable';
        $status      = $type === 'enable' ? '1' : '0';
        $notice      = $type === 'enable' ? 'lscache-enabled' : 'lscache-disabled';

        update_option( FlyWPLiteSpeed::OPTION_KEY, $status );

        flywp()->flyapi->cache_toggle( $type, 'lscache' );

        wp_safe_redirect( admin_url( 'index.php?page=flywp&fly-notice=' . $notice ) );
        exit;
    }
}
