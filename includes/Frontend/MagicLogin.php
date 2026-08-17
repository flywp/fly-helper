<?php

namespace FlyWP\Frontend;

use FlyWP\MagicLoginToken;
use WP_User;

/**
 * Magic Login.
 *
 * Signs a user in from a signed, single-use token minted by the FlyWP control plane. The token
 * names the user it is good for, so the request cannot choose one; an unknown user is refused
 * rather than substituted for an administrator.
 *
 * @since 1.0.0
 */
class MagicLogin {

    /**
     * Path this handler answers on.
     */
    const PATH = '/flywp-magic-login';

    /**
     * Option prefix recording tokens that have already been spent.
     */
    const SPENT_PREFIX = 'flywp_ml_used_';

    /**
     * Plugin Constructor.
     *
     * @return void
     */
    public function __construct() {
        // `setup_theme` deliberately, and it must stay that way. WordPress includes the active
        // theme's functions.php *after* this hook and before `init`, so running any later means a
        // theme with a fatal, an early redirect, or stray output past its closing tag takes magic
        // login down with it — and getting into wp-admin to fix exactly that is what this is for.
        //
        // Moving later buys nothing for security plugins either: `plugins_loaded` fires before
        // `setup_theme`, so they have already loaded and can hook here too.
        add_action( 'setup_theme', [ $this, 'login_user' ] );
    }

    /**
     * Check if the request is valid.
     *
     * @return bool
     */
    private function is_valid_request() {
        if ( ! isset( $_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD'] ) ) {
            return false;
        }

        if ( wp_unslash( $_SERVER['REQUEST_METHOD'] ) !== 'POST' ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
            return false;
        }

        // Compared as a path. Do not swap in `sanitize_text_field()`, which alters the value.
        $path = wp_parse_url( wp_unslash( $_SERVER['REQUEST_URI'] ), PHP_URL_PATH ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

        return $path === self::PATH;
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

        $token = $this->post_field( 'token' );

        if ( $token === '' ) {
            $this->refuse( 'missing_token' );
        }

        $claims = MagicLoginToken::parse( $token, flywp()->get_login_public_key(), time() );

        if ( ! $claims ) {
            $this->refuse( 'invalid_token' );
        }

        if ( ! $this->spend_token( $claims['jti'], $claims['exp'] ) ) {
            $this->refuse( 'token_already_used', $claims['sid'] );
        }

        $user = get_user_by( 'login', $claims['sub'] );

        if ( ! $user instanceof WP_User ) {
            // Fail closed on an unknown user; never substitute another account.
            $this->refuse( 'unknown_user', $claims['sid'] );
        }

        wp_set_current_user( $user->ID, $user->user_login );
        wp_set_auth_cookie( $user->ID );

        /**
         * Fires after magic login has signed a user in.
         *
         * @since 1.6.0
         *
         * @param int    $user_id    ID of the user signed in.
         * @param string $user_login Login name of the user signed in.
         * @param int    $site_id    FlyWP site id the token was minted for.
         */
        do_action( 'flywp_magic_login_success', $user->ID, $user->user_login, $claims['sid'] );

        $this->redirect_to_admin();
    }

    /**
     * Mark a token as spent, refusing a second use of the same one.
     *
     * Written before the cookie is issued. Uses an options row rather than a transient so the
     * unique index on `option_name` decides the outcome; keep it that way.
     *
     * @param string $jti Token identifier.
     * @param int    $exp Token expiry, as a unix timestamp.
     *
     * @return bool False when this token has been used already, or the marker could not be stored.
     */
    private function spend_token( $jti, $exp ) {
        global $wpdb;

        $this->forget_spent_tokens();

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $inserted = $wpdb->query(
            $wpdb->prepare(
                "INSERT IGNORE INTO {$wpdb->options} ( option_name, option_value, autoload ) VALUES ( %s, %s, 'no' )",
                self::SPENT_PREFIX . $jti,
                (string) $exp
            )
        );

        // 0 rows means the marker was already there; false means the write failed. Neither is a
        // login: a token we cannot prove is unused is a token we refuse.
        return $inserted === 1;
    }

    /**
     * Drop spent-token markers that have outlived the tokens they describe.
     *
     * Options carry no expiry of their own, so nothing else would ever collect these.
     *
     * @return void
     */
    private function forget_spent_tokens() {
        global $wpdb;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s AND CAST( option_value AS UNSIGNED ) < %d",
                $wpdb->esc_like( self::SPENT_PREFIX ) . '%',
                time() - MagicLoginToken::DEFAULT_SKEW
            )
        );
    }

    /**
     * Turn away a request, recording why. Never returns.
     *
     * @param string   $reason  Machine-readable reason, for logs and listeners.
     * @param int|null $site_id FlyWP site id, when the token got far enough to name one.
     *
     * @return void
     */
    private function refuse( $reason, $site_id = null ) {
        /**
         * Fires when magic login turns a request away.
         *
         * @since 1.6.0
         *
         * @param string   $reason  Machine-readable reason the request was refused.
         * @param int|null $site_id FlyWP site id, when the token got far enough to name one.
         */
        do_action( 'flywp_magic_login_failed', $reason, $site_id );

        // Behind WP_DEBUG_LOG. Listeners on the action above get every attempt regardless.
        if ( defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
            error_log( sprintf( 'FlyWP magic login refused (%s) for site %s', $reason, $site_id === null ? 'unknown' : $site_id ) );
        }

        $this->redirect_to_home();
    }

    /**
     * Read a field from the request body.
     *
     * @param string $field Field name.
     *
     * @return string
     */
    private function post_field( $field ) {
        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        return isset( $_POST[ $field ] ) ? sanitize_text_field( wp_unslash( $_POST[ $field ] ) ) : '';
    }
}
