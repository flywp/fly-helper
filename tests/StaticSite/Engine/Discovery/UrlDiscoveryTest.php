<?php

namespace FlyWP\Tests\StaticSite\Engine\Discovery;

use FlyWP\StaticSite\Engine\Discovery\AuthorSource;
use FlyWP\StaticSite\Engine\Discovery\DateArchiveSource;
use FlyWP\StaticSite\Engine\Discovery\DiscoveredUrl;
use FlyWP\StaticSite\Engine\Discovery\OriginUrl;
use FlyWP\StaticSite\Engine\Discovery\Pagination;
use FlyWP\StaticSite\Engine\Discovery\PostTypeSource;
use FlyWP\StaticSite\Engine\Discovery\RobotsMeta;
use FlyWP\StaticSite\Engine\Discovery\UrlDiscovery;
use FlyWP\StaticSite\Engine\Discovery\UrlSource;
use PHPUnit\Framework\TestCase;

class UrlDiscoveryTest extends TestCase {

    /**
     * @var FakeWordPressGateway
     */
    private $wp;

    protected function setUp(): void {
        $this->wp = new FakeWordPressGateway();
    }

    public function test_pagination_count_follows_posts_per_page() {
        for ( $id = 1; $id <= 25; $id++ ) {
            $this->wp->add_post( $id );
        }

        $this->wp->add_term( 7, 'category', 'news', 11 );
        $this->wp->add_term( 8, 'post_tag', 'small', 10 );

        $urls = $this->discover();

        $this->assertContains( 'https://wp.test/page/2/', $urls );
        $this->assertContains( 'https://wp.test/page/3/', $urls );
        $this->assertNotContains( 'https://wp.test/page/4/', $urls );
        $this->assertContains( 'https://wp.test/category/news/page/2/', $urls );
        $this->assertNotContains( 'https://wp.test/category/news/page/3/', $urls );
        $this->assertContains( 'https://wp.test/post_tag/small/', $urls );
        $this->assertNotContains( 'https://wp.test/post_tag/small/page/2/', $urls );
        $this->assertContains( 'https://wp.test/author/1/page/3/', $urls );
        $this->assertSame( 1, Pagination::page_count( 0, 10 ) );
        $this->assertSame( 3, Pagination::page_count( 21, 10 ) );
    }

    public function test_posts_per_page_minus_one_gives_one_page() {
        $this->wp->options['posts_per_page'] = -1;

        for ( $id = 1; $id <= 25; $id++ ) {
            $this->wp->add_post( $id );
        }

        $urls = $this->discover();

        $this->assertNotContains( 'https://wp.test/page/2/', $urls );
        $this->assertSame( 1, Pagination::page_count( 10000, 0 ) );
    }

    public function test_static_front_page_paginates_the_posts_page_only() {
        $this->wp->options['show_on_front']  = 'page';
        $this->wp->options['page_on_front']  = 100;
        $this->wp->options['page_for_posts'] = 101;
        $this->wp->options['posts_per_page'] = 1;
        $this->wp->add_post( 100, [ 'post_type' => 'page', 'slug' => '' ] );
        $this->wp->add_post( 101, [ 'post_type' => 'page', 'slug' => 'blog' ] );
        $this->wp->add_post( 1 );
        $this->wp->add_post( 2 );

        $urls = $this->discover();

        $this->assertContains( 'https://wp.test/blog/page/2/', $urls );
        $this->assertNotContains( 'https://wp.test/page/2/', $urls );
    }

