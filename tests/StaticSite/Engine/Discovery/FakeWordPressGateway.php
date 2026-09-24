<?php

namespace FlyWP\Tests\StaticSite\Engine\Discovery;

use FlyWP\StaticSite\Engine\Discovery\WordPressGateway;

/**
 * In-memory WordPress for discovery and incremental tests.
 */
class FakeWordPressGateway implements WordPressGateway {

    public $home = 'https://wp.test/';

    public $options = [
        'posts_per_page'      => 10,
        'show_on_front'       => 'posts',
        'page_on_front'       => 0,
        'page_for_posts'      => 0,
        'permalink_structure' => '/%postname%/',
    ];

    public $post_types = [ 'post', 'page' ];

    public $taxonomies = [ 'category', 'post_tag' ];

    /** @var array<int, array> */
    public $posts = [];

    /** @var array<int, array{taxonomy:string, slug:string, count:int}> */
    public $terms = [];

    /** @var array<string, string> */
    public $archives = [];

    public $robots_meta = [];

    public $yoast_term_meta = [];

    public $term_robots_meta = [];

    public $sitemaps = [];

    public $site_icons = [];

    public $stylesheets = [];

    /** @var array<string, callable> */
    public $filters = [];

    /** @var array<string, bool> "post:ID" or "term:ID" => true */
    public $children = [];

    /** @var array<int, array{post_id:int, approved:string}> */
    public $comments = [];

    /** @var array<int, int> */
    public $comment_counts = [];

    /** @var array<int, array{url:string, meta:array}> */
    public $attachments = [];

    public $bulk = false;

    public $post_row_calls = 0;

    public function add_post( $id, array $data = [] ) {
        $this->posts[ $id ] = array_merge(
            [
                'post_type'    => 'post',
                'status'       => 'publish',
                'password'     => '',
                'author'       => 1,
                'date'         => '2026-09-01 10:00:00',
                'modified_gmt' => '2026-09-01 10:00:00',
                'slug'         => 'post-' . $id,
                'terms'        => [],
                'pages'        => 1,
            ],
            $data
        );
    }

    public function add_term( $id, $taxonomy, $slug, $count ) {
        $this->terms[ $id ] = [ 'taxonomy' => $taxonomy, 'slug' => $slug, 'count' => $count ];
    }

    public function home_url() {
        return $this->home;
    }

    public function option( $name ) {
        return $this->options[ $name ] ?? false;
    }

    public function apply_filters( $hook, $value, $arg = null ) {
        return isset( $this->filters[ $hook ] ) ? call_user_func( $this->filters[ $hook ], $value, $arg ) : $value;
    }

    public function is_bulk_request() {
        return $this->bulk;
    }

    public function public_post_types() {
        return $this->post_types;
    }

    public function public_taxonomies() {
        return $this->taxonomies;
    }

    public function post_rows( array $post_types, $after_id, $limit ) {
        $this->post_row_calls++;
        $rows = [];

        ksort( $this->posts );

        foreach ( $this->posts as $id => $post ) {
            if ( $id > $after_id && 'publish' === $post['status'] && in_array( $post['post_type'], $post_types, true ) ) {
                $rows[] = [ 'id' => $id, 'post_type' => $post['post_type'], 'password' => $post['password'], 'modified_gmt' => $post['modified_gmt'] ];
            }
        }

        return array_slice( $rows, 0, $limit );
    }

    public function seo_robots_meta( array $post_ids ) {
        return array_values(
            array_filter(
                $this->robots_meta,
                function ( $row ) use ( $post_ids ) {
                    return in_array( $row['post_id'], $post_ids, true );
                }
            )
        );
    }

    public function seo_term_meta_option() {
        return $this->yoast_term_meta;
    }

    public function seo_term_robots_meta( $after_id, $limit ) {
        $rows = array_filter(
            $this->term_robots_meta,
            function ( $row ) use ( $after_id ) {
                return $row['term_id'] > $after_id;
            }
        );

        return array_slice( array_values( $rows ), 0, $limit );
    }

    public function post( $post_id ) {
        if ( ! isset( $this->posts[ $post_id ] ) ) {
            return null;
        }

        $post = $this->posts[ $post_id ];

        return [
            'id'           => $post_id,
            'post_type'    => $post['post_type'],
            'status'       => $post['status'],
            'password'     => $post['password'],
            'author'       => $post['author'],
            'date'         => $post['date'],
            'modified_gmt' => $post['modified_gmt'],
            'pages'        => $post['pages'],
        ];
    }

    public function permalink( $post_id ) {
        return isset( $this->posts[ $post_id ] ) ? $this->home . $this->posts[ $post_id ]['slug'] . '/' : null;
    }

    public function object_permalink( $post ) {
        return $this->home . $post->post_name . '/';
    }

    public function post_page_url( $permalink, $page ) {
        return rtrim( $permalink, '/' ) . '/' . $page . '/';
    }

    public function post_has_children( $post_id, $post_type ) {
        return isset( $this->children[ 'post:' . $post_id ] );
    }

    public function published_count( $post_type ) {
        return count(
            array_filter(
                $this->posts,
                function ( $post ) use ( $post_type ) {
                    return 'publish' === $post['status'] && $post_type === $post['post_type'];
                }
            )
        );
    }

