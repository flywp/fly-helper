<?php

namespace FlyWP\Tests\StaticSite\Engine\Incremental;

use FlyWP\StaticSite\Engine\Incremental\ChangeSetBuilder;
use FlyWP\Tests\StaticSite\Engine\Discovery\FakeWordPressGateway;
use PHPUnit\Framework\TestCase;

class ChangeSetBuilderTest extends TestCase {

    /**
     * @var FakeWordPressGateway
     */
    private $wp;

    /**
     * @var FakeChangeStore
     */
    private $store;

    protected function setUp(): void {
        $this->wp           = new FakeWordPressGateway();
        $this->store        = new FakeChangeStore();
        $this->wp->sitemaps = [ 'https://wp.test/wp-sitemap.xml', 'https://wp.test/wp-sitemap-posts-post-1.xml' ];
        $this->wp->add_term( 10, 'category', 'news', 12 );
        $this->wp->add_term( 11, 'post_tag', 'php', 3 );
    }

    public function test_no_rows_means_nothing_changed() {
        $set = $this->builder()->build( 0 );

        $this->assertNull( $set->full_reason );
        $this->assertSame( [], $set->urls );
        $this->assertSame( [], $set->deleted_urls );
        $this->assertSame( [], $set->lists );
        $this->assertSame( 0, $set->max_change_id );
    }

    public function test_post_with_two_terms_and_author_expands_to_its_lists() {
        $this->wp->add_post( 5, [ 'slug' => 'hello', 'author' => 3, 'terms' => [ 10, 11 ], 'date' => '2026-08-20 09:00:00' ] );
        $this->store->add( 'url', 'post', 5, 'https://wp.test/hello/' );
        $this->store->add( 'url', 'post', 5, 'https://wp.test/hello/' );

        $set = $this->builder()->build( 0 );

        $expected = [
            'https://wp.test/',
            'https://wp.test/feed/',
            'https://wp.test/comments/feed/',
            'https://wp.test/wp-sitemap.xml',
            'https://wp.test/wp-sitemap-posts-post-1.xml',
            'https://wp.test/hello/',
            'https://wp.test/category/news/',
            'https://wp.test/category/news/page/2/',
            'https://wp.test/post_tag/php/',
            'https://wp.test/author/3/',
            'https://wp.test/2026/',
            'https://wp.test/2026/08/',
        ];

        $this->assertNull( $set->full_reason );
        $this->assertEqualsCanonicalizing( $expected, $set->urls );
        $this->assertSame( [], $set->deleted_urls );
        $this->assertEqualsCanonicalizing(
            [
                [ 'url' => 'https://wp.test/category/news/', 'pages' => 2 ],
                [ 'url' => 'https://wp.test/post_tag/php/', 'pages' => 1 ],
                [ 'url' => 'https://wp.test/', 'pages' => 1 ],
                [ 'url' => 'https://wp.test/author/3/', 'pages' => 1 ],
                [ 'url' => 'https://wp.test/2026/', 'pages' => 1 ],
                [ 'url' => 'https://wp.test/2026/08/', 'pages' => 1 ],
            ],
            $set->lists
        );
        $this->assertSame( 2, $set->max_change_id );
    }

    public function test_all_pages_of_a_list_use_the_current_count() {
        $this->wp->add_term( 10, 'category', 'news', 45 );
        $this->wp->add_post( 5, [ 'slug' => 'hello', 'terms' => [ 10 ] ] );
        $this->store->add( 'url', 'post', 5, 'https://wp.test/hello/' );

        $set = $this->builder()->build( 0 );

        foreach ( [ 2, 3, 4, 5 ] as $page ) {
            $this->assertContains( 'https://wp.test/category/news/page/' . $page . '/', $set->urls );
        }

        $this->assertNotContains( 'https://wp.test/category/news/page/6/', $set->urls );
        $this->assertContains( [ 'url' => 'https://wp.test/category/news/', 'pages' => 5 ], $set->lists );
    }

    public function test_list_with_more_than_fifty_pages_forces_full_build() {
        $this->wp->add_term( 10, 'category', 'news', 501 );
        $this->wp->add_post( 5, [ 'terms' => [ 10 ] ] );
        $this->store->add( 'url', 'post', 5, 'https://wp.test/post-5/' );

        $set = $this->builder()->build( 0 );

        $this->assertSame( 'large_list', $set->full_reason );
        $this->assertSame( [], $set->urls );
    }

    public function test_global_change_forces_full_build() {
        $this->wp->add_post( 5 );
        $this->store->add( 'url', 'post', 5, 'https://wp.test/post-5/' );
        $this->store->add( 'global', null, null, null, 'switch_theme' );

        $set = $this->builder()->build( 0 );

        $this->assertSame( 'global_change', $set->full_reason );
        $this->assertSame( [], $set->urls );
        $this->assertSame( 2, $set->max_change_id );
    }

