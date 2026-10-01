<?php

namespace FlyWP\StaticSite\Engine\Discovery;

/**
 * Files that pages do not always link: robots.txt, favicon.ico, the site icon and the theme style.css.
 *
 * - WordPress serves robots.txt and favicon.ico through rewrite rules under the home path, also in a subdirectory install.
 * - Multisite is out of scope. The engine builds one site: the site of the current home URL.
 * - No directory scan. Only style.css of the theme and the parent theme.
 *
 * @since 1.8.0
 */
class SiteFileSource implements UrlSource {

    /**
     * @var WordPressGateway
     */
    private $wp;

    /**
     * @param WordPressGateway $wp Gateway.
     */
    public function __construct( WordPressGateway $wp ) {
        $this->wp = $wp;
    }

    /**
     * {@inheritdoc}
     */
    public function urls() {
        $home = $this->wp->home_url();

        if ( '' !== (string) $this->wp->option( 'permalink_structure' ) ) {
            yield new DiscoveredUrl( $home . 'robots.txt', 'file' );
            yield new DiscoveredUrl( $home . 'favicon.ico', 'file' );
        }

        foreach ( array_merge( $this->wp->site_icon_urls(), $this->wp->theme_stylesheet_urls() ) as $url ) {
            yield new DiscoveredUrl( $url, 'asset' );
        }
    }
}
