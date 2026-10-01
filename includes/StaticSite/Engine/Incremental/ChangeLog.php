<?php

namespace FlyWP\StaticSite\Engine\Incremental;

use FlyWP\StaticSite\Engine\Discovery\WordPressGateway;
use FlyWP\StaticSite\Engine\Discovery\WpGateway;

/**
 * Records content changes for incremental builds.
 *
 * Row kinds:
 *
 * - url: rebuild the URL. object_type/object_id tell the builder which lists to rebuild too.
 * - delete: the URL is gone.
 * - list: rebuild all pages of a list (object_type term, user, blog, archive or date). Written before a hard delete.
 * - global: the change can touch every page, so the next build is full.
 *
 * Rules:
 *
 * - Links are recorded at change time, because an old permalink is not known after the change.
 * - A link change of a parent post or term changes the child links too, so it is global.
 * - During an import or an install, the request records one global row only.
 * - Multisite is out of scope. Rows go to the table of the current site prefix.
 *
 * @since 1.8.0
 */
class ChangeLog implements ChangeStore {

    /**
     * Table name without prefix.
     */
    const TABLE = WpChangeStore::TABLE;

    /**
     * Option that turns on the hooks. The app sets it to "1".
     */
    const ENABLED_OPTION = 'flywp_static_enabled';

    /**
     * A deleted term with more objects than this is a global change.
     */
    const MAX_TERM_OBJECTS = 200;

    /**
     * Actions that change the whole site.
     */
    const GLOBAL_HOOKS = [
        'switch_theme',
        'customize_save_after',
        'wp_update_nav_menu',
        'wp_delete_nav_menu',
        'activated_plugin',
        'deactivated_plugin',
        'upgrader_process_complete',
    ];

    /**
     * Options that change the whole site.
     */
    const GLOBAL_OPTIONS = [
        'permalink_structure',
        'category_base',
        'tag_base',
        'blogname',
        'blogdescription',
        'home',
        'siteurl',
        'show_on_front',
        'page_on_front',
        'page_for_posts',
        'posts_per_page',
        'sidebars_widgets',
        'site_icon',
        'date_format',
        'time_format',
        'timezone_string',
        'gmt_offset',
        'WPLANG',
    ];

    /**
     * Option name prefixes that change the whole site (widgets, theme mods).
     */
    const GLOBAL_OPTION_PREFIXES = [ 'widget_', 'theme_mods_' ];

    /**
     * Block theme and reusable content post types. When published, they can show on every page.
     */
    const GLOBAL_POST_TYPES = [ 'wp_template', 'wp_template_part', 'wp_global_styles', 'wp_navigation', 'wp_block' ];

    /**
     * Rows recorded in this request, to write each row one time only.
     *
     * @var array<string, bool>
     */
    private $recorded = [];

    /**
     * Term links before an edit, by term ID.
     *
     * @var array<int, string|null>
     */
    private $term_links = [];

    /**
     * @var WordPressGateway
     */
    private $wp;

    /**
     * @var ChangeStore
     */
    private $store;

    /**
     * @param WordPressGateway|null $wp    Gateway. Null for WordPress.
     * @param ChangeStore|null      $store Rows. Null for the database table.
     */
    public function __construct( WordPressGateway $wp = null, ChangeStore $store = null ) {
        $this->wp    = $wp ? $wp : new WpGateway();
        $this->store = $store ? $store : new WpChangeStore();
    }

