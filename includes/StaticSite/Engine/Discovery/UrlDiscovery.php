<?php

namespace FlyWP\StaticSite\Engine\Discovery;

/**
 * Collects the start URLs of a full build from all sources, and the URLs that must never be published.
 *
 * - Multisite is out of scope. The engine builds the site of the current home URL.
 *
 * @since 1.8.0
 */
class UrlDiscovery {

    /**
     * Rows in one query.
     */
    const BATCH_SIZE = 500;

    /**
     * @var WordPressGateway
     */
    private $wp;

    /**
     * @var string|null
     */
    private $origin_url;

    /**
     * @var UrlSource[]
     */
    private $sources;

    /**
     * @param WordPressGateway $wp         Gateway.
     * @param string|null      $origin_url Origin URL that the engine fetches. Null to use the home URL.
     * @param UrlSource[]      $sources    Sources, in order.
     */
    public function __construct( WordPressGateway $wp, $origin_url, array $sources ) {
        $this->wp         = $wp;
        $this->origin_url = $origin_url;
        $this->sources    = $sources;
    }

    /**
     * @param WordPressGateway $wp         Gateway.
     * @param string|null      $origin_url Origin URL.
     *
     * @return UrlSource[]
     */
    public static function default_sources( WordPressGateway $wp, $origin_url ) {
        return [
            new HomeSource( $wp ),
            new PostTypeSource( $wp ),
            new TaxonomySource( $wp ),
            new AuthorSource( $wp ),
            new DateArchiveSource( $wp ),
            new FeedSource( $wp ),
            new SitemapSource( $wp ),
            new SiteFileSource( $wp ),
            new FilterSource( $wp, $origin_url ),
        ];
    }

    /**
     * URLs on the origin, unique by URL. URLs on other hosts are removed.
     *
     * @return \Generator<DiscoveredUrl>
     */
    public function urls() {
        $origin = new OriginUrl( $this->wp->home_url(), $this->origin_url );
        $seen   = [];

        foreach ( $this->sources as $source ) {
            foreach ( $source->urls() as $item ) {
                $url = $origin->rebase( $item->url );

                if ( null === $url ) {
                    continue;
                }

                $key = md5( $url, true );

                if ( isset( $seen[ $key ] ) ) {
                    continue;
                }

                $seen[ $key ] = true;
                $item->url    = $url;

                yield $item;
            }
        }
    }

    /**
     * URLs that must never be published, on the origin: password-protected published posts, and posts and terms
     * that Yoast SEO or Rank Math marks as noindex. Queries run in batches.
     *
     * @return \Generator<string>
     */
    public function excluded_urls() {
        $origin = new OriginUrl( $this->wp->home_url(), $this->origin_url );

        foreach ( $this->excluded_links() as $link ) {
            $url = $origin->rebase( $link );

            if ( null !== $url ) {
                yield $url;
            }
        }
    }

    /**
     * @return \Generator<string|null>
     */
    private function excluded_links() {
        $post_types = $this->wp->public_post_types();
        $after_id   = 0;

        do {
            $rows       = $this->wp->post_rows( $post_types, $after_id, self::BATCH_SIZE );
            $full_batch = count( $rows ) === self::BATCH_SIZE;
            $noindex    = RobotsMeta::noindex_post_ids( $this->wp->seo_robots_meta( array_column( $rows, 'id' ) ) );

            foreach ( $rows as $row ) {
                $after_id = $row['id'];

                if ( PostTypeSource::is_excluded( $row, $noindex ) ) {
                    yield $this->wp->permalink( $row['id'] );
                }
            }
        } while ( $full_batch );

        foreach ( RobotsMeta::yoast_noindex_terms( $this->wp->seo_term_meta_option() ) as $term ) {
            yield $this->wp->term_link( $term['id'], $term['taxonomy'] );
        }

        $after_id = 0;

        do {
            $rows       = $this->wp->seo_term_robots_meta( $after_id, self::BATCH_SIZE );
            $full_batch = count( $rows ) === self::BATCH_SIZE;

            foreach ( $rows as $row ) {
                $after_id = (int) $row['term_id'];

                if ( RobotsMeta::is_rank_math_noindex( $row['meta_value'] ) ) {
                    yield $this->wp->term_link( $row['term_id'], $row['taxonomy'] );
                }
            }
        } while ( $full_batch );
    }
}
