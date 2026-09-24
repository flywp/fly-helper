<?php

namespace FlyWP\StaticSite\Engine\Incremental;

use FlyWP\StaticSite\Engine\Discovery\HomeSource;
use FlyWP\StaticSite\Engine\Discovery\OriginUrl;
use FlyWP\StaticSite\Engine\Discovery\Pagination;
use FlyWP\StaticSite\Engine\Discovery\PostTypeSource;
use FlyWP\StaticSite\Engine\Discovery\RobotsMeta;
use FlyWP\StaticSite\Engine\Discovery\WordPressGateway;
use FlyWP\StaticSite\Engine\Discovery\WpGateway;

/**
 * Turns change rows into the URLs of an incremental build.
 *
 * - A global row forces a full build. One query finds it before other rows are read.
 * - For each changed post: the permalink, its <!--nextpage--> pages, its comment pages (comment changes),
 *   and all pages of its lists (terms, posts page, author, year, month, or the post type archive).
 * - For any change: the home page, the main and comments feeds, and the sitemaps.
 * - A list with more than MAX_LIST_PAGES pages forces a full build ("large_list").
 * - Known limit: other pages that show a post (Latest Posts block, related posts, widgets with new posts)
 *   are not rebuilt. The "Full rebuild" button covers this.
 *
 * @since 1.8.0
 */
class ChangeSetBuilder {

    /**
     * Rows and posts in one batch.
     */
    const BATCH_SIZE = 500;

    /**
     * Highest page count of one list in an incremental build.
     */
    const MAX_LIST_PAGES = 50;

    /**
     * @var ChangeStore
     */
    private $store;

    /**
     * @var WordPressGateway
     */
    private $wp;

    /**
     * @var string|null
     */
    private $origin_url;

    /**
     * URLs to rebuild, as keys.
     *
     * @var array<string, bool>
     */
    private $urls = [];

    /**
     * Deleted URLs, as keys.
     *
     * @var array<string, bool>
     */
    private $deleted = [];

    /**
     * List page 1 URL => page count.
     *
     * @var array<string, int>
     */
    private $lists = [];

    /**
     * @var bool
     */
    private $large_list = false;

    /**
     * Cached counts, by key.
     *
     * @var array<string, int>
     */
    private $counts = [];

    /**
     * @param ChangeStore|null      $store      Change rows. Null for the database table.
     * @param WordPressGateway|null $wp         Gateway. Null for WordPress.
     * @param string|null           $origin_url Origin URL that the engine fetches. Null to use the home URL.
     */
    public function __construct( ChangeStore $store = null, WordPressGateway $wp = null, $origin_url = null ) {
        $this->wp         = $wp ? $wp : new WpGateway();
        $this->store      = $store ? $store : new ChangeLog( $this->wp );
        $this->origin_url = $origin_url;
    }

