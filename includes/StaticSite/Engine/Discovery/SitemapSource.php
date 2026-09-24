<?php

namespace FlyWP\StaticSite\Engine\Discovery;

/**
 * Sitemaps: the WordPress core sitemap with its sub-sitemaps, or the index of an active SEO plugin.
 *
 * No HTTP probes. The gateway gives only sitemaps that are on.
 * The crawler follows the <loc> links in the sitemap files.
 *
 * @since 1.8.0
 */
class SitemapSource implements UrlSource {

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
        foreach ( $this->wp->sitemap_urls() as $url ) {
            $stylesheet = '.xsl' === substr( $url, -4 ) || false !== strpos( $url, 'sitemap-stylesheet=' );

            yield new DiscoveredUrl( $url, $stylesheet ? 'asset' : 'sitemap' );
        }
    }
}
