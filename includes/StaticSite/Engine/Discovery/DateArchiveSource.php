<?php

namespace FlyWP\StaticSite\Engine\Discovery;

/**
 * Year and month archives of posts. Day archives are not included, because there can be many.
 *
 * DISTINCT month queries in batches.
 *
 * @since 1.8.0
 */
class DateArchiveSource implements UrlSource {

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
     * @param int              $batch_size Months in one query.
     */
    public function __construct( WordPressGateway $wp, $batch_size = 500 ) {
        $this->wp         = $wp;
        $this->batch_size = max( 1, (int) $batch_size );
    }

    /**
     * {@inheritdoc}
     */
    public function urls() {
        $before_key = 0;
        $last_year  = null;

        do {
            $rows       = $this->wp->month_rows( $before_key, $this->batch_size );
            $full_batch = count( $rows ) === $this->batch_size;

            foreach ( $rows as $row ) {
                $before_key = $row['year'] * 100 + $row['month'];

                if ( $last_year !== $row['year'] ) {
                    $last_year = $row['year'];

                    yield new DiscoveredUrl( $this->wp->year_link( $row['year'] ), 'archive' );
                }

                yield new DiscoveredUrl( $this->wp->month_link( $row['year'], $row['month'] ), 'archive' );
            }
        } while ( $full_batch );
    }
}
