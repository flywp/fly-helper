<?php

namespace FlyWP\Admin;

use FlyWP\Admin;

class Email {

    /**
     * Class constructor.
     */
    public function __construct() {
        add_action( 'load-' . Admin::SCREEN_NAME, [ $this, 'save_settings' ] );
        add_action( 'load-' . Admin::SCREEN_NAME, [ $this, 'send_test_mail' ] );
    }

    /**
     * Save email settings.
     *
     * @return void
     */
    public function save_settings() {
        if ( ! isset( $_POST['flywp-email-nonce'] ) ) {
            return;
        }

        if ( ! wp_verify_nonce( $_POST['flywp-email-nonce'], 'flywp-email-settings' ) ) {
            return;
        }

        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $from_name  = isset( $_POST['from_name'] ) ? sanitize_text_field( $_POST['from_name'] ) : '';
        $from_email = isset( $_POST['from_email'] ) ? sanitize_email( $_POST['from_email'] ) : '';

        update_option(
            'flywp_email_settings',
            [
                'from_name'  => $from_name,
                'from_email' => $from_email,
            ],
            false
        );

        wp_safe_redirect( add_query_arg( [
            'tab'     => 'email',
            'message' => 'email-settings-saved',
        ], flywp()->admin->page_url() ) );
        exit;
    }

    /**
     * Send test email.
     *
     * @return void
     */
    public function send_test_mail() {
        if ( ! isset( $_POST['flywp-email-test-nonce'] ) ) {
            return;
        }

        if ( ! wp_verify_nonce( $_POST['flywp-email-test-nonce'], 'flywp-email-test' ) ) {
            return;
        }

        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $to      = isset( $_POST['to_email'] ) ? sanitize_email( $_POST['to_email'] ) : '';
        $is_html = isset( $_POST['html_email'] ) ? true : false;

        $subject = __( 'Test Email from FlyWP', 'flywp' );
        $headers = [];
        $message = $this->get_mail_body( $is_html );

        if ( $is_html ) {
            $headers[] = 'Content-Type: text/html; charset=UTF-8';
        }

        $sent = wp_mail( $to, $subject, $message, $headers );

        wp_safe_redirect( add_query_arg( [
            'tab'     => 'email',
            'message' => $sent ? 'test-mail-sent' : 'test-mail-failed',
        ], flywp()->admin->page_url() ) );
        exit;
    }

    /**
     * Get email body.
     *
     * @param bool $is_html
     *
     * @return string
     */
    private function get_mail_body( $is_html = false ) {
        if ( !$is_html ) {
            $message = <<<EOT
Hello,

This is a test email sent from FlyWP to verify the email functionality of your WordPress site.

If you have received this email, it means the email sending feature is working as expected.

Thank you for using FlyWP.

Best regards,
FlyWP Team
EOT;
        } else {
            $message = file_get_contents( FLYWP_PLUGIN_DIR . 'views/email-template.html' );
        }

        return $message;
    }
}
