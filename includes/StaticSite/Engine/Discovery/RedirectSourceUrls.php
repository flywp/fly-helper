<?php

namespace FlyWP\StaticSite\Engine\Discovery;

/**
 * Source URLs of redirect plugins. The crawler fetches them, the origin answers with a 3xx,
 * and the engine writes the rule.
 *
 * - Plugins: Redirection, Rank Math, Yoast SEO Premium, Safe Redirect Manager and Simple 301 Redirects.
 * - One class that returns absolute URLs on the origin.
 * - Absolute sources on a site host (with or without "www.", http or https) move to the origin scheme and host.
 * - No Rank Math row limit. The _redirects limits apply later.
 * - Simple 301 wildcard sources become dynamic rules only when its wildcard option is on.
 * - `wp flywp static site` writes the result to site.json.
 *
 * - A plugin is read only when its classes or functions exist (the plugin is active).
 * - Regex rules and sources with "*" are not crawled.
 *
 * @since 1.8.0
 */
class RedirectSourceUrls {

    const RANK_MATH_PAGE_SIZE = 500;

    const CODES = [ 301, 302, 307, 308 ];

    /**
     * Absolute source URLs on the origin.
     *
     * @param string   $origin_url Origin URL.
     * @param string[] $site_urls  Other URLs of the site (home URL, site URL).
     *
     * @return string[]
     */
    public static function all( $origin_url, array $site_urls = [] ) {
        $sources = array_merge(
            self::redirection(),
            self::rank_math(),
            self::yoast_premium(),
            self::safe_redirect_manager(),
            self::simple_301_redirects()
        );

        $urls = [];

        foreach ( $sources as $source ) {
            $url = self::to_origin( (string) $source, $origin_url, $site_urls );

            if ( $url !== null && strpos( $url, '*' ) === false ) {
                $urls[ $url ] = true;
            }
        }

        return array_keys( $urls );
    }

    /**
     * Simple 301 Redirects wildcard rules, when its wildcard option is on.
     *
     * @param string   $origin_url Origin URL.
     * @param string[] $site_urls  Other URLs of the site.
     *
     * @return array<int, array{from: string, to: string, status: int}>
     */
    public static function wildcard_rules( $origin_url, array $site_urls = [] ) {
        if ( ! class_exists( 'Simple301Redirects' ) || get_option( '301_redirects_wildcard' ) !== 'true' ) {
            return [];
        }

        $rules = [];

        foreach ( (array) get_option( '301_redirects' ) as $from => $to ) {
            $from_url = self::to_origin( (string) $from, $origin_url, $site_urls );

            if ( $from_url === null || strpos( $from_url, '*' ) === false ) {
                continue;
            }

            $to_url  = self::to_origin( (string) $to, $origin_url, $site_urls );
            $rules[] = [
                'from'   => $from_url,
                'to'     => $to_url !== null ? $to_url : (string) $to,
                'status' => 301,
            ];
        }

        return $rules;
    }

    /**
     * Move a source onto the origin URL.
     *
     * @param string   $source     Relative path or absolute URL.
     * @param string   $origin_url Origin URL.
     * @param string[] $site_urls  Other URLs of the site.
     *
     * @return string|null Null for an empty source or a source on another host.
     */
    public static function to_origin( $source, $origin_url, array $site_urls ) {
        $source = trim( $source );

        if ( $source === '' ) {
            return null;
        }

        // Plugins store site-root paths ("/blog/old-page" on a site in "/blog"), so resolve them like a browser.
        if ( ! Url::is_http( $source ) ) {
            return Url::resolve( rtrim( $origin_url, '/' ) . '/', $source );
        }

        $hosts = [];

        foreach ( array_merge( [ $origin_url ], $site_urls ) as $url ) {
            $hosts[ self::bare_host( $url ) ] = true;
        }

        if ( ! isset( $hosts[ self::bare_host( $source ) ] ) ) {
            return null;
        }

        $parts  = Url::parts( $source );
        $origin = Url::parts( $origin_url );

        $parts['scheme'] = $origin['scheme'];
        $parts['host']   = $origin['host'];
        $parts['port']   = $origin['port'];
        $parts['user']   = '';
        $parts['pass']   = '';

        return Url::build( $parts );
    }

