<?php

namespace FlyWP\Optimizations;

class Admin extends Base {

    /**
     * Group name
     *
     * @var string
     */
    protected $group = 'admin';

    /**
     * Features to remove.
     *
     * @var array
     */
    protected $features = [
        'wp_logo'           => 'remove_wp_logo',
        'login_logo'        => 'show_login_logo',
        'dashboard_widgets' => 'remove_dashboard_widgets',
    ];

    /**
     * Remove WordPress logo from admin bar.
     *
     * @return void
     */
    public function remove_wp_logo() {
        add_action( 'admin_bar_menu', function ( $wp_admin_bar ) {
            $wp_admin_bar->remove_node( 'wp-logo' );
        }, 20 );
    }

    /**
     * Show the site icon.
     *
     * @return void
     */
    public function show_login_logo() {
        $site_logo = get_site_icon_url();

        add_action( 'login_headerurl', function () {
            return home_url();
        } );

        add_action( 'login_headertext', function () {
            return get_bloginfo( 'name' );
        } );

        add_action( 'login_head', function () use ( $site_logo ) {
            if ( $site_logo ) {
                echo '<style type="text/css">.login h1 a { background-image: url(' . esc_url( $site_logo ) . ') !important; }</style>';
            } else {
                echo '<style type="text/css">.login h1 a { display: none; }</style>';
            }
        } );
    }

    /**
     * Remove dashboard widgets.
     *
     * @return void
     */
    public function remove_dashboard_widgets() {
        add_action( 'wp_dashboard_setup', function () {
            remove_action( 'welcome_panel', 'wp_welcome_panel' );

            remove_meta_box( 'dashboard_right_now', 'dashboard', 'normal' );
            remove_meta_box( 'dashboard_activity', 'dashboard', 'normal' );
            remove_meta_box( 'dashboard_quick_press', 'dashboard', 'side' );
            remove_meta_box( 'dashboard_primary', 'dashboard', 'side' );
            remove_meta_box( 'dashboard_recent_drafts', 'dashboard', 'normal' );
            remove_meta_box( 'dashboard_secondary', 'dashboard', 'normal' );
            remove_meta_box( 'dashboard_site_health', 'dashboard', 'normal' );
        } );
    }
}
