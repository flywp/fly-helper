<?php

namespace FlyWP;

class Optimizations {

    const OPTION_KEY = 'flywp_optimizations';

    /**
     * Class constructor
     *
     * @return void
     */
    public function __construct() {
        if ( ! $this->enabled() ) {
            return;
        }

        new Optimizations\Admin( $this );
        new Optimizations\Header( $this );
        new Optimizations\General( $this );
    }

    /**
     * Check if optimizations are enabled.
     *
     * @return bool
     */
    public function enabled() {
        return $this->get_option( 'enabled' ) === true;
    }

    /**
     * Check if a feature is enabled.
     *
     * @param string $feature feature name
     * @param string $group   group name
     *
     * @return bool
     */
    public function feature_enabled( $feature, $group ) {
        $value = $this->get_option( $group );

        return $value[ $feature ] ?? false;
    }

    /**
     * Get option.
     *
     * @param string $key     option key
     * @param mixed  $default default value
     *
     * @return mixed
     */
    public function get_option( $key, $default = null ) {
        $options = $this->get_options();

        return $options[ $key ] ?? $default;
    }

    /**
     * Get options.
     *
     * @return array
     */
    public function get_options() {
        $options = get_option( self::OPTION_KEY, [
            'enabled' => true,
            'general' => [
                'emoji'          => true,
                'oembed'         => true,
                'self_ping'      => true,
                'comments'       => false,
                'jquery_migrate' => true,
                'clean_nav_menu' => true,
                'rss_feed'       => false,
                'xmlrpc'         => true,
            ],
            'admin' => [
                'wp_logo'           => true,
                'login_logo'        => true,
                'dashboard_widgets' => true,
            ],
            'header' => [
                'generator'  => true,
                'rsd_link'   => true,
                'feed_links' => true,
                'rest_api'   => true,
                'shortlink'  => true,
                'oembed'     => true,
            ],
        ] );

        return apply_filters( 'fly_helper_optimizations_options', $options );
    }
}
