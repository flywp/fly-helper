<?php

namespace FlyWP\StaticSite\Engine\Discovery;

/**
 * Term archives of all public taxonomies, with pagination.
 *
 * Generator with term ID batches behind the gateway.
 *
 * @since 1.8.0
 */
class TaxonomySource implements UrlSource {

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
     * @param int              $batch_size Terms in one query.
     */
    public function __construct( WordPressGateway $wp, $batch_size = 500 ) {
        $this->wp         = $wp;
        $this->batch_size = max( 1, (int) $batch_size );
    }

    /**
     * {@inheritdoc}
     */
    public function urls() {
        foreach ( $this->wp->public_taxonomies() as $taxonomy ) {
            $after_id = 0;

            do {
                $rows       = $this->wp->term_rows( $taxonomy, $after_id, $this->batch_size );
                $full_batch = count( $rows ) === $this->batch_size;

                foreach ( $rows as $row ) {
                    $after_id = $row['id'];
                    $link     = $this->wp->term_link( $row['id'], $taxonomy );

                    if ( null === $link ) {
                        continue;
                    }

                    yield new DiscoveredUrl( $link, 'term', 'term', $row['id'] );
                    yield from Pagination::urls( $this->wp, $link, $row['count'] );
                }
            } while ( $full_batch );
        }
    }
}
