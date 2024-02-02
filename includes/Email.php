<?php

namespace FlyWP;

class Email {

    /**
     * Class constructor.
     */
    public function __construct() {
        add_filter( 'wp_mail_from', [ $this, 'mail_from' ], PHP_INT_MAX );
        add_filter( 'wp_mail_from_name', [ $this, 'mail_from_name' ], PHP_INT_MAX );
    }

    /**
     * Set the from email.
     *
     * @param string $email
     *
     * @return string
     */
    public function mail_from( $email ) {
        $settings = $this->settings();

        if ( ! empty( $settings['from_email'] ) ) {
            return $settings['from_email'];
        }

        return $email;
    }

    /**
     * Set the from name.
     *
     * @param string $name
     *
     * @return string
     */
    public function mail_from_name( $name ) {
        $settings = $this->settings();

        if ( ! empty( $settings['from_name'] ) ) {
            return $settings['from_name'];
        }

        return $name;
    }

    /**
     * Get all email settings.
     *
     * @return array
     */
    public function settings() {
        $default = [
            'from_name'  => '',
            'from_email' => '',
        ];

        return get_option( 'flywp_email_settings', $default );
    }
}
