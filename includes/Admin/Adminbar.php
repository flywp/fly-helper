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
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $logo  = '<svg viewBox="0 0 44 45" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M22 0.580017C9.85252 0.580017 0 10.4226 0 22.58C0 34.7374 9.85252 44.58 22 44.58C34.1475 44.58 44 34.7275 44 22.58C44 10.4325 34.1475 0.580017 22 0.580017ZM11.0692 27.625C10.9011 27.625 10.7329 27.625 10.5647 27.6052C11.9595 25.0234 14.6799 23.2725 17.8156 23.2725C17.9838 23.2725 18.152 23.2725 18.3201 23.2922C16.9254 25.8741 14.205 27.625 11.0692 27.625ZM32.3273 17.3867L22.5144 32.7194C21.9802 33.7086 20.4964 33.4712 20.2986 32.3633L19.3885 27.4271C19.0126 25.3894 19.8633 23.3318 21.5647 22.1448C21.7428 22.0261 21.8022 21.7886 21.6933 21.6007C21.5944 21.4127 21.3669 21.3237 21.1691 21.4029C19.2005 22.125 17.0045 21.5908 15.58 20.0476L13.2554 17.5351C12.5531 16.7734 13.0971 15.5467 14.1259 15.5467H31.3183C32.268 15.5467 32.8417 16.5953 32.3273 17.3966V17.3867Z" fill="#a7aaad"/></svg>';
        $style = 'background-position: 0 6px; color: #fff; background-repeat: no-repeat;background-size: 20px;float: left;height: 30px;width: 26px; background-image: url(data:image/svg+xml;base64,' . base64_encode( $logo ) . ');';

        $wp_admin_bar->add_node(
            [
                'id'     => 'flywp',
                'title'  => '<div id="flywp-ab-icon" class="flywp-logo svg" style="' . $style . '"></div><span class="ab-label">' . __( 'FlyWP', 'flywp' ) . '</span>',
                'href'   => admin_url( 'index.php?page=flywp' ),
                'parent' => 'top-secondary',
            ]
        );

        if ( flywp()->fastcgi->enabled() ) {
            if ( ! is_admin() && is_singular() ) {
                $page_id = get_queried_object_id();
                $wp_admin_bar->add_node(
                    [
                        'id'     => 'flywp-cache-single',
                        'title'  => __( 'Clear This Page Cache', 'flywp' ),
                        'href'   => flywp()->fastcgi->purge_cache_url() . '&page_id=' . $page_id,
                        'parent' => 'flywp',
                    ]
                );
            }

            $wp_admin_bar->add_node(
                [
                    'id'     => 'flywp-cache',
                    'title'  => __( 'Clear Page Cache', 'flywp' ),
                    'href'   => flywp()->fastcgi->purge_cache_url(),
                    'parent' => 'flywp',
                ]
            );
        }

        if ( flywp()->opcache->enabled() ) {
            $wp_admin_bar->add_node(
                [
                    'id'     => 'flywp-opcache',
                    'title'  => __( 'Clear OPcache', 'flywp' ),
                    'href'   => flywp()->opcache->purge_cache_url(),
                    'parent' => 'flywp',
                ]
            );
        }

        $wp_admin_bar->add_node(
            [
                'id'     => 'flywp-settings',
                'title'  => __( 'Settings', 'flywp' ),
                'href'   => admin_url( 'index.php?page=flywp' ),
                'parent' => 'flywp',
            ]
        );
    }
}
