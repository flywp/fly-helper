<?php

namespace FlyWP;

/**
 * Resolves a FlyWP key that may never have reached a PHP constant.
 *
 * Classic WordPress writes these into `wp-config.php`, so the constant is always set and
 * nothing here does any work. Bedrock has no `wp-config.php`: FlyWP writes the value into
 * `.env`, and the constant exists only when a config file declares it with `Config::define()`.
 * `flywp/bedrock-starter` declares it; a repository a customer built from upstream
 * `roots/bedrock` does not. On those sites the key is present and correct in the environment
 * while the constant is empty, which used to switch the whole plugin off.
 *
 * All three environment sources are read because the loaders disagree on which they fill.
 * `flywp/bedrock-starter` builds its Dotenv repository from `EnvConstAdapter` and
 * `PutenvAdapter`, which populates `getenv()` and `$_ENV` but not `$_SERVER`. A repository
 * using `createImmutable`, or a host with `putenv()` disabled, populates a different subset.
 *
 * This is deliberately free of WordPress functions so it can be unit tested without booting
 * WordPress, which is why `$_SERVER` is unslashed with `stripslashes()` rather than
 * `wp_unslash()`. The two are the same operation for a string, and the unslashing is required:
 * `wp_magic_quotes()` runs `add_magic_quotes()` over `$_GET`, `$_POST`, `$_COOKIE` **and
 * `$_SERVER`** before any plugin loads. A key containing a quote or a backslash would otherwise
 * arrive here escaped while the bearer token it is compared against is unslashed by
 * {@see \FlyWP\Api::get_bearer_token()}, and `hash_equals()` would never match.
 *
 * `$_ENV` is the one superglobal `wp_magic_quotes()` leaves alone, so it is read as it stands.
 */
class KeyResolver {

    /**
     * Resolve a key from its constant, falling back to the environment.
     *
     * @param string $constant_value Value of the matching constant, '' when undeclared.
     * @param string $name           Environment variable to fall back to.
     *
     * @return string The key, or '' when no source carries one.
     */
    public static function resolve( $constant_value, $name ) {
        if ( is_string( $constant_value ) && $constant_value !== '' ) {
            return $constant_value;
        }

        $candidates = [
            getenv( $name ),
            isset( $_ENV[ $name ] ) ? $_ENV[ $name ] : false,
            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized,WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- The value IS unslashed, with stripslashes() rather than the wp_unslash() the sniff looks for, because this class stays free of WordPress functions so it can be unit tested. The two are identical for a string. It is not sanitized because it is a credential compared with hash_equals(), and sanitizing would alter it.
            isset( $_SERVER[ $name ] ) && is_string( $_SERVER[ $name ] ) ? stripslashes( $_SERVER[ $name ] ) : false,
        ];

        foreach ( $candidates as $candidate ) {
            if ( is_string( $candidate ) && $candidate !== '' ) {
                return $candidate;
            }
        }

        return '';
    }
}
