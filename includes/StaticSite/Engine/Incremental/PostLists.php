<?php

namespace FlyWP\StaticSite\Engine\Incremental;

use FlyWP\StaticSite\Engine\Discovery\HomeSource;
use FlyWP\StaticSite\Engine\Discovery\WordPressGateway;

/**
 * The list pages that show a post: term archives, and the posts page, author and date archives (type "post")
 * or the post type archive (other types).
 *
 * The change log records these lists before a hard delete. The builder uses the same lists for a post that
 * still exists. Thus a hard-deleted post and a trashed post rebuild the same lists.
 *
 * @since 1.8.0
 */
class PostLists {

    /**
     * @param WordPressGateway                                       $wp   Gateway.
     * @param array{id:int, post_type:string, author:int, date:string} $post Post.
     *
     * @return array<int, array{type:string, id:int|null, url:string|null}>
     *         type: term (id = term ID), blog, user (id = user ID), date (id = year or year * 100 + month), archive.
     */
    public static function for_post( WordPressGateway $wp, array $post ) {
        $lists = [];

        foreach ( $wp->post_terms( $post['id'] ) as $term ) {
            $lists[] = [
				'type' => 'term',
				'id' => $term['id'],
				'url' => $wp->term_link( $term['id'], $term['taxonomy'] ),
			];
        }

        if ( 'post' !== $post['post_type'] ) {
            $lists[] = [
				'type' => 'archive',
				'id' => null,
				'url' => $wp->post_type_archive_link( $post['post_type'] ),
			];

            return $lists;
        }

        $lists[] = [
			'type' => 'blog',
			'id' => null,
			'url' => HomeSource::blog_url( $wp ),
		];
        $lists[] = [
			'type' => 'user',
			'id' => (int) $post['author'],
			'url' => $wp->author_link( $post['author'] ),
		];

        if ( preg_match( '/^(\d{4})-(\d{2})/', (string) $post['date'], $date ) ) {
            $year  = (int) $date[1];
            $month = (int) $date[2];

            $lists[] = [
				'type' => 'date',
				'id' => $year,
				'url' => $wp->year_link( $year ),
			];
            $lists[] = [
				'type' => 'date',
				'id' => $year * 100 + $month,
				'url' => $wp->month_link( $year, $month ),
			];
        }

        return $lists;
    }
}