    /**
     * Register the hooks. Does nothing when the option flywp_static_enabled is not "1".
     *
     * @return void
     */
    public static function register_hooks() {
        if ( '1' !== (string) get_option( self::ENABLED_OPTION ) ) {
            return;
        }

        $log = new self();

        add_action( 'save_post', [ $log, 'on_save_post' ], 10, 2 );
        add_action( 'post_updated', [ $log, 'on_post_updated' ], 10, 3 );
        add_action( 'before_delete_post', [ $log, 'on_before_delete_post' ] );
        add_action( 'set_object_terms', [ $log, 'on_set_object_terms' ], 10, 6 );
        add_action( 'edit_terms', [ $log, 'on_edit_terms' ], 10, 2 );
        add_action( 'edited_term', [ $log, 'on_edited_term' ], 10, 3 );
        add_action( 'pre_delete_term', [ $log, 'on_pre_delete_term' ], 10, 2 );
        add_action( 'delete_term', [ $log, 'on_delete_term' ], 10, 5 );
        add_action( 'transition_comment_status', [ $log, 'on_transition_comment_status' ], 10, 3 );
        add_action( 'comment_post', [ $log, 'on_comment_post' ], 10, 2 );
        add_action( 'edit_comment', [ $log, 'on_edit_comment' ] );
        add_action( 'attachment_updated', [ $log, 'on_attachment_updated' ] );
        add_filter( 'wp_update_attachment_metadata', [ $log, 'on_attachment_metadata' ], 10, 2 );
        add_action( 'delete_attachment', [ $log, 'on_delete_attachment' ] );
        add_action( 'updated_option', [ $log, 'on_option' ] );
        add_action( 'added_option', [ $log, 'on_option' ] );
        add_action( 'deleted_option', [ $log, 'on_option' ] );

        foreach ( self::GLOBAL_HOOKS as $hook ) {
            add_action(
                $hook,
                function () use ( $log, $hook ) {
                    $log->record( 'global', null, null, null, $hook );
                }
            );
        }
    }

    /**
     * Create or update the table.
     *
     * @return void
     */
    public static function install() {
        WpChangeStore::install();
    }

    /**
     * @return string Table name with prefix.
     */
    public static function table() {
        return WpChangeStore::table();
    }

    /**
     * {@inheritdoc}
     */
    public function insert( $kind, $object_type, $object_id, $url, $reason ) {
        return $this->store->insert( $kind, $object_type, $object_id, $url, $reason );
    }

    /**
     * {@inheritdoc}
     */
    public function rows( $after_id, $up_to_id, $limit ) {
        return $this->store->rows( $after_id, $up_to_id, $limit );
    }

    /**
     * {@inheritdoc}
     */
    public function has_global( $after_id, $up_to_id ) {
        return $this->store->has_global( $after_id, $up_to_id );
    }

    /**
     * {@inheritdoc}
     */
    public function max_id() {
        return $this->store->max_id();
    }

    /**
     * {@inheritdoc}
     */
    public function clear_up_to( $change_id ) {
        return $this->store->clear_up_to( $change_id );
    }

    /**
     * Write one row. A url, delete or list row without a URL is not written.
     *
     * @param string      $kind        url, delete, list or global.
     * @param string|null $object_type Object type.
     * @param int|null    $object_id   Object ID.
     * @param string|null $url         Absolute URL.
     * @param string      $reason      Short reason.
     *
     * @return bool False when the row is not written.
     */
    public function record( $kind, $object_type, $object_id, $url, $reason ) {
        if ( 'global' !== $kind && $this->wp->is_bulk_request() ) {
            return $this->record( 'global', null, null, null, 'bulk_request' );
        }

        if ( 'global' !== $kind && ( ! is_string( $url ) || '' === $url ) ) {
            return false;
        }

        $key = 'global' === $kind ? 'global' : implode( '|', [ $kind, $object_type, $object_id, $url, $reason ] );

        if ( isset( $this->recorded[ $key ] ) ) {
            return true;
        }

        $object_id = null === $object_id ? null : (int) $object_id;
        $url       = 'global' === $kind ? null : $url;

        if ( ! $this->store->insert( $kind, $object_type, $object_id, $url, $reason ) ) {
            return false;
        }

        $this->recorded[ $key ] = true;

        return true;
    }

    /**
     * A post is saved. This also runs when a post is published the first time.
     *
     * @param int    $post_id Post ID.
     * @param object $post    Post.
     *
     * @return void
     */
    public function on_save_post( $post_id, $post ) {
        if ( ! is_object( $post ) || 'revision' === $post->post_type ) {
            return;
        }

        if ( in_array( $post->post_type, self::GLOBAL_POST_TYPES, true ) ) {
            if ( 'publish' === $post->post_status ) {
                $this->record( 'global', null, null, null, 'post_type:' . $post->post_type );
            }

            return;
        }

        $this->record_post( $post_id, 'post_saved' );
    }

