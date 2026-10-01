<?php

namespace FlyWP\StaticSite\Engine\Discovery;

/**
 * Finds posts and terms that an SEO plugin marks as "noindex".
 *
 * @since 1.8.0
 */
class RobotsMeta {

    /**
     * Yoast SEO post meta: value "1" is noindex.
     */
    const YOAST_NOINDEX = '_yoast_wpseo_meta-robots-noindex';

    /**
     * Rank Math post and term meta: serialized list that can contain "noindex".
     */
    const RANK_MATH_ROBOTS = 'rank_math_robots';

    /**
     * @param array<int, array{post_id:int, meta_key:string, meta_value:string}> $rows Post meta rows.
     *
     * @return array<int, bool> Post ID => true for each noindex post.
     */
    public static function noindex_post_ids( array $rows ) {
        $ids = [];

        foreach ( $rows as $row ) {
            $value     = (string) $row['meta_value'];
            $yoast     = self::YOAST_NOINDEX === $row['meta_key'] && '1' === $value;
            $rank_math = self::RANK_MATH_ROBOTS === $row['meta_key'] && self::is_rank_math_noindex( $value );

            if ( $yoast || $rank_math ) {
                $ids[ (int) $row['post_id'] ] = true;
            }
        }

        return $ids;
    }

    /**
     * Terms with "wpseo_noindex" = "noindex" in the Yoast SEO option wpseo_taxonomy_meta.
     *
     * @param array $option Option value: taxonomy => term ID => meta.
     *
     * @return array<int, array{id:int, taxonomy:string}>
     */
    public static function yoast_noindex_terms( array $option ) {
        $terms = [];

        foreach ( $option as $taxonomy => $term_meta ) {
            foreach ( (array) $term_meta as $term_id => $meta ) {
                if ( is_array( $meta ) && 'noindex' === ( $meta['wpseo_noindex'] ?? '' ) ) {
                    $terms[] = [
                        'id'       => (int) $term_id,
                        'taxonomy' => (string) $taxonomy,
                    ];
                }
            }
        }

        return $terms;
    }

    /**
     * @param string $value Raw rank_math_robots meta value.
     *
     * @return bool
     */
    public static function is_rank_math_noindex( $value ) {
        return false !== strpos( (string) $value, 'noindex' );
    }
}
