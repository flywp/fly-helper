<?php

namespace FlyWP\StaticSite\Engine\Discovery;

/**
 * The home page and the pagination of the posts list.
 *
 * Generator behind the gateway. The posts page gets the pagination, a static front page does not.
 *
 * @since 1.8.0
 */
class HomeSource implements UrlSource {

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
        $home     = $this->wp->home_url();
        $blog_url = self::blog_url( $this->wp );

        yield new DiscoveredUrl( $home, 'home', null, null, $home === $blog_url ? $this->wp->newest_modified_gmt( [ 'post' ] ) : null );

        if ( null !== $blog_url ) {
            yield from Pagination::urls( $this->wp, $blog_url, $this->wp->published_count( 'post' ) );
        }
    }

    /**
     * URL of the list of posts: the home page, or the "posts page" when the front page is static.
     *
     * @param WordPressGateway $wp Gateway.
     *
     * @return string|null
     */
    public static function blog_url( WordPressGateway $wp ) {
        if ( 'page' !== $wp->option( 'show_on_front' ) || (int) $wp->option( 'page_on_front' ) < 1 ) {
            return $wp->home_url();
        }

        $posts_page = (int) $wp->option( 'page_for_posts' );

        return $posts_page > 0 ? $wp->permalink( $posts_page ) : null;
    }
}