    /**
     * Read the rows after $since_change_id, up to the highest ID at the start. Rows written during the build stay for the next build.
     *
     * @param int $since_change_id Highest row ID of the previous build.
     *
     * @return ChangeSet
     */
    public function build( $since_change_id = 0 ) {
        $since              = (int) $since_change_id;
        $set                = new ChangeSet();
        $set->max_change_id = max( $since, (int) $this->store->max_id() );

        if ( $set->max_change_id === $since ) {
            return $set;
        }

        if ( $this->store->has_global( $since, $set->max_change_id ) ) {
            $set->full_reason = 'global_change';

            return $set;
        }

        $this->urls       = [];
        $this->deleted    = [];
        $this->lists      = [];
        $this->large_list = false;

        $posts         = [];
        $terms         = [];
        $list_rows     = [];
        $comment_posts = [];
        $after_id      = $since;

        do {
            $rows       = $this->store->rows( $after_id, $set->max_change_id, self::BATCH_SIZE );
            $full_batch = count( $rows ) === self::BATCH_SIZE;

            foreach ( $rows as $row ) {
                $after_id  = (int) $row['id'];
                $url       = (string) $row['url'];
                $object_id = null === $row['object_id'] ? null : (int) $row['object_id'];

                if ( 'delete' === $row['kind'] ) {
                    self::add_to( $this->deleted, $url );
                }

                if ( 'list' === $row['kind'] ) {
                    $list_rows[] = [ $row['object_type'], $object_id, $url ];
                } elseif ( 'post' === $row['object_type'] ) {
                    self::add_to( $posts[ $object_id ], $url );

                    if ( 'comment' === $row['reason'] ) {
                        $comment_posts[ $object_id ] = true;
                    }
                } elseif ( 'term' === $row['object_type'] ) {
                    self::add_to( $terms[ $object_id ], $url );
                } elseif ( 'url' === $row['kind'] ) {
                    self::add_to( $this->urls, $url );
                }
            }
        } while ( $full_batch );

        if ( $after_id === $since ) {
            return $set;
        }

        $this->add_site_urls();
        $this->expand_posts( $posts, $comment_posts );
        $this->expand_terms( $terms );

        foreach ( $list_rows as $list ) {
            $this->expand_list( $list[0], $list[1], $list[2] );
        }

        if ( $this->large_list ) {
            $set->full_reason = 'large_list';

            return $set;
        }

        $origin            = new OriginUrl( $this->wp->home_url(), $this->origin_url );
        $set->urls         = self::rebase( $origin, array_keys( $this->urls ) );
        $set->deleted_urls = array_values( array_diff( self::rebase( $origin, array_keys( $this->deleted ) ), $set->urls ) );

        foreach ( $this->lists as $link => $pages ) {
            $url = $origin->rebase( $link );

            if ( null !== $url ) {
                $set->lists[] = [
                    'url'   => $url,
                    'pages' => $pages,
                ];
            }
        }

        return $set;
    }

    /**
     * Pages that show every change: home, feeds and sitemaps.
     *
     * @return void
     */
    private function add_site_urls() {
        self::add_to( $this->urls, $this->wp->home_url() );
        self::add_to( $this->urls, $this->wp->feed_link() );
        self::add_to( $this->urls, $this->wp->feed_link( 'comments_' ) );

        foreach ( $this->wp->sitemap_urls() as $url ) {
            self::add_to( $this->urls, $url );
        }
    }

    /**
     * @param array<int, array<string, bool>> $posts         Post ID => URLs recorded for the post.
     * @param array<int, bool>                $comment_posts Post IDs with a comment change.
     *
     * @return void
     */
    private function expand_posts( array $posts, array $comment_posts ) {
        $public_types = $this->wp->public_post_types();

        foreach ( array_chunk( array_keys( $posts ), self::BATCH_SIZE ) as $post_ids ) {
            $noindex = RobotsMeta::noindex_post_ids( $this->wp->seo_robots_meta( $post_ids ) );

            foreach ( $post_ids as $post_id ) {
                $post   = $this->wp->post( $post_id );
                $public = null !== $post && in_array( $post['post_type'], $public_types, true );

                if ( $public && 'publish' === $post['status'] && ! PostTypeSource::is_excluded( $post, $noindex ) ) {
                    $this->add_post_pages( $post, isset( $comment_posts[ $post_id ] ) );
                } else {
                    $this->deleted += (array) $posts[ $post_id ];
                }

                if ( $public ) {
                    foreach ( PostLists::for_post( $this->wp, $post ) as $list ) {
                        $this->expand_list( $list['type'], $list['id'], $list['url'] );
                    }
                }
            }
        }
    }

    /**
     * The permalink, the <!--nextpage--> pages, and the comment pages when comments changed.
     *
     * @param array{id:int, pages:int} $post     Post.
     * @param bool                     $comments True for a comment change.
     *
     * @return void
     */
    private function add_post_pages( array $post, $comments ) {
        $permalink = $this->wp->permalink( $post['id'] );

        if ( null === $permalink ) {
            return;
        }

        self::add_to( $this->urls, $permalink );

        $last_page = min( (int) ( $post['pages'] ?? 1 ), self::MAX_LIST_PAGES );

        for ( $page = 2; $page <= $last_page; $page++ ) {
            self::add_to( $this->urls, $this->wp->post_page_url( $permalink, $page ) );
        }

        if ( ! $comments || ! $this->wp->option( 'page_comments' ) ) {
            return;
        }

        $count = $this->wp->approved_comment_count( $post['id'], (bool) $this->wp->option( 'thread_comments' ) );
        $pages = Pagination::page_count( $count, $this->wp->option( 'comments_per_page' ) );

        $last_page = min( $pages, self::MAX_LIST_PAGES );

        for ( $page = 1; $pages > 1 && $page <= $last_page; $page++ ) {
            self::add_to( $this->urls, $this->wp->comment_page_url( $permalink, $page ) );
        }
    }

