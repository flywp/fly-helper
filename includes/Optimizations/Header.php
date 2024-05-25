<?php

namespace FlyWP\Optimizations;

class Header extends Base {

    /**
     * Group name
     *
     * @var string
     */
    protected $group = 'header';

    /**
     * Features to remove.
     *
     * @var array
     */
    protected $features = [
        'feed_links' => 'remove_feed_links',
        'rsd_link'   => 'remove_rsd_link',
        'generator'  => 'remove_generator',
        'rest_api'   => 'remove_rest_api',
        'shortlink'  => 'remove_shortlink',
        'oembed'     => 'remove_oembed',
    ];

    /**
     * Remove feed links.
     *
     * @return void
     */
    public function remove_feed_links() {
        remove_action( 'wp_head', 'feed_links', 2 );
        remove_action( 'wp_head', 'feed_links_extra', 3 );
    }

    /**
     * Remove RSD link.
     *
     * @return void
     */
    public function remove_rsd_link() {
        remove_action( 'wp_head', 'rsd_link' );
    }

    /**
     * Remove generator.
     *
     * @return void
     */
    public function remove_generator() {
        remove_action( 'wp_head', 'wp_generator' );
    }

    /**
     * Remove REST API links.
     *
     * @return void
     */
    public function remove_rest_api() {
        remove_action( 'wp_head', 'rest_output_link_wp_head' );
        remove_action( 'template_redirect', 'rest_output_link_header', 11 );
    }

    /**
     * Remove shortlink.
     *
     * @return void
     */
    public function remove_shortlink() {
        remove_action( 'wp_head', 'wp_shortlink_wp_head', 10, 0 );
    }

    /**
     * Remove oEmbed discovery links.
     *
     * @return void
     */
    public function remove_oembed() {
        remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
    }
}