    /**
     * A post is updated. Record the old permalink when the post is unpublished, trashed or its URL changes.
     *
     * @param int    $post_id Post ID.
     * @param object $after   Post after the update.
     * @param object $before  Post before the update.
     *
     * @return void
     */
    public function on_post_updated( $post_id, $after, $before ) {
        if ( ! is_object( $after ) || ! is_object( $before ) || 'publish' !== $before->post_status ) {
            return;
        }

        if ( in_array( $before->post_type, self::GLOBAL_POST_TYPES, true ) ) {
            if ( 'publish' !== $after->post_status ) {
                $this->record( 'global', null, null, null, 'post_type:' . $before->post_type );
            }

            return;
        }

        if ( ! $this->is_public_type( $before->post_type ) ) {
            return;
        }

        $old_link = $this->wp->object_permalink( $before );
        $new_link = 'publish' === $after->post_status ? $this->wp->object_permalink( $after ) : null;

        if ( null !== $old_link && $old_link !== $new_link ) {
            if ( $this->wp->post_has_children( $post_id, $before->post_type ) ) {
                $this->record( 'global', null, null, null, 'parent_link_changed' );

                return;
            }

            $this->record( 'delete', 'post', $post_id, $old_link, null === $new_link ? 'post_unpublished' : 'permalink_changed' );
        }

        if ( (int) $before->post_author !== (int) $after->post_author ) {
            $this->record( 'list', 'user', (int) $before->post_author, $this->wp->author_link( (int) $before->post_author ), 'author_changed' );
        }
    }

    /**
     * A post is about to be deleted. Record its permalink and its lists while they are known.
     *
     * @param int $post_id Post ID.
     *
     * @return void
     */
    public function on_before_delete_post( $post_id ) {
        $post = $this->wp->post( $post_id );

        if ( null === $post ) {
            return;
        }

        if ( in_array( $post['post_type'], self::GLOBAL_POST_TYPES, true ) ) {
            if ( 'publish' === $post['status'] ) {
                $this->record( 'global', null, null, null, 'post_type:' . $post['post_type'] );
            }

            return;
        }

        if ( ! $this->is_public_type( $post['post_type'] ) ) {
            return;
        }

        if ( $this->wp->post_has_children( $post['id'], $post['post_type'] ) ) {
            $this->record( 'global', null, null, null, 'parent_deleted' );

            return;
        }

        if ( 'publish' === $post['status'] ) {
            $this->record( 'delete', 'post', $post['id'], $this->wp->permalink( $post['id'] ), 'post_deleted' );
        }

        foreach ( PostLists::for_post( $this->wp, $post ) as $list ) {
            $this->record( 'list', $list['type'], $list['id'], $list['url'], 'post_deleted' );
        }
    }

    /**
     * Terms of an object are set. Record the lists of terms that the post left.
     *
     * @param int    $object_id  Object ID.
     * @param array  $terms      Terms.
     * @param int[]  $tt_ids     New term taxonomy IDs.
     * @param string $taxonomy   Taxonomy.
     * @param bool   $append     Append mode.
     * @param int[]  $old_tt_ids Old term taxonomy IDs.
     *
     * @return void
     */
    public function on_set_object_terms( $object_id, $terms, $tt_ids, $taxonomy, $append, $old_tt_ids ) {
        if ( ! in_array( $taxonomy, $this->wp->public_taxonomies(), true ) ) {
            return;
        }

        $post = $this->wp->post( $object_id );

        if ( null === $post || 'publish' !== $post['status'] || ! $this->wp->object_in_taxonomy( $post['post_type'], $taxonomy ) ) {
            return;
        }

        foreach ( array_diff( array_map( 'intval', (array) $old_tt_ids ), array_map( 'intval', (array) $tt_ids ) ) as $tt_id ) {
            $term = $this->wp->term_by_tt_id( $tt_id, $taxonomy );

            if ( null !== $term ) {
                $this->record( 'list', 'term', $term['id'], $this->wp->term_link( $term['id'], $taxonomy ), 'term_removed' );
            }
        }
    }

