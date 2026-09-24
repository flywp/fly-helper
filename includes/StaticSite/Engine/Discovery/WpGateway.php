<?php

namespace FlyWP\StaticSite\Engine\Discovery;

use WP_Rewrite;
use WP_Term;

/**
 * WordPress implementation of the gateway.
 *
 * Queries read IDs in batches and prime the object caches, so that link functions do not
 * send one query for each object.
 *
 * @since 1.8.0
 */
class WpGateway implements WordPressGateway {

    /**
     * {@inheritdoc}
     */
    public function home_url() {
        return trailingslashit( home_url( '/' ) );
    }

    /**
     * {@inheritdoc}
     */
    public function option( $name ) {
        return get_option( $name );
    }

    /**
     * {@inheritdoc}
     */
    public function apply_filters( $hook, $value, $arg = null ) {
        return apply_filters( $hook, $value, $arg );
    }

    /**
     * {@inheritdoc}
     */
    public function is_bulk_request() {
        return ( defined( 'WP_IMPORTING' ) && WP_IMPORTING ) || wp_installing();
    }

    /**
     * {@inheritdoc}
     */
    public function public_post_types() {
        return array_values( array_diff( get_post_types( [ 'public' => true ], 'names' ), [ 'attachment' ] ) );
    }

    /**
     * {@inheritdoc}
     */
    public function public_taxonomies() {
        return array_values( get_taxonomies( [ 'public' => true ], 'names' ) );
    }

