<?php

namespace FlyWP\StaticSite\Engine\Discovery;

/**
 * Pagination URLs of a list page.
 *
 * Pages come from the item count and posts_per_page. The URL format comes from the gateway (not a fixed "/page/N/").
 *
 * @since 1.8.0
 */
class Pagination {

    /**
     * @param int $item_count Number of items in the list.
     * @param int $per_page   Items on one page.
     *
     * @return int Number of pages, 1 or more. posts_per_page 0 or less (for example -1) shows all items on one page.
     */
    public static function page_count( $item_count, $per_page ) {
        if ( (int) $per_page <= 0 ) {
            return 1;
        }

        return max( 1, (int) ceil( (int) $item_count / (int) $per_page ) );
    }

    /**
     * URLs of page 2 and later pages.
     *
     * @param WordPressGateway $wp         Gateway.
     * @param string           $base_url   Page 1 URL.
     * @param int              $item_count Number of items in the list.
     *
     * @return \Generator<DiscoveredUrl>
     */
    public static function urls( WordPressGateway $wp, $base_url, $item_count ) {
        $last = self::page_count( $item_count, $wp->option( 'posts_per_page' ) );

        for ( $page = 2; $page <= $last; $page++ ) {
            yield new DiscoveredUrl( $wp->paged_url( $base_url, $page ), 'pagination' );
        }
    }
}
