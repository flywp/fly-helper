<?php

namespace FlyWP\StaticSite\Engine\Discovery;

/**
 * Moves WordPress links from the home URL to the origin URL that the engine fetches.
 *
 * WordPress builds links with home_url(). The build can fetch another origin (--origin-url).
 * Links on other hosts are not part of the site, so rebase() returns null for them.
 *
 * @since 1.8.0
 */
class OriginUrl {

    /**
     * Home URL without scheme and trailing slash.
     *
     * @var string
     */
    private $home;

    /**
     * Origin URL without trailing slash.
     *
     * @var string
     */
    private $origin;

    /**
     * @param string      $home_url   WordPress home URL.
     * @param string|null $origin_url Origin URL. Null or empty to use the home URL.
     */
    public function __construct( $home_url, $origin_url = null ) {
        $this->home   = self::without_scheme( rtrim( $home_url, '/' ) );
        $this->origin = rtrim( $origin_url ? $origin_url : $home_url, '/' );
    }

    /**
     * @param string $url Absolute URL.
     *
     * @return string|null URL on the origin without fragment, or null when the URL is not on the site.
     */
    public function rebase( $url ) {
        $url      = (string) $url;
        $fragment = strpos( $url, '#' );

        if ( false !== $fragment ) {
            $url = substr( $url, 0, $fragment );
        }

        $bare = self::without_scheme( $url );

        foreach ( [ self::without_scheme( $this->origin ), $this->home ] as $base ) {
            if ( '' === $base || 0 !== stripos( $bare, $base ) ) {
                continue;
            }

            $rest = (string) substr( $bare, strlen( $base ) );

            if ( '' === $rest ) {
                return $this->origin . '/';
            }

            if ( '/' === $rest[0] || '?' === $rest[0] ) {
                return $this->origin . $rest;
            }
        }

        return null;
    }

    /**
     * @param string $url URL.
     *
     * @return string
     */
    private static function without_scheme( $url ) {
        return (string) preg_replace( '#^([a-z][a-z0-9+.-]*:)?//#i', '', $url );
    }
}