    /**
     * {@inheritdoc}
     */
    public function post_rows( array $post_types, $after_id, $limit ) {
        global $wpdb;

        if ( empty( $post_types ) ) {
            return [];
        }

        $this->flush_runtime_cache();

        $placeholders = implode( ', ', array_fill( 0, count( $post_types ), '%s' ) );
        $rows         = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT ID, post_type, post_password, post_modified_gmt FROM {$wpdb->posts}
                WHERE post_status = 'publish' AND post_type IN ( {$placeholders} ) AND ID > %d
                ORDER BY ID ASC LIMIT %d",
                array_merge( array_values( $post_types ), [ (int) $after_id, (int) $limit ] )
            ),
            ARRAY_A
        );

        $rows = is_array( $rows ) ? $rows : [];

        _prime_post_caches( array_map( 'intval', array_column( $rows, 'ID' ) ), true, false );

        return array_map(
            function ( $row ) {
                return [
                    'id'           => (int) $row['ID'],
                    'post_type'    => $row['post_type'],
                    'password'     => (string) $row['post_password'],
                    'modified_gmt' => $row['post_modified_gmt'],
                ];
            },
            $rows
        );
    }

    /**
     * {@inheritdoc}
     */
    public function seo_robots_meta( array $post_ids ) {
        global $wpdb;

        $keys = [];

        if ( defined( 'WPSEO_VERSION' ) ) {
            $keys[] = RobotsMeta::YOAST_NOINDEX;
        }

        if ( class_exists( 'RankMath' ) ) {
            $keys[] = RobotsMeta::RANK_MATH_ROBOTS;
        }

        if ( empty( $keys ) || empty( $post_ids ) ) {
            return [];
        }

        $ids          = implode( ', ', array_map( 'intval', $post_ids ) );
        $placeholders = implode( ', ', array_fill( 0, count( $keys ), '%s' ) );
        $rows         = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT post_id, meta_key, meta_value FROM {$wpdb->postmeta} WHERE post_id IN ( {$ids} ) AND meta_key IN ( {$placeholders} )",
                $keys
            ),
            ARRAY_A
        );

        return is_array( $rows ) ? $rows : [];
    }

    /**
     * {@inheritdoc}
     */
    public function seo_term_meta_option() {
        if ( ! defined( 'WPSEO_VERSION' ) ) {
            return [];
        }

        $option = get_option( 'wpseo_taxonomy_meta' );

        return is_array( $option ) ? $option : [];
    }

    /**
     * {@inheritdoc}
     */
    public function seo_term_robots_meta( $after_id, $limit ) {
        global $wpdb;

        if ( ! class_exists( 'RankMath' ) ) {
            return [];
        }

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT tm.term_id, tt.taxonomy, tm.meta_value FROM {$wpdb->termmeta} tm
                INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_id = tm.term_id
                WHERE tm.meta_key = %s AND tm.term_id > %d
                ORDER BY tm.term_id ASC LIMIT %d",
                RobotsMeta::RANK_MATH_ROBOTS,
                (int) $after_id,
                (int) $limit
            ),
            ARRAY_A
        );

        return is_array( $rows ) ? $rows : [];
    }

    /**
     * {@inheritdoc}
     */
    public function post( $post_id ) {
        $post = get_post( (int) $post_id );

        if ( ! $post ) {
            return null;
        }

        return [
            'id'           => (int) $post->ID,
            'post_type'    => $post->post_type,
            'status'       => $post->post_status,
            'password'     => (string) $post->post_password,
            'author'       => (int) $post->post_author,
            'date'         => $post->post_date,
            'modified_gmt' => $post->post_modified_gmt,
            'pages'        => substr_count( (string) $post->post_content, '<!--nextpage-->' ) + 1,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function permalink( $post_id ) {
        return $this->link( get_permalink( (int) $post_id ) );
    }

    /**
     * {@inheritdoc}
     */
    public function object_permalink( $post ) {
        return $this->link( get_permalink( $post ) );
    }

    /**
     * {@inheritdoc}
     */
    public function post_page_url( $permalink, $page ) {
        return $this->pretty_url( $permalink, (string) (int) $page, 'single_paged', 'page', $page );
    }

    /**
     * {@inheritdoc}
     */
    public function post_has_children( $post_id, $post_type ) {
        global $wpdb;

        if ( ! is_post_type_hierarchical( $post_type ) ) {
            return false;
        }

        return (bool) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT ID FROM {$wpdb->posts} WHERE post_parent = %d AND post_type = %s AND post_status = 'publish' LIMIT 1",
                (int) $post_id,
                $post_type
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function published_count( $post_type ) {
        $counts = wp_count_posts( $post_type );

        return (int) ( $counts->publish ?? 0 );
    }

    /**
     * {@inheritdoc}
     */
    public function newest_modified_gmt( array $post_types ) {
        global $wpdb;

        if ( empty( $post_types ) ) {
            return null;
        }

        $placeholders = implode( ', ', array_fill( 0, count( $post_types ), '%s' ) );
        $value        = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT MAX(post_modified_gmt) FROM {$wpdb->posts} WHERE post_status = 'publish' AND post_type IN ( {$placeholders} )",
                array_values( $post_types )
            )
        );

        return $value ? $value : null;
    }

    /**
     * {@inheritdoc}
     */
    public function post_type_archive_link( $post_type ) {
        return $this->link( get_post_type_archive_link( $post_type ) );
    }

    /**
     * {@inheritdoc}
     */
    public function term_rows( $taxonomy, $after_id, $limit ) {
        global $wpdb;

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT tt.term_id, tt.taxonomy, tt.count FROM {$wpdb->term_taxonomy} tt
                WHERE tt.taxonomy = %s AND tt.term_id > %d
                AND ( tt.count > 0 OR EXISTS ( SELECT 1 FROM {$wpdb->term_taxonomy} child WHERE child.parent = tt.term_id AND child.taxonomy = tt.taxonomy ) )
                ORDER BY tt.term_id ASC LIMIT %d",
                $taxonomy,
                (int) $after_id,
                (int) $limit
            ),
            ARRAY_A
        );

        $rows = is_array( $rows ) ? $rows : [];

        _prime_term_caches( array_map( 'intval', array_column( $rows, 'term_id' ) ), false );

        return array_map(
            function ( $row ) {
                return [
                    'id'       => (int) $row['term_id'],
                    'taxonomy' => $row['taxonomy'],
                    'count'    => (int) $row['count'],
                ];
            },
            $rows
        );
    }

    /**
     * {@inheritdoc}
     */
    public function term( $term_id ) {
        $term = get_term( (int) $term_id );

        return $term instanceof WP_Term ? $this->term_row( $term ) : null;
    }

    /**
     * {@inheritdoc}
     */
    public function term_by_tt_id( $tt_id, $taxonomy ) {
        $term = get_term_by( 'term_taxonomy_id', (int) $tt_id, $taxonomy );

        return $term instanceof WP_Term ? $this->term_row( $term ) : null;
    }

    /**
     * {@inheritdoc}
     */
    public function post_terms( $post_id ) {
        $terms = wp_get_object_terms( (int) $post_id, $this->public_taxonomies() );

        return is_array( $terms ) ? array_map( [ $this, 'term_row' ], $terms ) : [];
    }

    /**
     * {@inheritdoc}
     */
    public function term_link( $term_id, $taxonomy ) {
        return $this->link( get_term_link( (int) $term_id, $taxonomy ) );
    }

    /**
     * {@inheritdoc}
     */
    public function term_has_children( $term_id, $taxonomy ) {
        global $wpdb;

        if ( ! is_taxonomy_hierarchical( $taxonomy ) ) {
            return false;
        }

        return (bool) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT term_id FROM {$wpdb->term_taxonomy} WHERE parent = %d AND taxonomy = %s LIMIT 1",
                (int) $term_id,
                $taxonomy
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function object_in_taxonomy( $post_type, $taxonomy ) {
        return is_object_in_taxonomy( $post_type, $taxonomy );
    }

    /**
     * {@inheritdoc}
     */
    public function author_rows( $after_id, $limit ) {
        global $wpdb;

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT post_author, COUNT(*) AS total, MAX(post_modified_gmt) AS modified_gmt FROM {$wpdb->posts}
                WHERE post_type = 'post' AND post_status = 'publish' AND post_author > %d
                GROUP BY post_author ORDER BY post_author ASC LIMIT %d",
                (int) $after_id,
                (int) $limit
            ),
            ARRAY_A
        );

        $rows = is_array( $rows ) ? $rows : [];

        cache_users( array_map( 'intval', array_column( $rows, 'post_author' ) ) );

        return array_map(
            function ( $row ) {
                return [
                    'id'           => (int) $row['post_author'],
                    'count'        => (int) $row['total'],
                    'modified_gmt' => $row['modified_gmt'],
                ];
            },
            $rows
        );
    }

    /**
     * {@inheritdoc}
     */
    public function author_post_count( $user_id ) {
        return (int) count_user_posts( (int) $user_id, 'post', true );
    }

    /**
     * {@inheritdoc}
     */
    public function author_link( $user_id ) {
        if ( ! get_userdata( (int) $user_id ) ) {
            return null;
        }

        return $this->link( get_author_posts_url( (int) $user_id ) );
    }

    /**
     * {@inheritdoc}
     */
    public function month_rows( $before_key, $limit ) {
        global $wpdb;

        $before = $before_key > 0 ? sprintf( '%04d-%02d-01 00:00:00', intdiv( (int) $before_key, 100 ), (int) $before_key % 100 ) : '9999-12-31 23:59:59';
        $rows   = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT DISTINCT YEAR(post_date) AS year, MONTH(post_date) AS month FROM {$wpdb->posts}
                WHERE post_type = 'post' AND post_status = 'publish' AND post_date < %s
                ORDER BY year DESC, month DESC LIMIT %d",
                $before,
                (int) $limit
            ),
            ARRAY_A
        );

        return array_map(
            function ( $row ) {
                return [
                    'year'  => (int) $row['year'],
                    'month' => (int) $row['month'],
                ];
            },
            is_array( $rows ) ? $rows : []
        );
    }

    /**
     * {@inheritdoc}
     */
    public function date_post_count( $year, $month ) {
        global $wpdb;

        $year  = (int) $year;
        $month = (int) $month;
        $start = sprintf( '%04d-%02d-01 00:00:00', $year, $month > 0 ? $month : 1 );
        $end   = $month > 0
            ? sprintf( '%04d-%02d-01 00:00:00', 12 === $month ? $year + 1 : $year, 12 === $month ? 1 : $month + 1 )
            : sprintf( '%04d-01-01 00:00:00', $year + 1 );

        return (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'post' AND post_status = 'publish' AND post_date >= %s AND post_date < %s",
                $start,
                $end
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function year_link( $year ) {
        return get_year_link( (int) $year );
    }

    /**
     * {@inheritdoc}
     */
    public function month_link( $year, $month ) {
        return get_month_link( (int) $year, (int) $month );
    }

    /**
     * {@inheritdoc}
     */
    public function feed_link( $feed = '' ) {
        return get_feed_link( $feed );
    }

    /**
     * {@inheritdoc}
     */
    public function paged_url( $base_url, $page ) {
        global $wp_rewrite;

        $pagination_base = $wp_rewrite instanceof WP_Rewrite ? $wp_rewrite->pagination_base : 'page';

        return $this->pretty_url( $base_url, $pagination_base . '/' . (int) $page, 'paged', 'paged', $page );
    }

    /**
     * {@inheritdoc}
     */
    public function comment( $comment_id ) {
        $comment = get_comment( $comment_id );

        if ( ! $comment ) {
            return null;
        }

        return [
            'post_id'  => (int) $comment->comment_post_ID,
            'approved' => (string) $comment->comment_approved,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function approved_comment_count( $post_id, $top_level_only ) {
        $args = [
            'post_id' => (int) $post_id,
            'status'  => 'approve',
            'count'   => true,
        ];

        if ( $top_level_only ) {
            $args['parent'] = 0;
        }

        return (int) get_comments( $args );
    }

    /**
     * {@inheritdoc}
     */
    public function comment_page_url( $permalink, $page ) {
        global $wp_rewrite;

        $base = $wp_rewrite instanceof WP_Rewrite ? $wp_rewrite->comments_pagination_base : 'comment-page';

        return $this->pretty_url( $permalink, $base . '-' . (int) $page, 'commentpaged', 'cpage', $page );
    }

    /**
     * {@inheritdoc}
     */
    public function attachment_file_url( $attachment_id ) {
        return $this->link( wp_get_attachment_url( (int) $attachment_id ) );
    }

    /**
     * {@inheritdoc}
     */
    public function attachment_metadata( $attachment_id ) {
        $metadata = wp_get_attachment_metadata( (int) $attachment_id, true );

        return is_array( $metadata ) ? $metadata : [];
    }

    /**
     * {@inheritdoc}
     */
    public function sitemap_urls() {
        $urls = [];

        if ( function_exists( 'wp_sitemaps_get_server' ) ) {
            $server = wp_sitemaps_get_server();

            if ( $server->sitemaps_enabled() ) {
                $urls[] = $server->index->get_index_url();
                $urls[] = $server->renderer->get_sitemap_index_stylesheet_url();
                $urls[] = $server->renderer->get_sitemap_stylesheet_url();

                foreach ( $server->registry->get_providers() as $provider ) {
                    foreach ( $provider->get_sitemap_entries() as $entry ) {
                        $urls[] = $entry['loc'] ?? null;
                    }
                }
            }
        }

        $yoast_sitemap     = defined( 'WPSEO_VERSION' ) && class_exists( 'WPSEO_Options' ) && \WPSEO_Options::get( 'enable_xml_sitemap' );
        $rank_math_sitemap = class_exists( '\RankMath\Helper' ) && \RankMath\Helper::is_module_active( 'sitemap' );

        if ( $yoast_sitemap || $rank_math_sitemap ) {
            $urls[] = home_url( '/sitemap_index.xml' );
        }

        return $this->links( $urls );
    }

    /**
     * {@inheritdoc}
     */
    public function site_icon_urls() {
        if ( ! has_site_icon() ) {
            return [];
        }

        return $this->links( [ get_site_icon_url( 32 ), get_site_icon_url( 180 ), get_site_icon_url( 192 ), get_site_icon_url( 270 ) ] );
    }

    /**
     * {@inheritdoc}
     */
    public function theme_stylesheet_urls() {
        $urls = [ get_stylesheet_uri() ];

        if ( get_template() !== get_stylesheet() ) {
            $urls[] = trailingslashit( get_template_directory_uri() ) . 'style.css';
        }

        return $this->links( $urls );
    }

    /**
     * Add a page part to a URL: "/base/<suffix>/" with pretty permalinks, else "?<query_var>=<page>".
     *
     * @param string $base_url  URL of page 1.
     * @param string $suffix    Path part for pretty permalinks.
     * @param string $type      Type for user_trailingslashit().
     * @param string $query_var Query variable for plain permalinks.
     * @param int    $page      Page number.
     *
     * @return string
     */
    private function pretty_url( $base_url, $suffix, $type, $query_var, $page ) {
        global $wp_rewrite;

        if ( ! $wp_rewrite instanceof WP_Rewrite || ! $wp_rewrite->using_permalinks() || false !== strpos( $base_url, '?' ) ) {
            return add_query_arg( $query_var, (int) $page, $base_url );
        }

        return user_trailingslashit( trailingslashit( $base_url ) . $suffix, $type );
    }

    /**
     * @param WP_Term $term Term.
     *
     * @return array{id:int, taxonomy:string, count:int}
     */
    private function term_row( WP_Term $term ) {
        return [
            'id'       => (int) $term->term_id,
            'taxonomy' => $term->taxonomy,
            'count'    => (int) $term->count,
        ];
    }

    /**
     * @param mixed $value Value from a WordPress link function.
     *
     * @return string|null
     */
    private function link( $value ) {
        return is_string( $value ) && '' !== $value ? $value : null;
    }

    /**
     * @param array $values Values from WordPress link functions.
     *
     * @return string[] Unique, not empty links.
     */
    private function links( array $values ) {
        return array_values( array_unique( array_filter( array_map( [ $this, 'link' ], $values ) ) ) );
    }

    /**
     * Remove objects of old batches from the runtime cache. Thus memory does not grow on big sites.
     *
     * @return void
     */
    private function flush_runtime_cache() {
        if ( function_exists( 'wp_cache_supports' ) && wp_cache_supports( 'flush_runtime' ) ) {
            wp_cache_flush_runtime();
        }
    }
}
