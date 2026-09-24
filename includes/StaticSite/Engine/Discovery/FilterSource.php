<?php

namespace FlyWP\StaticSite\Engine\Discovery;

/**
 * URLs that developers add with the "flywp_static_urls" filter.
 *
 * The filter gets an empty array and the origin URL. It returns absolute URLs as strings or as DiscoveredUrl objects.
 * The filter does not get the full list, because discovery does not keep all URLs in memory.
 *
 * Example:
 *
 *     add_filter( 'flywp_static_urls', function ( $urls ) {
 *         $urls[] = home_url( '/landing/' );
 *         return $urls;
 *     } );
 *
 * @since 1.8.0
 */
class FilterSource implements UrlSource {

    /**
     * Filter name.
     */
    const FILTER = 'flywp_static_urls';

    /**
     * @var WordPressGateway
     */
    private $wp;

    /**
     * @var string|null
     */
    private $origin_url;

    /**
     * @param WordPressGateway $wp         Gateway.
     * @param string|null      $origin_url Origin URL.
     */
    public function __construct( WordPressGateway $wp, $origin_url ) {
        $this->wp         = $wp;
        $this->origin_url = $origin_url;
    }

    /**
     * {@inheritdoc}
     */
    public function urls() {
        foreach ( (array) $this->wp->apply_filters( self::FILTER, [], $this->origin_url ) as $item ) {
            if ( $item instanceof DiscoveredUrl ) {
                yield $item;
            } elseif ( is_string( $item ) && '' !== $item ) {
                yield new DiscoveredUrl( $item, 'file' );
            }
        }
    }
}