    /**
     * A term is about to be updated. Keep its link to find a slug or parent change.
     *
     * @param int    $term_id  Term ID.
     * @param string $taxonomy Taxonomy.
     *
     * @return void
     */
    public function on_edit_terms( $term_id, $taxonomy ) {
        if ( in_array( $taxonomy, $this->wp->public_taxonomies(), true ) ) {
            $this->term_links[ (int) $term_id ] = $this->wp->term_link( $term_id, $taxonomy );
        }
    }

    /**
     * A term is updated.
     *
     * @param int    $term_id  Term ID.
     * @param int    $tt_id    Term taxonomy ID.
     * @param string $taxonomy Taxonomy.
     *
     * @return void
     */
    public function on_edited_term( $term_id, $tt_id, $taxonomy ) {
        if ( ! in_array( $taxonomy, $this->wp->public_taxonomies(), true ) ) {
            return;
        }

        $link     = $this->wp->term_link( $term_id, $taxonomy );
        $old_link = $this->term_links[ (int) $term_id ] ?? null;

        if ( null !== $old_link && $old_link !== $link ) {
            if ( $this->wp->term_has_children( $term_id, $taxonomy ) ) {
                $this->record( 'global', null, null, null, 'parent_term_link_changed' );

                return;
            }

            $this->record( 'delete', 'term', $term_id, $old_link, 'term_link_changed' );
        }

        $this->record( 'url', 'term', $term_id, $link, 'term_edited' );
    }

    /**
     * A term is about to be deleted.
     *
     * @param int    $term_id  Term ID.
     * @param string $taxonomy Taxonomy.
     *
     * @return void
     */
    public function on_pre_delete_term( $term_id, $taxonomy ) {
        if ( ! in_array( $taxonomy, $this->wp->public_taxonomies(), true ) ) {
            return;
        }

        if ( $this->wp->term_has_children( $term_id, $taxonomy ) ) {
            $this->record( 'global', null, null, null, 'parent_term_deleted' );

            return;
        }

        $this->record( 'delete', 'term', $term_id, $this->wp->term_link( $term_id, $taxonomy ), 'term_deleted' );
    }

    /**
     * A term is deleted. The posts of the term show other terms now.
     *
     * @param int    $term_id      Term ID.
     * @param int    $tt_id        Term taxonomy ID.
     * @param string $taxonomy     Taxonomy.
     * @param mixed  $deleted_term Deleted term.
     * @param int[]  $object_ids   Object IDs of the term.
     *
     * @return void
     */
    public function on_delete_term( $term_id, $tt_id, $taxonomy, $deleted_term, $object_ids ) {
        if ( ! in_array( $taxonomy, $this->wp->public_taxonomies(), true ) ) {
            return;
        }

        $object_ids = (array) $object_ids;

        if ( count( $object_ids ) > self::MAX_TERM_OBJECTS ) {
            $this->record( 'global', null, null, null, 'large_term_deleted' );

            return;
        }

        foreach ( $object_ids as $object_id ) {
            $this->record_post( $object_id, 'term_deleted' );
        }
    }

    /**
     * A comment status changes. Only approved comments show on the page.
     *
     * @param string $new_status New status.
     * @param string $old_status Old status.
     * @param object $comment    Comment.
     *
     * @return void
     */
    public function on_transition_comment_status( $new_status, $old_status, $comment ) {
        if ( is_object( $comment ) && in_array( 'approved', [ $new_status, $old_status ], true ) ) {
            $this->record_post( (int) $comment->comment_post_ID, 'comment' );
        }
    }

    /**
     * A new comment is added.
     *
     * @param int        $comment_id Comment ID.
     * @param int|string $approved   1, 0, "spam" or "trash".
     *
     * @return void
     */
    public function on_comment_post( $comment_id, $approved ) {
        if ( 1 === (int) $approved ) {
            $this->on_edit_comment( $comment_id );
        }
    }