    public function test_password_and_noindex_posts_are_excluded() {
        $this->wp->add_post( 1, [ 'slug' => 'visible' ] );
        $this->wp->add_post( 2, [ 'slug' => 'secret', 'password' => 'x' ] );
        $this->wp->add_post( 3, [ 'slug' => 'yoast-noindex' ] );
        $this->wp->add_post( 4, [ 'slug' => 'rank-math-noindex' ] );
        $this->wp->add_post( 5, [ 'slug' => 'yoast-index', 'post_type' => 'page' ] );
        $this->wp->add_post( 6, [ 'slug' => 'draft', 'status' => 'draft' ] );

        $this->wp->robots_meta = [
            [ 'post_id' => 3, 'meta_key' => RobotsMeta::YOAST_NOINDEX, 'meta_value' => '1' ],
            [ 'post_id' => 4, 'meta_key' => RobotsMeta::RANK_MATH_ROBOTS, 'meta_value' => 'a:2:{i:0;s:7:"noindex";i:1;s:8:"nofollow";}' ],
            [ 'post_id' => 5, 'meta_key' => RobotsMeta::YOAST_NOINDEX, 'meta_value' => '2' ],
        ];

        $items = iterator_to_array( ( new PostTypeSource( $this->wp ) )->urls(), false );

        $this->assertSame( [ 'https://wp.test/visible/', 'https://wp.test/yoast-index/' ], $this->to_urls( $items ) );
        $this->assertSame( 'post', $items[0]->kind );
        $this->assertSame( 'page', $items[1]->kind );
        $this->assertSame( 'post', $items[0]->object_type );
        $this->assertSame( 1, $items[0]->object_id );
        $this->assertSame( '2026-09-01 10:00:00', $items[0]->modified_gmt );
    }

    public function test_excluded_urls_list_password_and_noindex_posts_and_terms() {
        $this->wp->add_post( 1, [ 'slug' => 'visible' ] );
        $this->wp->add_post( 2, [ 'slug' => 'secret', 'password' => 'x' ] );
        $this->wp->add_post( 3, [ 'slug' => 'hidden' ] );
        $this->wp->add_post( 4, [ 'slug' => 'draft-secret', 'password' => 'x', 'status' => 'draft' ] );
        $this->wp->add_term( 10, 'category', 'news', 1 );
        $this->wp->add_term( 11, 'post_tag', 'yoast-hidden', 1 );
        $this->wp->add_term( 12, 'post_tag', 'rank-math-hidden', 1 );
        $this->wp->add_term( 13, 'post_tag', 'rank-math-visible', 1 );

        $this->wp->robots_meta     = [ [ 'post_id' => 3, 'meta_key' => RobotsMeta::YOAST_NOINDEX, 'meta_value' => '1' ] ];
        $this->wp->yoast_term_meta = [
            'post_tag' => [ 11 => [ 'wpseo_noindex' => 'noindex' ] ],
            'category' => [ 10 => [ 'wpseo_noindex' => 'index' ] ],
        ];
        $this->wp->term_robots_meta = [
            [ 'term_id' => 12, 'taxonomy' => 'post_tag', 'meta_value' => 'a:1:{i:0;s:7:"noindex";}' ],
            [ 'term_id' => 13, 'taxonomy' => 'post_tag', 'meta_value' => 'a:1:{i:0;s:5:"index";}' ],
        ];

        $discovery = new UrlDiscovery( $this->wp, 'http://127.0.0.1:8080', [] );

        $this->assertSame(
            [
                'http://127.0.0.1:8080/secret/',
                'http://127.0.0.1:8080/hidden/',
                'http://127.0.0.1:8080/post_tag/yoast-hidden/',
                'http://127.0.0.1:8080/post_tag/rank-math-hidden/',
            ],
            iterator_to_array( $discovery->excluded_urls(), false )
        );
    }

    public function test_posts_authors_and_months_are_read_in_batches() {
        for ( $id = 1; $id <= 5; $id++ ) {
            $this->wp->add_post( $id, [ 'author' => $id % 2 + 1, 'date' => sprintf( '202%d-0%d-01 10:00:00', $id % 2 + 5, $id ) ] );
        }

        $items = iterator_to_array( ( new PostTypeSource( $this->wp, 2 ) )->urls(), false );

        $this->assertCount( 5, $items );
        $this->assertSame( 3, $this->wp->post_row_calls );
        $this->assertSame( [ 'https://wp.test/author/1/', 'https://wp.test/author/2/' ], $this->source_urls( new AuthorSource( $this->wp, 1 ) ) );
        $this->assertSame(
            [
                'https://wp.test/2026/',
                'https://wp.test/2026/05/',
                'https://wp.test/2026/03/',
                'https://wp.test/2026/01/',
                'https://wp.test/2025/',
                'https://wp.test/2025/04/',
                'https://wp.test/2025/02/',
            ],
            $this->source_urls( new DateArchiveSource( $this->wp, 2 ) )
        );
    }