    public function test_deleted_post_gives_deleted_url_and_list_pages() {
        $this->store->add( 'delete', 'post', 9, 'https://wp.test/gone/' );
        $this->store->add( 'url', null, null, 'https://wp.test/category/news/' );

        $set = $this->builder()->build( 0 );

        $this->assertSame( [ 'https://wp.test/gone/' ], $set->deleted_urls );
        $this->assertContains( 'https://wp.test/', $set->urls );
        $this->assertContains( 'https://wp.test/feed/', $set->urls );
        $this->assertContains( 'https://wp.test/wp-sitemap.xml', $set->urls );
        $this->assertContains( 'https://wp.test/category/news/', $set->urls );
        $this->assertNotContains( 'https://wp.test/gone/', $set->urls );
    }

    public function test_trashed_post_rebuilds_the_archives_it_was_in() {
        $this->wp->add_post( 5, [ 'slug' => 'old__trashed', 'status' => 'trash', 'terms' => [ 11 ] ] );
        $this->store->add( 'delete', 'post', 5, 'https://wp.test/old/' );

        $set = $this->builder()->build( 0 );

        $this->assertSame( [ 'https://wp.test/old/' ], $set->deleted_urls );
        $this->assertContains( 'https://wp.test/post_tag/php/', $set->urls );
        $this->assertNotContains( 'https://wp.test/old__trashed/', $set->urls );
    }

    public function test_password_protected_post_is_deleted() {
        $this->wp->add_post( 5, [ 'slug' => 'private-now', 'password' => 'secret' ] );
        $this->store->add( 'url', 'post', 5, 'https://wp.test/private-now/' );

        $set = $this->builder()->build( 0 );

        $this->assertSame( [ 'https://wp.test/private-now/' ], $set->deleted_urls );
        $this->assertNotContains( 'https://wp.test/private-now/', $set->urls );
    }

    public function test_republished_url_is_not_deleted() {
        $this->wp->add_post( 5, [ 'slug' => 'back' ] );
        $this->store->add( 'delete', 'post', 5, 'https://wp.test/back/' );
        $this->store->add( 'url', 'post', 5, 'https://wp.test/back/' );

        $set = $this->builder()->build( 0 );

        $this->assertContains( 'https://wp.test/back/', $set->urls );
        $this->assertSame( [], $set->deleted_urls );
    }

    public function test_only_rows_after_since_are_read_and_urls_move_to_origin() {
        $this->wp->add_post( 5, [ 'slug' => 'old-change' ] );
        $this->wp->add_post( 6, [ 'slug' => 'new-change' ] );
        $this->store->add( 'global', null, null, null, 'switch_theme' );
        $this->store->add( 'url', 'post', 6, 'https://wp.test/new-change/' );

        $set = ( new ChangeSetBuilder( $this->store, $this->wp, 'http://127.0.0.1:8080' ) )->build( 1 );

        $this->assertNull( $set->full_reason );
        $this->assertContains( 'http://127.0.0.1:8080/new-change/', $set->urls );
        $this->assertNotContains( 'http://127.0.0.1:8080/old-change/', $set->urls );
        $this->assertContains( [ 'url' => 'http://127.0.0.1:8080/', 'pages' => 1 ], $set->lists );
        $this->assertSame( 2, $set->max_change_id );
    }

    public function test_deleted_term_url_is_deleted() {
        $this->store->add( 'delete', 'term', 99, 'https://wp.test/category/removed/' );
        $this->store->add( 'url', 'term', 10, 'https://wp.test/category/news/' );

        $set = $this->builder()->build( 0 );

        $this->assertSame( [ 'https://wp.test/category/removed/' ], $set->deleted_urls );
        $this->assertContains( 'https://wp.test/category/news/page/2/', $set->urls );
    }

    public function test_multi_page_post_and_comment_pages_are_rebuilt() {
        $this->wp->options['page_comments']     = '1';
        $this->wp->options['comments_per_page'] = 5;
        $this->wp->comment_counts[5]            = 12;
        $this->wp->add_post( 5, [ 'slug' => 'long', 'pages' => 3 ] );
        $this->store->add( 'url', 'post', 5, 'https://wp.test/long/', 'comment' );

        $set = $this->builder()->build( 0 );

        foreach ( [ 'long/2/', 'long/3/', 'long/comment-page-1/', 'long/comment-page-3/' ] as $path ) {
            $this->assertContains( 'https://wp.test/' . $path, $set->urls );
        }

        $this->assertNotContains( 'https://wp.test/long/4/', $set->urls );
        $this->assertNotContains( 'https://wp.test/long/comment-page-4/', $set->urls );
    }

    /**
     * @return ChangeSetBuilder
     */
    private function builder() {
        return new ChangeSetBuilder( $this->store, $this->wp );
    }
}