    /**
     * A comment is edited.
     *
     * @param int $comment_id Comment ID.
     *
     * @return void
     */
    public function on_edit_comment( $comment_id ) {
        $comment = $this->wp->comment( $comment_id );

        if ( null !== $comment && '1' === $comment['approved'] ) {
            $this->record_post( $comment['post_id'], 'comment' );
        }
    }

    /**
     * An attachment is updated.
     *
     * @param int $attachment_id Attachment ID.
     *
     * @return void
     */
    public function on_attachment_updated( $attachment_id ) {
        $this->record_attachment( 'url', $attachment_id, $this->wp->attachment_metadata( $attachment_id ), 'attachment_updated' );
    }

    /**
     * The attachment metadata is saved, for example after the file is replaced. This is a filter.
     *
     * @param mixed $metadata      New metadata.
     * @param int   $attachment_id Attachment ID.
     *
     * @return mixed The metadata, unchanged.
     */
    public function on_attachment_metadata( $metadata, $attachment_id ) {
        if ( is_array( $metadata ) ) {
            $this->record_attachment( 'url', $attachment_id, $metadata, 'attachment_file' );
        }

        return $metadata;
    }

    /**
     * An attachment is about to be deleted.
     *
     * @param int $attachment_id Attachment ID.
     *
     * @return void
     */
    public function on_delete_attachment( $attachment_id ) {
        $this->record_attachment( 'delete', $attachment_id, $this->wp->attachment_metadata( $attachment_id ), 'attachment_deleted' );
    }

    /**
     * An option is added, updated or deleted.
     *
     * @param string $option Option name.
     *
     * @return void
     */
    public function on_option( $option ) {
        $global = in_array( $option, self::GLOBAL_OPTIONS, true );

        foreach ( self::GLOBAL_OPTION_PREFIXES as $prefix ) {
            $global = $global || 0 === strpos( $option, $prefix );
        }

        if ( $global ) {
            $this->record( 'global', null, null, null, 'option:' . $option );
        }
    }

    /**
     * URLs of an attachment file: the original file, each image size and the original image before scaling.
     *
     * @param string|null $file_url URL of the attached file.
     * @param array       $metadata Attachment metadata.
     *
     * @return string[]
     */
    public static function attachment_urls( $file_url, array $metadata ) {
        if ( ! $file_url ) {
            return [];
        }

        $base  = substr( $file_url, 0, strrpos( $file_url, '/' ) + 1 );
        $files = array_column( (array) ( $metadata['sizes'] ?? [] ), 'file' );
        $urls  = [ $file_url ];

        if ( ! empty( $metadata['original_image'] ) ) {
            $files[] = $metadata['original_image'];
        }

        foreach ( $files as $file ) {
            if ( is_string( $file ) && '' !== $file ) {
                $urls[] = $base . $file;
            }
        }

        return array_values( array_unique( $urls ) );
    }

    /**
     * @param string $kind          url or delete.
     * @param int    $attachment_id Attachment ID.
     * @param array  $metadata      Attachment metadata.
     * @param string $reason        Reason.
     *
     * @return void
     */
    private function record_attachment( $kind, $attachment_id, array $metadata, $reason ) {
        foreach ( self::attachment_urls( $this->wp->attachment_file_url( $attachment_id ), $metadata ) as $url ) {
            $this->record( $kind, null, null, $url, $reason );
        }
    }

    /**
     * Record the permalink of a published post of a public type.
     *
     * @param int    $post_id Post ID.
     * @param string $reason  Reason.
     *
     * @return void
     */
    private function record_post( $post_id, $reason ) {
        $post = $this->wp->post( $post_id );

        if ( null === $post || 'publish' !== $post['status'] || ! $this->is_public_type( $post['post_type'] ) ) {
            return;
        }

        $this->record( 'url', 'post', $post['id'], $this->wp->permalink( $post['id'] ), $reason );
    }

    /**
     * @param string $post_type Post type.
     *
     * @return bool
     */
    private function is_public_type( $post_type ) {
        return in_array( $post_type, $this->wp->public_post_types(), true );
    }
}
