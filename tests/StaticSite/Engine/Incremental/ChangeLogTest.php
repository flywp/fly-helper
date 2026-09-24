<?php

namespace FlyWP\Tests\StaticSite\Engine\Incremental;

use FlyWP\StaticSite\Engine\Incremental\ChangeLog;
use FlyWP\StaticSite\Engine\Incremental\ChangeSetBuilder;
use FlyWP\Tests\StaticSite\Engine\Discovery\FakeWordPressGateway;
use PHPUnit\Framework\TestCase;

class ChangeLogTest extends TestCase {

    /**
     * @var FakeWordPressGateway
     */
    private $wp;

    /**
     * @var FakeChangeStore
     */
    private $store;

    /**
     * @var ChangeLog
     */
    private $log;

    protected function setUp(): void {
        $this->wp    = new FakeWordPressGateway();
        $this->store = new FakeChangeStore();
        $this->log   = new ChangeLog( $this->wp, $this->store );
        $this->wp->add_term( 10, 'category', 'news', 12 );
        $this->wp->add_term( 11, 'post_tag', 'php', 3 );
    }

    public function test_saving_a_published_post_records_its_url_one_time() {
        $this->wp->add_post( 5, [ 'slug' => 'hello' ] );
        $this->wp->add_post( 6, [ 'status' => 'draft' ] );

        $this->log->on_save_post( 5, $this->post_object( 5, 'hello' ) );
        $this->log->on_save_post( 5, $this->post_object( 5, 'hello' ) );
        $this->log->on_save_post( 6, $this->post_object( 6, 'post-6', 'draft' ) );
        $this->log->on_save_post( 7, $this->post_object( 7, 'rev', 'inherit', 'revision' ) );

        $this->assertSame( [ [ 'url', 'post', 5, 'https://wp.test/hello/' ] ], $this->store->summary() );
    }

    public function test_global_post_types_count_only_when_published() {
        $this->log->on_save_post( 8, $this->post_object( 8, 'block', 'draft', 'wp_block' ) );

        $this->assertSame( [], $this->store->summary() );

        $this->log->on_save_post( 8, $this->post_object( 8, 'block', 'publish', 'wp_block' ) );

        $this->assertSame( [ [ 'global', null, null, null ] ], $this->store->summary() );
    }

    public function test_unpublish_records_the_old_url_as_deleted() {
        $this->log->on_post_updated( 5, $this->post_object( 5, 'hello', 'draft' ), $this->post_object( 5, 'hello' ) );

        $this->assertSame( [ [ 'delete', 'post', 5, 'https://wp.test/hello/' ] ], $this->store->summary() );
    }

    public function test_slug_change_records_the_old_url_and_the_old_author_list() {
        $this->log->on_post_updated( 5, $this->post_object( 5, 'new', 'publish', 'post', 4 ), $this->post_object( 5, 'old', 'publish', 'post', 3 ) );

        $this->assertSame(
            [
                [ 'delete', 'post', 5, 'https://wp.test/old/' ],
                [ 'list', 'user', 3, 'https://wp.test/author/3/' ],
            ],
            $this->store->summary()
        );
    }

    public function test_link_change_of_a_parent_post_or_term_is_global() {
        $this->wp->children['post:5'] = true;

        $this->log->on_post_updated( 5, $this->post_object( 5, 'company', 'publish', 'page' ), $this->post_object( 5, 'about', 'publish', 'page' ) );

        $this->assertSame( [ [ 'global', null, null, null ] ], $this->store->summary() );

        $store                          = new FakeChangeStore();
        $log                            = new ChangeLog( $this->wp, $store );
        $this->wp->children['term:10'] = true;

        $log->on_edit_terms( 10, 'category' );
        $this->wp->terms[10]['slug'] = 'renamed';
        $log->on_edited_term( 10, 10, 'category' );

        $this->assertSame( [ [ 'global', null, null, null ] ], $store->summary() );
    }

    public function test_hard_delete_rebuilds_the_same_lists_as_trash() {
        $this->wp->options['show_on_front']  = 'page';
        $this->wp->options['page_on_front']  = 100;
        $this->wp->options['page_for_posts'] = 101;
        $this->wp->add_post( 101, [ 'post_type' => 'page', 'slug' => 'blog' ] );

        for ( $id = 1; $id <= 11; $id++ ) {
            $this->wp->add_post( $id );
        }

        $this->wp->add_post( 50, [ 'slug' => 'hello', 'author' => 3, 'terms' => [ 10, 11 ], 'date' => '2026-08-20 09:00:00' ] );

        $trash_wp                        = clone $this->wp;
        $trash_wp->posts[50]['status']   = 'trash';
        $trash_store                     = new FakeChangeStore();
        $trash_store->add( 'delete', 'post', 50, 'https://wp.test/hello/' );
        $trashed = ( new ChangeSetBuilder( $trash_store, $trash_wp ) )->build( 0 );

        $this->log->on_before_delete_post( 50 );
        unset( $this->wp->posts[50] );
        $deleted = ( new ChangeSetBuilder( $this->store, $this->wp ) )->build( 0 );

        $this->assertSame( [ 'https://wp.test/hello/' ], $deleted->deleted_urls );
        $this->assertSame( $trashed->deleted_urls, $deleted->deleted_urls );
        $this->assertEqualsCanonicalizing( $trashed->urls, $deleted->urls );
        $this->assertEqualsCanonicalizing( $trashed->lists, $deleted->lists );
        $this->assertContains( 'https://wp.test/blog/page/2/', $deleted->urls );
        $this->assertContains( 'https://wp.test/category/news/page/2/', $deleted->urls );
        $this->assertContains( 'https://wp.test/author/3/', $deleted->urls );
        $this->assertContains( 'https://wp.test/2026/08/', $deleted->urls );
    }

