<?php

namespace FlyWP\StaticSite\Engine\Discovery;

/**
 * The main feed and the comments feed. The crawler finds other feeds from the page links.
 *
 * No search feed.
 *
 * @since 1.8.0
 */
class FeedSource implements UrlSource {

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
        yield new DiscoveredUrl( $this->wp->feed_link(), 'feed', null, null, $this->wp->newest_modified_gmt( [ 'post' ] ) );
        yield new DiscoveredUrl( $this->wp->feed_link( 'comments_' ), 'feed' );
    }
}