    /**
     * @param array<int, array<string, bool>> $terms Term ID => URLs recorded for the term.
     *
     * @return void
     */
    private function expand_terms( array $terms ) {
        $public_taxonomies = $this->wp->public_taxonomies();

        foreach ( $terms as $term_id => $recorded ) {
            $term = $this->wp->term( $term_id );
            $link = null !== $term && in_array( $term['taxonomy'], $public_taxonomies, true ) ? $this->wp->term_link( $term_id, $term['taxonomy'] ) : null;

            if ( null === $link ) {
                $this->deleted += (array) $recorded;

                continue;
            }

            $this->add_list( $link, $term['count'] );
        }
    }

    /**
     * Add all pages of a list. Counts come from the current data.
     *
     * @param string      $type term, blog, user, date or archive.
     * @param int|null    $id   Term ID, user ID, or year (YYYY) or month (YYYYMM).
     * @param string|null $url  Page 1 URL at change time.
     *
     * @return void
     */
    private function expand_list( $type, $id, $url ) {
        if ( 'term' === $type ) {
            $term = $this->wp->term( $id );

            if ( null !== $term ) {
                $this->add_list( $this->wp->term_link( $id, $term['taxonomy'] ), $term['count'] );
            }
        } elseif ( 'user' === $type ) {
            $this->add_list( $this->wp->author_link( $id ), $this->count( 'author', $id ) );
        } elseif ( 'blog' === $type ) {
            $this->add_list( HomeSource::blog_url( $this->wp ), $this->count( 'type', 'post' ) );
        } elseif ( 'date' === $type ) {
            $year  = $id > 9999 ? intdiv( $id, 100 ) : (int) $id;
            $month = $id > 9999 ? $id % 100 : 0;
            $link  = $month > 0 ? $this->wp->month_link( $year, $month ) : $this->wp->year_link( $year );

            $this->add_list( $link, $this->wp->date_post_count( $year, $month ) );
        } elseif ( 'archive' === $type ) {
            foreach ( $this->wp->public_post_types() as $post_type ) {
                if ( 'post' !== $post_type && null !== $url && $this->wp->post_type_archive_link( $post_type ) === $url ) {
                    $this->add_list( $url, $this->count( 'type', $post_type ) );
                }
            }
        }
    }

    /**
     * @param string|null $link       Page 1 URL.
     * @param int         $item_count Items in the list.
     *
     * @return void
     */
    private function add_list( $link, $item_count ) {
        if ( null === $link ) {
            return;
        }

        $pages = Pagination::page_count( $item_count, $this->wp->option( 'posts_per_page' ) );

        if ( $pages > self::MAX_LIST_PAGES ) {
            $this->large_list = true;

            return;
        }

        $this->lists[ $link ] = $pages;

        self::add_to( $this->urls, $link );

        foreach ( Pagination::urls( $this->wp, $link, $item_count ) as $page ) {
            self::add_to( $this->urls, $page->url );
        }
    }

    /**
     * @param string     $kind  "type" or "author".
     * @param string|int $value Post type or user ID.
     *
     * @return int
     */
    private function count( $kind, $value ) {
        $key = $kind . ':' . $value;

        if ( ! isset( $this->counts[ $key ] ) ) {
            $this->counts[ $key ] = 'type' === $kind ? $this->wp->published_count( $value ) : $this->wp->author_post_count( (int) $value );
        }

        return $this->counts[ $key ];
    }

    /**
     * @param array<string, bool>|null $set URL set.
     * @param string|null              $url URL.
     *
     * @return void
     */
    private static function add_to( &$set, $url ) {
        $set = (array) $set;

        if ( is_string( $url ) && '' !== $url ) {
            $set[ $url ] = true;
        }
    }

    /**
     * @param OriginUrl $origin Origin.
     * @param string[]  $urls   URLs.
     *
     * @return string[] Unique URLs on the origin.
     */
    private static function rebase( OriginUrl $origin, array $urls ) {
        return array_values( array_unique( array_filter( array_map( [ $origin, 'rebase' ], $urls ) ) ) );
    }
}