    public function test_filter_adds_strings_and_objects() {
        $this->wp->filters['flywp_static_urls'] = function ( $urls, $origin_url ) {
            $urls[] = 'https://wp.test/landing/';
            $urls[] = new DiscoveredUrl( 'https://wp.test/special.json', 'asset' );
            $urls[] = 'https://other.test/outside/';

            return $urls;
        };

        $urls = $this->discover();

        $this->assertContains( 'https://wp.test/landing/', $urls );
        $this->assertContains( 'https://wp.test/special.json', $urls );
        $this->assertNotContains( 'https://other.test/outside/', $urls );
    }

    public function test_urls_are_unique_and_moved_to_the_origin() {
        $this->wp->add_post( 1, [ 'slug' => 'hello' ] );
        $this->wp->sitemaps   = [ 'https://wp.test/wp-sitemap.xml', 'https://wp.test/wp-sitemap-index.xsl' ];
        $this->wp->site_icons = [ 'https://cdn.test/icon.png' ];
        $this->wp->filters['flywp_static_urls'] = function ( $urls ) {
            return [ 'http://wp.test/hello/#top', 'https://wp.test/' ];
        };

        $discovery = new UrlDiscovery( $this->wp, 'http://127.0.0.1:8080', UrlDiscovery::default_sources( $this->wp, 'http://127.0.0.1:8080' ) );
        $items     = iterator_to_array( $discovery->urls(), false );
        $urls      = $this->to_urls( $items );

        $this->assertSame( array_values( array_unique( $urls ) ), $urls );
        $this->assertSame( 'http://127.0.0.1:8080/', $urls[0] );
        $this->assertSame( 'home', $items[0]->kind );
        $this->assertContains( 'http://127.0.0.1:8080/hello/', $urls );
        $this->assertContains( 'http://127.0.0.1:8080/robots.txt', $urls );
        $this->assertContains( 'http://127.0.0.1:8080/wp-sitemap.xml', $urls );
        $this->assertNotContains( 'https://cdn.test/icon.png', $urls );

        foreach ( $items as $item ) {
            $this->assertStringStartsWith( 'http://127.0.0.1:8080/', $item->url );
        }
    }

    public function test_robots_and_favicon_need_pretty_permalinks() {
        $this->wp->options['permalink_structure'] = '';

        $urls = $this->discover();

        $this->assertNotContains( 'https://wp.test/robots.txt', $urls );
        $this->assertNotContains( 'https://wp.test/favicon.ico', $urls );
    }

    public function test_robots_and_favicon_are_under_a_subdirectory_home() {
        $this->wp->home = 'https://wp.test/shop/';

        $urls = $this->discover();

        $this->assertContains( 'https://wp.test/shop/robots.txt', $urls );
        $this->assertContains( 'https://wp.test/shop/favicon.ico', $urls );
    }

    public function test_origin_url_keeps_other_hosts_out() {
        $origin = new OriginUrl( 'https://wp.test/', null );

        $this->assertSame( 'https://wp.test/a/?b=1', $origin->rebase( 'http://WP.test/a/?b=1#c' ) );
        $this->assertSame( 'https://wp.test/', $origin->rebase( 'https://wp.test' ) );
        $this->assertNull( $origin->rebase( 'https://wp.test.evil.com/' ) );
        $this->assertNull( $origin->rebase( 'https://cdn.test/wp.test/' ) );
    }

    /**
     * @return string[]
     */
    private function discover() {
        $discovery = new UrlDiscovery( $this->wp, null, UrlDiscovery::default_sources( $this->wp, null ) );

        return $this->to_urls( iterator_to_array( $discovery->urls(), false ) );
    }

    /**
     * @param UrlSource $source Source.
     *
     * @return string[]
     */
    private function source_urls( UrlSource $source ) {
        return $this->to_urls( iterator_to_array( $source->urls(), false ) );
    }

    /**
     * @param DiscoveredUrl[] $items Items.
     *
     * @return string[]
     */
    private function to_urls( array $items ) {
        return array_map(
            function ( DiscoveredUrl $item ) {
                return $item->url;
            },
            $items
        );
    }
}
