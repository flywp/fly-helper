<?php

namespace FlyWP\Frontend;

/**
 * Magic Login.
 *
 * @since 1.0.0
 */
class MagicLogin {

    /**
     * Plugin Constructor.
     *
     * @return void
     */
    public function __construct() {
        add_action( 'setup_theme', [ $this, 'login_user' ] );
    }

    /**
     * Check if the request is valid.
     *
     * @return bool
     */
    private function is_valid_request() {
        return isset( $_SERVER['REQUEST_URI'] ) && isset( $_SERVER['REQUEST_METHOD'] ) && $_SERVER['REQUEST_URI'] === '/flywp-magic-login' && $_SERVER['REQUEST_METHOD'] === 'POST';
    }

    /**
     * Redirect to home.
     *
     * @return void
     */
    public function redirect_to_home() {
        wp_safe_redirect( site_url() );
        exit;
    }

    /**
     * Redirect to admin.
     *
     * @return void
     */
    public function redirect_to_admin() {
        wp_safe_redirect( admin_url() );
        exit;
    }

    /**
     * Login an user.
     *
     * @return void
     */
    public function login_user() {
        if ( ! $this->is_valid_request() ) {
            return;
        }

        // phpcs:disable WordPress.Security.NonceVerification.Missing
        $api_key  = isset( $_POST['api_key'] ) ? sanitize_text_field( wp_unslash( $_POST['api_key'] ) ) : '';
        $username = isset( $_POST['username'] ) ? sanitize_text_field( wp_unslash( $_POST['username'] ) ) : '';
        // phpcs:enable WordPress.Security.NonceVerification.Missing

        if ( ! $api_key || ! $username ) {
            $this->redirect_to_home();
        }

        if ( $api_key !== flywp()->get_api_key() ) {
            $this->redirect_to_home();
        }

        if ( is_user_logged_in() ) {
            $this->redirect_to_admin();
        }

        $user = get_user_by( 'login', $username );

        if ( ! $user ) {
            $this->redirect_to_admin();
        }

        wp_set_current_user( $user->ID, $user->user_login );
        wp_set_auth_cookie( $user->ID );

        // redirect to admin
        $this->redirect_to_admin();
    }
}
