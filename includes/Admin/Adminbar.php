<?php

namespace FlyWP\Admin;

use WP_Admin_Bar;

class Adminbar {

    /**
     * Class constructor.
     */
    public function __construct() {
        add_action( 'admin_bar_menu', [ $this, 'admin_bar_menu' ] );
    }

    /**
     * Admin bar menu.
     *
     * @param WP_Admin_Bar $wp_admin_bar
     */
    public function admin_bar_menu( $wp_admin_bar ) {
        $wp_admin_bar->add_node(
            [
                'id'     => 'flywp',
                'title'  => __( 'FlyWP', 'flywp' ),
                'href'   => flywp()->admin->page_url(),
                'parent' => 'top-secondary',
            ]
        );

        if ( flywp()->fastcgi->enabled() ) {
            $wp_admin_bar->add_node(
                [
                    'id'     => 'flywp-cache',
                    'title'  => __( 'Clear Page Cache', 'flywp' ),
                    'href'   => flywp()->admin->fastcgi->purge_cache_url(),
                    'parent' => 'flywp',
                ]
            );
        }
    }
}
