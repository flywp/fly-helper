<?php

namespace FlyWP\StaticSite\Engine\Discovery;

/**
 * Author archives of authors with published posts, with pagination.
 *
 * Grouped queries in author ID batches give the authors, their post count and the newest change.
 *
 * @since 1.8.0
 */
class AuthorSource implements UrlSource {

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
     * @param int              $batch_size Authors in one query.
     */
    public function __construct( WordPressGateway $wp, $batch_size = 500 ) {
        $this->wp         = $wp;
        $this->batch_size = max( 1, (int) $batch_size );
    }

    /**
     * {@inheritdoc}
     */
    public function urls() {
        $after_id = 0;

        do {
            $rows       = $this->wp->author_rows( $after_id, $this->batch_size );
            $full_batch = count( $rows ) === $this->batch_size;

            foreach ( $rows as $row ) {
                $after_id = $row['id'];
                $link     = $this->wp->author_link( $row['id'] );

                if ( null === $link ) {
                    continue;
                }

                yield new DiscoveredUrl( $link, 'author', 'user', $row['id'], $row['modified_gmt'] );
                yield from Pagination::urls( $this->wp, $link, $row['count'] );
            }
        } while ( $full_batch );
    }
}
