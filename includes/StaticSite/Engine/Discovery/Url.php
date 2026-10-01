<?php

namespace FlyWP\StaticSite\Engine\Discovery;

/**
 * String URL helpers for the redirect sources. String URLs in and out.
 *
 * @since 1.8.0
 */
class Url {

    /**
     * Parts of a URL. Missing parts are empty strings, a missing port is null.
     *
     * @param string $url URL.
     *
     * @return array{scheme: string, user: string, pass: string, host: string, port: int|null, path: string, query: string, fragment: string}
     */
    public static function parts( $url ) {
        $parts = parse_url( (string) $url );

        if ( ! is_array( $parts ) ) {
            $parts = [];
        }

        return [
            'scheme'   => strtolower( isset( $parts['scheme'] ) ? $parts['scheme'] : '' ),
            'user'     => isset( $parts['user'] ) ? $parts['user'] : '',
            'pass'     => isset( $parts['pass'] ) ? $parts['pass'] : '',
            'host'     => strtolower( isset( $parts['host'] ) ? $parts['host'] : '' ),
            'port'     => isset( $parts['port'] ) ? (int) $parts['port'] : null,
            'path'     => isset( $parts['path'] ) ? $parts['path'] : '',
            'query'    => isset( $parts['query'] ) ? $parts['query'] : '',
            'fragment' => isset( $parts['fragment'] ) ? $parts['fragment'] : '',
        ];
    }

    /**
     * Build a URL from parts.
     *
     * @param array $parts Result of parts().
     *
     * @return string
     */
    public static function build( array $parts ) {
        $url = '';

        if ( $parts['scheme'] !== '' ) {
            $url .= $parts['scheme'] . ':';
        }

        $authority = self::authority( $parts );

        if ( $authority !== '' || $parts['scheme'] === 'http' || $parts['scheme'] === 'https' ) {
            $url .= '//' . $authority;
        }

        $path = $parts['path'];

        if ( $authority !== '' && $path !== '' && $path[0] !== '/' ) {
            $path = '/' . $path;
        }

        $url .= $path;

        if ( $parts['query'] !== '' ) {
            $url .= '?' . $parts['query'];
        }

        if ( $parts['fragment'] !== '' ) {
            $url .= '#' . $parts['fragment'];
        }

        return $url;
    }

    /**
     * Host and port. The default port of the scheme is not included.
     *
     * @param array $parts Result of parts().
     *
     * @return string
     */
    public static function authority( array $parts ) {
        $authority = $parts['host'];

        if ( $parts['user'] !== '' ) {
            $authority = $parts['user'] . ( $parts['pass'] !== '' ? ':' . $parts['pass'] : '' ) . '@' . $authority;
        }

        $port = self::effective_port( $parts );

        if ( $port !== null ) {
            $authority .= ':' . $port;
        }

        return $authority;
    }

    /**
     * Whether a URL is absolute with an http or https scheme and a host.
     *
     * @param string $url URL.
     *
     * @return bool
     */
    public static function is_http( $url ) {
        $parts = self::parts( $url );

        return in_array( $parts['scheme'], [ 'http', 'https' ], true ) && $parts['host'] !== '';
    }

    /**
     * Resolve a reference against a base URL (RFC 3986 section 5.2).
     *
     * @param string $base      Absolute base URL.
     * @param string $reference Reference, for example a Location header.
     *
     * @return string
     */
    public static function resolve( $base, $reference ) {
        $reference = trim( (string) $reference );

        if ( $reference === '' ) {
            return $base;
        }

        $base_parts = self::parts( $base );
        $rel        = self::parts( $reference );

        if ( $rel['scheme'] !== '' ) {
            $rel['path'] = self::remove_dot_segments( $rel['path'] );

            return self::build( $rel );
        }

        $target = $base_parts;

        if ( strpos( $reference, '//' ) === 0 ) {
            $rel['scheme'] = $base_parts['scheme'];
            $rel['path']   = self::remove_dot_segments( $rel['path'] );

            return self::build( $rel );
        }

        if ( $rel['path'] === '' ) {
            $target['query'] = $rel['query'] !== '' ? $rel['query'] : $base_parts['query'];
        } else {
            if ( $rel['path'][0] === '/' ) {
                $path = $rel['path'];
            } elseif ( $base_parts['host'] !== '' && $base_parts['path'] === '' ) {
                $path = '/' . $rel['path'];
            } else {
                $last_slash = strrpos( $base_parts['path'], '/' );
                $path       = $last_slash === false ? $rel['path'] : substr( $base_parts['path'], 0, $last_slash + 1 ) . $rel['path'];
            }

            $target['path']  = self::remove_dot_segments( $path );
            $target['query'] = $rel['query'];
        }

        $target['fragment'] = $rel['fragment'];

        return self::build( $target );
    }

    /**
     * Remove "." and ".." segments from a path.
     *
     * @param string $path URL path.
     *
     * @return string
     */
    public static function remove_dot_segments( $path ) {
        if ( $path === '' || $path === '/' ) {
            return $path;
        }

        $results = [];
        $segment = '';

        foreach ( explode( '/', $path ) as $segment ) {
            if ( $segment === '..' ) {
                array_pop( $results );
            } elseif ( $segment !== '.' ) {
                $results[] = $segment;
            }
        }

        $new_path = implode( '/', $results );

        if ( $path[0] === '/' && ( $new_path === '' || $new_path[0] !== '/' ) ) {
            $new_path = '/' . $new_path;
        } elseif ( $new_path !== '' && ( $segment === '.' || $segment === '..' ) ) {
            $new_path .= '/';
        }

        return $new_path;
    }

    /**
     * Port, or null when it is the default port of the scheme.
     *
     * @param array $parts Result of parts().
     *
     * @return int|null
     */
    private static function effective_port( array $parts ) {
        $defaults = [
            'http'  => 80,
            'https' => 443,
        ];

        if ( $parts['port'] === null ) {
            return null;
        }

        if ( isset( $defaults[ $parts['scheme'] ] ) && $defaults[ $parts['scheme'] ] === $parts['port'] ) {
            return null;
        }

        return $parts['port'];
    }
}