    public function test_term_delete_records_the_term_and_its_posts() {
        $this->wp->add_post( 5, [ 'slug' => 'hello' ] );

        $this->log->on_pre_delete_term( 11, 'post_tag' );
        $this->log->on_delete_term( 11, 11, 'post_tag', null, [ 5 ] );

        $this->assertSame(
            [
                [ 'delete', 'term', 11, 'https://wp.test/post_tag/php/' ],
                [ 'url', 'post', 5, 'https://wp.test/hello/' ],
            ],
            $this->store->summary()
        );
    }

    public function test_large_term_or_parent_term_delete_is_global() {
        $large = new FakeChangeStore();
        ( new ChangeLog( $this->wp, $large ) )->on_delete_term( 10, 10, 'category', null, range( 1, 201 ) );

        $this->assertSame( [ [ 'global', null, null, null ] ], $large->summary() );

        $parent                         = new FakeChangeStore();
        $this->wp->children['term:10'] = true;
        ( new ChangeLog( $this->wp, $parent ) )->on_pre_delete_term( 10, 'category' );

        $this->assertSame( [ [ 'global', null, null, null ] ], $parent->summary() );
    }

    public function test_rows_created_after_the_snapshot_stay_after_clear() {
        $this->wp->add_post( 5 );
        $this->store->add( 'url', 'post', 5, 'https://wp.test/post-5/' );

        $builder = new ChangeSetBuilder( $this->log, $this->wp );
        $set     = $builder->build( 0 );

        $this->store->add( 'url', 'post', 5, 'https://wp.test/post-5/', 'during_build' );

        $this->assertTrue( $this->log->clear_up_to( $set->max_change_id ) );
        $this->assertSame( [ 2 ], array_keys( $this->store->rows ) );

        $next = $builder->build( 0 );

        $this->assertSame( 2, $next->max_change_id );
        $this->assertContains( 'https://wp.test/post-5/', $next->urls );
    }

    public function test_failed_insert_is_not_marked_as_recorded() {
        $this->wp->add_post( 5, [ 'slug' => 'hello' ] );
        $this->store->failing_inserts = 1;

        $this->log->on_save_post( 5, $this->post_object( 5, 'hello' ) );

        $this->assertSame( [], $this->store->summary() );

        $this->log->on_save_post( 5, $this->post_object( 5, 'hello' ) );

        $this->assertSame( [ [ 'url', 'post', 5, 'https://wp.test/hello/' ] ], $this->store->summary() );
    }

    public function test_bulk_request_records_one_global_row() {
        $this->wp->bulk = true;
        $this->wp->add_post( 5 );
        $this->wp->add_post( 6 );

        $this->log->on_save_post( 5, $this->post_object( 5, 'post-5' ) );
        $this->log->on_save_post( 6, $this->post_object( 6, 'post-6' ) );

        $this->assertSame( [ [ 'global', null, null, null ] ], $this->store->summary() );
        $this->assertSame( 'bulk_request', array_values( $this->store->rows )[0]['reason'] );
    }

    public function test_attachment_file_urls_are_recorded_on_update_and_delete() {
        $base = 'https://wp.test/wp-content/uploads/2026/09/';
        $meta = [
            'sizes'          => [
                'thumbnail' => [ 'file' => 'hero-150x150.jpg' ],
                'large'     => [ 'file' => 'hero-1024x768.jpg' ],
            ],
            'original_image' => 'hero-original.jpg',
        ];

        $this->wp->attachments[40] = [ 'url' => $base . 'hero.jpg', 'meta' => $meta ];

        $this->assertSame( $meta, $this->log->on_attachment_metadata( $meta, 40 ) );

        $this->log->on_delete_attachment( 40 );

        $files    = [ 'hero.jpg', 'hero-150x150.jpg', 'hero-1024x768.jpg', 'hero-original.jpg' ];
        $expected = [];

        foreach ( [ 'url', 'delete' ] as $kind ) {
            foreach ( $files as $file ) {
                $expected[] = [ $kind, null, null, $base . $file ];
            }
        }

        $this->assertSame( $expected, $this->store->summary() );
    }

    /**
     * @param int    $id     Post ID.
     * @param string $slug   Slug.
     * @param string $status Status.
     * @param string $type   Post type.
     * @param int    $author Author ID.
     *
     * @return object
     */
    private function post_object( $id, $slug, $status = 'publish', $type = 'post', $author = 1 ) {
        return (object) [
            'ID'          => $id,
            'post_name'   => $slug,
            'post_status' => $status,
            'post_type'   => $type,
            'post_author' => $author,
        ];
    }
}
