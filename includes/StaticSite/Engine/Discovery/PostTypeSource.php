<?php

namespace FlyWP\StaticSite\Engine\Discovery;

/**
 * Published posts of all public post types, and the post type archives with pagination.
 *
 * Generator with ID batches behind the gateway. Skips password and noindex posts.
 *
 * @since 1.8.0
 */
class PostTypeSource implements UrlSource {

    /**
     * @var WordPressGateway
     */
    private $wp;

    /**
     * @var int
     */
    private $batch_size;

    /**
     * @param WordPressGateway $wp         Gateway.
     * @param int              $batch_size Posts in one query.
     */
    public function __construct( WordPressGateway $wp, $batch_size = 500 ) {
        $this->wp         = $wp;
        $this->batch_size = max( 1, (int) $batch_size );
    }

    /**
     * {@inheritdoc}
     */
    public function urls() {
        $post_types = $this->wp->public_post_types();
        $after_id   = 0;

        do {
            $rows       = $this->wp->post_rows( $post_types, $after_id, $this->batch_size );
            $full_batch = count( $rows ) === $this->batch_size;
            $noindex    = RobotsMeta::noindex_post_ids( $this->wp->seo_robots_meta( array_column( $rows, 'id' ) ) );

            foreach ( $rows as $row ) {
                $after_id = $row['id'];

                if ( self::is_excluded( $row, $noindex ) ) {
                    continue;
                }

                $link = $this->wp->permalink( $row['id'] );

                if ( null !== $link ) {
                    yield new DiscoveredUrl( $link, self::kind( $row['post_type'] ), 'post', $row['id'], $row['modified_gmt'] );
                }
            }
        } while ( $full_batch );

        foreach ( $post_types as $post_type ) {
            $archive = 'post' === $post_type ? null : $this->wp->post_type_archive_link( $post_type );

            if ( null === $archive ) {
                continue;
            }

            yield new DiscoveredUrl( $archive, 'archive', null, null, $this->wp->newest_modified_gmt( [ $post_type ] ) );
            yield from Pagination::urls( $this->wp, $archive, $this->wp->published_count( $post_type ) );
        }
    }

    /**
     * A password-protected or noindex post must not be published.
     *
     * @param array{id:int, password:string} $row     Post row.
     * @param array<int, bool>               $noindex Noindex post IDs.
     *
     * @return bool
     */
    public static function is_excluded( array $row, array $noindex ) {
        return '' !== $row['password'] || isset( $noindex[ $row['id'] ] );
    }

    /**
     * @param string $post_type Post type.
     *
     * @return string post, page or cpt.
     */
    public static function kind( $post_type ) {
        return in_array( $post_type, [ 'post', 'page' ], true ) ? $post_type : 'cpt';
    }
}
