<?php

namespace FlyWP\StaticSite\Engine\Discovery;

/**
 * All WordPress data that discovery and the change log read.
 *
 * The URL rules and the hook handlers use only this interface. Thus they run in PHPUnit without WordPress.
 *
 * @since 1.8.0
 */
interface WordPressGateway {

    /**
     * @return string Home URL with a trailing slash.
     */
    public function home_url();

    /**
     * @param string $name Option name.
     *
     * @return mixed
     */
    public function option( $name );

    /**
     * @param string $hook  Filter name.
     * @param mixed  $value Value to filter.
     * @param mixed  $arg   Extra argument.
     *
     * @return mixed
     */
    public function apply_filters( $hook, $value, $arg = null );

    /**
     * @return bool True during an import (WP_IMPORTING) or an install.
     */
    public function is_bulk_request();

    /**
     * @return string[] Public post types, without attachment.
     */
    public function public_post_types();

    /**
     * @return string[] Public taxonomies.
     */
    public function public_taxonomies();

    /**
     * Published posts with an ID higher than $after_id, in ID order.
     *
     * @param string[] $post_types Post types.
     * @param int      $after_id   Last ID of the previous batch.
     * @param int      $limit      Batch size.
     *
     * @return array<int, array{id:int, post_type:string, password:string, modified_gmt:string}>
     */
    public function post_rows( array $post_types, $after_id, $limit );

    /**
     * SEO robots meta rows of the posts. Only for SEO plugins that are active.
     *
     * @param int[] $post_ids Post IDs.
     *
     * @return array<int, array{post_id:int, meta_key:string, meta_value:string}>
     */
    public function seo_robots_meta( array $post_ids );

    /**
     * Yoast SEO term meta option (wpseo_taxonomy_meta). Empty when Yoast SEO is not active.
     *
     * @return array<string, array<int, array<string, string>>>
     */
    public function seo_term_meta_option();

    /**
     * Rank Math term robots meta with a term ID higher than $after_id, in ID order. Empty when Rank Math is not active.
     *
     * @param int $after_id Last term ID of the previous batch.
     * @param int $limit    Batch size.
     *
     * @return array<int, array{term_id:int, taxonomy:string, meta_value:string}>
     */
    public function seo_term_robots_meta( $after_id, $limit );

    /**
     * @param int $post_id Post ID.
     *
     * @return array{id:int, post_type:string, status:string, password:string, author:int, date:string, modified_gmt:string, pages:int}|null
     *         "pages" is the number of <!--nextpage--> pages.
     */
    public function post( $post_id );

    /**
     * @param int $post_id Post ID.
     *
     * @return string|null
     */
    public function permalink( $post_id );

    /**
     * Permalink of a post object as it is, for example the object before an update.
     *
     * @param object $post Post object.
     *
     * @return string|null
     */
    public function object_permalink( $post );

    /**
     * @param string $permalink Post permalink.
     * @param int    $page      Page number of a <!--nextpage--> post.
     *
     * @return string
     */
    public function post_page_url( $permalink, $page );

    /**
     * @param int    $post_id   Post ID.
     * @param string $post_type Post type.
     *
     * @return bool True when the post type is hierarchical and the post has published children.
     */
    public function post_has_children( $post_id, $post_type );

    /**
     * @param string $post_type Post type.
     *
     * @return int Number of published posts.
     */
    public function published_count( $post_type );

    /**
     * @param string[] $post_types Post types.
     *
     * @return string|null Newest post_modified_gmt of published posts.
     */
    public function newest_modified_gmt( array $post_types );

    /**
     * @param string $post_type Post type.
     *
     * @return string|null
     */
    public function post_type_archive_link( $post_type );

    /**
     * Terms that have posts or child terms, with an ID higher than $after_id, in ID order.
     *
     * @param string $taxonomy Taxonomy.
     * @param int    $after_id Last ID of the previous batch.
     * @param int    $limit    Batch size.
     *
     * @return array<int, array{id:int, taxonomy:string, count:int}>
     */
    public function term_rows( $taxonomy, $after_id, $limit );

