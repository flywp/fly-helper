<?php

namespace FlyWP\Admin;

use FlyWP\Admin;
use FlyWP\Optimizations as OptimizationsBase;
use WeDevs\WpUtils\HookTrait;

class Optimizations {

    use HookTrait;

    /**
     * Class constructor
     */
    public function __construct() {
        $this->add_action( 'flywp_admin_tab_content', 'tab_content' );
        $this->add_action( 'load-' . Admin::SCREEN_NAME, 'save_settings' );
    }

    /**
     * Save settings.
     *
     * @return void
     */
    public function save_settings() {
        if ( ! isset( $_POST['flywp-optimizations-nonce'] ) ) {
            return;
        }

        if ( ! wp_verify_nonce( $_POST['flywp-optimizations-nonce'], 'flywp-optimizations-settings' ) ) {
            wp_die( 'Error: Invalid nonce specified in request', 'Invalid Request', 403 );
        }

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Error: You are not allowed to perform this action.', 'Unauthorized Access', 403 );
        }

        $options = [
            'enabled' => isset( $_POST['enabled'] ) ? true : false,
        ];

        foreach ( self::options() as $group => $data ) {
            foreach ( $data['fields'] as $field => $field_data ) {
                $options[ $group ][ $field ] = isset( $_POST[ $group ][ $field ] ) ? true : false;
            }
        }

        update_option( OptimizationsBase::OPTION_KEY, $options );

        wp_safe_redirect( add_query_arg( [
            'tab'     => 'optimizations',
            'message' => 'optimizations-settings-saved',
        ], flywp()->admin->page_url() ) );
        exit;
    }

    /**
     * Get optimization options.
     *
     * @return array
     */
    public static function options() {
        $options = [
            'general' => [
                'title'       => __( 'General', 'flywp' ),
                'description' => __( 'General optimizations.', 'flywp' ),
                'fields'      => [
                    'emoji' => [
                        'label'       => __( 'Disable Emojis', 'flywp' ),
                        'description' => __( 'Remove extra JavaScript that adds support for emojis in older browsers. Native emoji\'s will still work.', 'flywp' ),
                    ],
                    'oembed' => [
                        'label'       => __( 'Disable Embeds', 'flywp' ),
                        'description' => __( 'Prevent others from embedding your site.', 'flywp' ),
                    ],
                    'self_ping' => [
                        'label'       => __( 'Disable Self Pingbacks', 'flywp' ),
                        'description' => __( 'Disable self pingbacks from your own site.', 'flywp' ),
                    ],
                    'comments' => [
                        'label'       => __( 'Disable Comments', 'flywp' ),
                        'description' => __( 'Disable comments from the site.', 'flywp' ),
                    ],
                    'jquery_migrate' => [
                        'label'       => __( 'Disable jQuery Migrate', 'flywp' ),
                        'description' => __( 'Disable jQuery Migrate from the site frontend and admin panel.', 'flywp' ),
                    ],
                    'clean_nav_menu' => [
                        'label'       => __( 'Clean Navigation Menu', 'flywp' ),
                        'description' => __( 'Remove unnecessary classes from the navigation menu.', 'flywp' ),
                    ],
                    'rss_feed' => [
                        'label'       => __( 'Disable RSS Feed', 'flywp' ),
                        'description' => __( 'Disable the RSS and Atom feeds from the site.', 'flywp' ),
                    ],
                ],
            ],
            'admin' => [
                'title'       => __( 'Admin', 'flywp' ),
                'description' => __( 'Customize the WordPress admin area.', 'flywp' ),
                'fields'      => [
                    'wp_logo' => [
                        'label'       => __( 'Remove WordPress Logo', 'flywp' ),
                        'description' => __( 'Remove the WordPress logo from the admin bar.', 'flywp' ),
                    ],
                    'login_logo' => [
                        'label'       => __( 'Show Login Logo', 'flywp' ),
                        'description' => __( 'Show the site logo on the login page.', 'flywp' ),
                    ],
                    'dashboard_widgets' => [
                        'label'       => __( 'Remove Dashboard Widgets', 'flywp' ),
                        'description' => __( 'Remove all default dashboard widgets from the WordPress admin.', 'flywp' ),
                    ],
                ],
            ],
            'header' => [
                'title'       => __( 'Site Header', 'flywp' ),
                'description' => __( 'Customize the site header.', 'flywp' ),
                'fields'      => [
                    'feed_links' => [
                        'label'       => __( 'Remove Feed Links', 'flywp' ),
                        'description' => __( 'Remove the RSS and Atom feed links from the site header.', 'flywp' ),
                    ],
                    'rsd_link' => [
                        'label'       => __( 'Remove RSD Link', 'flywp' ),
                        'description' => __( 'Remove the RSD link from the site header.', 'flywp' ),
                    ],
                    'generator' => [
                        'label'       => __( 'Remove WP Version Number', 'flywp' ),
                        'description' => __( 'Remove the WordPress version from the site header. It helps improving the site security by not exposing your current WordPress version number.', 'flywp' ),
                    ],
                    'rest_api' => [
                        'label'       => __( 'Remove REST API Links', 'flywp' ),
                        'description' => __( 'Remove the REST API links from the site header.', 'flywp' ),
                    ],
                    'shortlink' => [
                        'label'       => __( 'Remove Shortlink', 'flywp' ),
                        'description' => __( 'Remove the shortlink tag from the site header.', 'flywp' ),
                    ],
                    'oembed' => [
                        'label'       => __( 'Remove oEmbed Discovery Links', 'flywp' ),
                        'description' => __( 'Remove the oEmbed discovery links from the site header.', 'flywp' ),
                    ],
                ],
            ],
        ];

        return $options;
    }

    /**
     * Tab content.
     *
     * @param string $current_tab current tab
     *
     * @return void
     */
    public function tab_content( $current_tab ) {
        if ( 'optimizations' !== $current_tab ) {
            return;
        }

        require_once FLYWP_PLUGIN_DIR . '/views/optimizations.php';
    }
}
