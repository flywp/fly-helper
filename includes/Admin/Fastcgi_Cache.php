<?php

namespace FlyWP\Admin;

use FlyWP\Admin;

class Fastcgi_Cache {

    /**
     * Class constructor.
     */
    public function __construct() {
        add_action( 'load-' . Admin::SCREEN_NAME, [ $this, 'save_settings' ] );
        add_action( 'load-' . Admin::SCREEN_NAME, [ $this, 'handle_cleanup' ] );
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
                    'flywp-action' => 'toggle-fastcgi-cache',
                    'type'         => $action,
                ],
                flywp()->admin->page_url()
            ),
            'fly-fastcgi-toggle-cache'
        );
    }

    /**
     * Handle cache cleanup.
     *
     * @return void
     */
    public function handle_cleanup() {
        if ( ! isset( $_GET['flywp-action'] ) || 'purge-fastcgi-cache' !== $_GET['flywp-action'] ) {
            return;
        }

        if ( isset( $_GET['_wpnonce'] ) && ! wp_verify_nonce( $_GET['_wpnonce'], 'fly-fastcgi-purge-cache' ) ) {
            return;
        }

        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $page_id = isset( $_GET['page_id'] ) ? absint( $_GET['page_id'] ) : 0;

        if ( $page_id ) {
            flywp()->fastcgi->purge_cache_by_url( get_permalink( $page_id ) );
        } else {
            flywp()->fastcgi->clear_cache();
        }

        wp_safe_redirect( admin_url( 'index.php?page=flywp&fly-notice=fastcgi-purged' ) );
        exit;
    }

    /**
     * Save cache settings.
     *
     * @return void
     */
    public function save_settings() {
        if ( ! isset( $_POST['flywp-fastcgi-nonce'] ) ) {
            return;
        }

        if ( ! wp_verify_nonce( $_POST['flywp-fastcgi-nonce'], 'flywp-fastcgi-nonce' ) ) {
            return;
        }

        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $settings = [];
        $keys     = [
            'home-purge-created'  => 'home_created',
            'home-purge-deleted'  => 'home_deleted',
            // 'single-post-created' => 'single_modified',
            // 'single-post-comment' => 'single_comment',
        ];

        foreach ( $keys as $form_key => $settings_key ) {
            $settings[ $settings_key ] = isset( $_POST[ $form_key ] ) ? true : false;
        }

        $old_settings = flywp()->fastcgi->settings();
        $settings     = array_merge( $old_settings, $settings );

        update_option( flywp()->fastcgi::SETTINGS_KEY, $settings );

        wp_safe_redirect( admin_url( 'index.php?page=flywp&fly-notice=fastcgi-saved' ) );
        exit;
    }

    /**
     * Handle enable/disable.
     *
     * @return void
     */
    public function handle_enable_disable() {
        if ( ! isset( $_GET['flywp-action'] ) || 'toggle-fastcgi-cache' !== $_GET['flywp-action'] ) {
            return;
        }

        if ( isset( $_GET['_wpnonce'] ) && ! wp_verify_nonce( $_GET['_wpnonce'], 'fly-fastcgi-toggle-cache' ) ) {
            return;
        }

        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $valid_types = [ 'enable', 'disable' ];
        $type        = isset( $_GET['type'] ) && in_array( $_GET['type'], $valid_types ) ? $_GET['type'] : 'enable';

        $settings            = flywp()->fastcgi->settings();
        $settings['enabled'] = $type === 'enable' ? true : false;
        $notice              = $type === 'enable' ? 'fastcgi-enabled' : 'fastcgi-disabled';

        flywp()->flyapi->cache_toggle( $type );

        update_option( flywp()->fastcgi::SETTINGS_KEY, $settings );

        wp_safe_redirect( admin_url( 'index.php?page=flywp&fly-notice=' . $notice ) );
        exit;
    }
}