    public function newest_modified_gmt( array $post_types ) {
        return null;
    }

    public function post_type_archive_link( $post_type ) {
        return $this->archives[ $post_type ] ?? null;
    }

    public function term_rows( $taxonomy, $after_id, $limit ) {
        $rows = [];

        ksort( $this->terms );

        foreach ( $this->terms as $id => $term ) {
            if ( $id > $after_id && $taxonomy === $term['taxonomy'] && $term['count'] > 0 ) {
                $rows[] = [ 'id' => $id, 'taxonomy' => $taxonomy, 'count' => $term['count'] ];
            }
        }

        return array_slice( $rows, 0, $limit );
    }

    public function term( $term_id ) {
        if ( ! isset( $this->terms[ $term_id ] ) ) {
            return null;
        }

        return [ 'id' => $term_id, 'taxonomy' => $this->terms[ $term_id ]['taxonomy'], 'count' => $this->terms[ $term_id ]['count'] ];
    }

    public function term_by_tt_id( $tt_id, $taxonomy ) {
        return $this->term( $tt_id );
    }

    public function post_terms( $post_id ) {
        return array_values( array_filter( array_map( [ $this, 'term' ], $this->posts[ $post_id ]['terms'] ?? [] ) ) );
    }

    public function term_link( $term_id, $taxonomy ) {
        return isset( $this->terms[ $term_id ] ) ? $this->home . $taxonomy . '/' . $this->terms[ $term_id ]['slug'] . '/' : null;
    }

    public function term_has_children( $term_id, $taxonomy ) {
        return isset( $this->children[ 'term:' . $term_id ] );
    }

    public function object_in_taxonomy( $post_type, $taxonomy ) {
        return true;
    }

    public function author_rows( $after_id, $limit ) {
        $rows = [];

        foreach ( $this->posts as $post ) {
            if ( 'publish' === $post['status'] && 'post' === $post['post_type'] && $post['author'] > $after_id ) {
                $rows[ $post['author'] ] = [ 'id' => $post['author'], 'count' => $this->author_post_count( $post['author'] ), 'modified_gmt' => null ];
            }
        }

        ksort( $rows );

        return array_slice( array_values( $rows ), 0, $limit );
    }

    public function author_post_count( $user_id ) {
        return count(
            array_filter(
                $this->posts,
                function ( $post ) use ( $user_id ) {
                    return 'publish' === $post['status'] && 'post' === $post['post_type'] && $user_id === $post['author'];
                }
            )
        );
    }

    public function author_link( $user_id ) {
        return $this->home . 'author/' . $user_id . '/';
    }

    public function month_rows( $before_key, $limit ) {
        $keys = [];

        foreach ( $this->posts as $post ) {
            if ( 'publish' === $post['status'] && 'post' === $post['post_type'] ) {
                $keys[ (int) substr( $post['date'], 0, 4 ) * 100 + (int) substr( $post['date'], 5, 2 ) ] = true;
            }
        }

        krsort( $keys );

        $rows = [];

        foreach ( array_keys( $keys ) as $key ) {
            if ( 0 === $before_key || $key < $before_key ) {
                $rows[] = [ 'year' => intdiv( $key, 100 ), 'month' => $key % 100 ];
            }
        }

        return array_slice( $rows, 0, $limit );
    }

    public function date_post_count( $year, $month ) {
        $prefix = $month > 0 ? sprintf( '%04d-%02d', $year, $month ) : sprintf( '%04d', $year );

        return count(
            array_filter(
                $this->posts,
                function ( $post ) use ( $prefix ) {
                    return 'publish' === $post['status'] && 'post' === $post['post_type'] && 0 === strpos( $post['date'], $prefix );
                }
            )
        );
    }

    public function year_link( $year ) {
        return $this->home . $year . '/';
    }

    public function month_link( $year, $month ) {
        return $this->home . $year . '/' . sprintf( '%02d', $month ) . '/';
    }

    public function feed_link( $feed = '' ) {
        return $this->home . ( 'comments_' === $feed ? 'comments/feed/' : 'feed/' );
    }

    public function paged_url( $base_url, $page ) {
        return rtrim( $base_url, '/' ) . '/page/' . $page . '/';
    }

    public function comment( $comment_id ) {
        return $this->comments[ $comment_id ] ?? null;
    }

    public function approved_comment_count( $post_id, $top_level_only ) {
        return $this->comment_counts[ $post_id ] ?? 0;
    }

    public function comment_page_url( $permalink, $page ) {
        return rtrim( $permalink, '/' ) . '/comment-page-' . $page . '/';
    }

    public function attachment_file_url( $attachment_id ) {
        return $this->attachments[ $attachment_id ]['url'] ?? null;
    }

    public function attachment_metadata( $attachment_id ) {
        return $this->attachments[ $attachment_id ]['meta'] ?? [];
    }

    public function sitemap_urls() {
        return $this->sitemaps;
    }

    public function site_icon_urls() {
        return $this->site_icons;
    }

    public function theme_stylesheet_urls() {
        return $this->stylesheets;
    }
}
