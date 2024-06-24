<?php

namespace FlyWP\Optimizations;

class General extends Base {

    /**
     * Group name
     *
     * @var string
     */
    protected $group = 'general';

    /**
     * Features to remove.
     *
     * @var array
     */
    protected $features = [
        'emoji'           => 'remove_emoji',
        'oembed'          => 'remove_embed',
        'self_ping'       => 'disable_self_ping',
        'comments'        => 'disable_comments',
        'jquery_migrate'  => 'remove_jquery_migrate',
        'clean_nav_menu'  => 'clean_nav_menu',
        'rss_feed'        => 'remove_rss_feed',
        'xmlrpc'          => 'remove_xmlrpc',
    ];

    /**
     * Remove emoji scripts.
     *
     * @return void
     */
    public function remove_emoji() {
        remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
        remove_action( 'wp_print_styles', 'print_emoji_styles' );
        remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
        remove_action( 'admin_print_styles', 'print_emoji_styles' );
        remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
        remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
        remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );

        add_filter( 'emoji_svg_url', '__return_false' );

        // Remove from TinyMCE
        add_filter( 'tiny_mce_plugins', function ( $plugins ) {
            return is_array( $plugins ) ? array_diff( $plugins, [ 'wpemoji' ] ) : [];
        } );
    }

    /**
     * Remove embed scripts.
     *
     * @return void
     */
    public function remove_embed() {
        remove_action( 'wp_head', 'wp_oembed_add_host_js' );
        remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
        remove_action( 'rest_api_init', 'wp_oembed_register_route' );

        add_filter( 'embed_oembed_discover', '__return_false' );
        remove_filter( 'oembed_dataparse', 'wp_filter_oembed_result' );
    }

    /**
     * Disable self ping.
     *
     * @return void
     */
    public function disable_self_ping() {
        add_action( 'pre_ping', function ( &$links ) {
            $home = get_option( 'home' );

            foreach ( $links as $l => $link ) {
                if ( 0 === strpos( $link, $home ) ) {
                    unset( $links[ $l ] );
                }
            }
        } );
    }

    /**
     * Disable comments.
     *
     * @return void
     */
    public function disable_comments() {
        add_filter( 'comments_open', '__return_false', 20, 2 );
        add_filter( 'pings_open', '__return_false', 20, 2 );

        // Remove comments feed
        add_filter( 'feed_links_show_comments_feed', '__return_false' );
        add_filter( 'post_comments_feed_link', '__return_false' );
        add_filter( 'comments_link_feed', '__return_false' );
    }

    /**
     * Remove jQuery Migrate.
     *
     * @return void
     */
    public function remove_jquery_migrate() {
        add_action( 'wp_default_scripts', function ( $scripts ) {
            if ( isset( $scripts->registered['jquery'] ) ) {
                $script = $scripts->registered['jquery'];

                if ( $script->deps ) {
                    $script->deps = array_diff( $script->deps, [ 'jquery-migrate' ] );
                }
            }
        } );
    }

    /**
     * Clean navigation menu.
     *
     * @return void
     */
    public function clean_nav_menu() {
        add_filter( 'nav_menu_css_class', function ( $classes ) {
            return array_intersect( $classes, [
                'current-menu-item',
                'menu-item-has-children',
                'menu-item',
                'current-menu-ancestor',
            ] );
        } );

        add_filter( 'nav_menu_item_id', '__return_null' );
    }

    /**
     * Remove RSS feed links.
     *
     * @return void
     */
    public function remove_rss_feed() {
        add_action( 'template_redirect', function () {
            if ( is_feed() ) {
                wp_redirect( home_url(), 301 );
                exit;
            }
        }, 1 );
    }

    /**
     * Remove XML-RPC.
     *
     * @return void
     */
    public function remove_xmlrpc() {
        add_filter( 'xmlrpc_enabled', '__return_false' );
    }
}