    /**
     * Host without "www.".
     *
     * @param string $url URL.
     *
     * @return string
     */
    private static function bare_host( $url ) {
        return (string) preg_replace( '/^www\./', '', Url::parts( $url )['host'] );
    }

    /**
     * Redirection plugin.
     *
     * @return string[]
     */
    private static function redirection() {
        if ( ! class_exists( 'Red_Item' ) || ! method_exists( 'Red_Item', 'get_all' ) ) {
            return [];
        }

        $urls = [];

        foreach ( \Red_Item::get_all() as $item ) {
            if ( ! $item->is_enabled() || $item->is_dynamic() || $item->is_regex() ) {
                continue;
            }

            if ( $item->get_match_type() !== 'url' || $item->get_action_type() !== 'url' ) {
                continue;
            }

            $urls[] = $item->get_url();
        }

        return $urls;
    }

    /**
     * Rank Math, all active rules, page by page.
     *
     * @return string[]
     */
    private static function rank_math() {
        if ( ! class_exists( '\RankMath\Redirections\DB' ) ) {
            return [];
        }

        $urls     = [];
        $first_id = null;

        for ( $page = 1; ; $page++ ) {
            $result    = \RankMath\Redirections\DB::get_redirections(
                [
                    'status' => 'active',
                    'limit'  => self::RANK_MATH_PAGE_SIZE,
                    'paged'  => $page,
                ]
            );
            $redirects = isset( $result['redirections'] ) ? array_values( (array) $result['redirections'] ) : [];
            $page_id   = isset( $redirects[0]['id'] ) ? $redirects[0]['id'] : null;

            // Stop when the plugin ignores "paged" and gives the first page again.
            if ( ! $redirects || ( $page > 1 && $page_id === $first_id ) ) {
                break;
            }

            $first_id = $page === 1 ? $page_id : $first_id;

            foreach ( $redirects as $redirect ) {
                if ( ! in_array( (int) $redirect['header_code'], self::CODES, true ) ) {
                    continue;
                }

                foreach ( (array) maybe_unserialize( $redirect['sources'] ) as $source ) {
                    if ( isset( $source['comparison'], $source['pattern'] ) && $source['comparison'] === 'exact' ) {
                        $urls[] = $source['pattern'];
                    }
                }
            }

            if ( count( $redirects ) < self::RANK_MATH_PAGE_SIZE ) {
                break;
            }
        }

        return $urls;
    }

    /**
     * Yoast SEO Premium.
     *
     * @return string[]
     */
    private static function yoast_premium() {
        if ( ! class_exists( 'WPSEO_Redirect_Manager' ) ) {
            return [];
        }

        $urls = [];

        foreach ( (array) get_option( 'wpseo-premium-redirects-base' ) as $item ) {
            if ( is_array( $item ) && isset( $item['format'], $item['type'], $item['origin'] ) && $item['format'] === 'plain' && in_array( (int) $item['type'], self::CODES, true ) ) {
                $urls[] = $item['origin'];
            }
        }

        return $urls;
    }

    /**
     * Safe Redirect Manager.
     *
     * @return string[]
     */
    private static function safe_redirect_manager() {
        if ( ! class_exists( 'SRM_Redirect' ) || ! function_exists( 'srm_get_redirects' ) || ! function_exists( 'srm_get_max_redirects' ) ) {
            return [];
        }

        $redirects = srm_get_redirects(
            [
                'posts_per_page' => srm_get_max_redirects(),
                'post_status'    => 'publish',
            ]
        );
        $urls      = [];

        foreach ( (array) $redirects as $item ) {
            if ( empty( $item['enable_regex'] ) && in_array( (int) $item['status_code'], self::CODES, true ) ) {
                $urls[] = $item['redirect_from'];
            }
        }

        return $urls;
    }

    /**
     * Simple 301 Redirects. Sources with "*" are removed in all().
     *
     * @return string[]
     */
    private static function simple_301_redirects() {
        if ( ! class_exists( 'Simple301Redirects' ) ) {
            return [];
        }

        $redirects = get_option( '301_redirects' );

        return is_array( $redirects ) ? array_map( 'strval', array_keys( $redirects ) ) : [];
    }
}