    /**
     * @param int $term_id Term ID.
     *
     * @return array{id:int, taxonomy:string, count:int}|null
     */
    public function term( $term_id );

    /**
     * @param int    $tt_id    Term taxonomy ID.
     * @param string $taxonomy Taxonomy.
     *
     * @return array{id:int, taxonomy:string, count:int}|null
     */
    public function term_by_tt_id( $tt_id, $taxonomy );

    /**
     * Terms of the post in all public taxonomies.
     *
     * @param int $post_id Post ID.
     *
     * @return array<int, array{id:int, taxonomy:string, count:int}>
     */
    public function post_terms( $post_id );

    /**
     * @param int    $term_id  Term ID.
     * @param string $taxonomy Taxonomy.
     *
     * @return string|null
     */
    public function term_link( $term_id, $taxonomy );

    /**
     * @param int    $term_id  Term ID.
     * @param string $taxonomy Taxonomy.
     *
     * @return bool True when the taxonomy is hierarchical and the term has child terms.
     */
    public function term_has_children( $term_id, $taxonomy );

    /**
     * @param string $post_type Post type.
     * @param string $taxonomy  Taxonomy.
     *
     * @return bool
     */
    public function object_in_taxonomy( $post_type, $taxonomy );

    /**
     * Authors that have published posts of the type "post", with a user ID higher than $after_id, in ID order.
     *
     * @param int $after_id Last user ID of the previous batch.
     * @param int $limit    Batch size.
     *
     * @return array<int, array{id:int, count:int, modified_gmt:string}>
     */
    public function author_rows( $after_id, $limit );

    /**
     * @param int $user_id User ID.
     *
     * @return int Published posts of the type "post".
     */
    public function author_post_count( $user_id );

    /**
     * @param int $user_id User ID.
     *
     * @return string|null
     */
    public function author_link( $user_id );

    /**
     * Months with published posts of the type "post", newest first, older than $before_key.
     *
     * @param int $before_key Year * 100 + month of the last row of the previous batch. 0 for the first batch.
     * @param int $limit      Batch size.
     *
     * @return array<int, array{year:int, month:int}>
     */
    public function month_rows( $before_key, $limit );

    /**
     * @param int $year  Year.
     * @param int $month Month, or 0 for the full year.
     *
     * @return int Published posts of the type "post" in the period.
     */
    public function date_post_count( $year, $month );

    /**
     * @param int $year Year.
     *
     * @return string
     */
    public function year_link( $year );

    /**
     * @param int $year  Year.
     * @param int $month Month.
     *
     * @return string
     */
    public function month_link( $year, $month );

    /**
     * @param string $feed Empty for the main feed, "comments_" for the comments feed.
     *
     * @return string
     */
    public function feed_link( $feed = '' );

    /**
     * @param string $base_url Page 1 URL.
     * @param int    $page     Page number.
     *
     * @return string
     */
    public function paged_url( $base_url, $page );

    /**
     * @param int $comment_id Comment ID.
     *
     * @return array{post_id:int, approved:string}|null
     */
    public function comment( $comment_id );

    /**
     * @param int  $post_id        Post ID.
     * @param bool $top_level_only Count only comments without a parent.
     *
     * @return int
     */
    public function approved_comment_count( $post_id, $top_level_only );

    /**
     * @param string $permalink Post permalink.
     * @param int    $page      Comment page number.
     *
     * @return string
     */
    public function comment_page_url( $permalink, $page );

    /**
     * @param int $attachment_id Attachment ID.
     *
     * @return string|null URL of the attached file.
     */
    public function attachment_file_url( $attachment_id );

    /**
     * @param int $attachment_id Attachment ID.
     *
     * @return array Attachment metadata ("sizes", "original_image").
     */
    public function attachment_metadata( $attachment_id );

    /**
     * Sitemap index, sub-sitemaps and stylesheets that WordPress or an active SEO plugin serves.
     *
     * @return string[]
     */
    public function sitemap_urls();

    /**
     * @return string[]
     */
    public function site_icon_urls();

    /**
     * @return string[] style.css of the active theme and of its parent theme.
     */
    public function theme_stylesheet_urls();
}
