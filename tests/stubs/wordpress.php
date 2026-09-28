<?php
/**
 * Just enough of WordPress for the unit tests of the hook classes. The suite loads no WordPress:
 * each function returns its input, and `flywp()` answers with the brand a test sets.
 */

if ( ! defined( 'FLYWP_PLUGIN_BASENAME' ) ) {
    define( 'FLYWP_PLUGIN_BASENAME', 'flywp/flywp.php' );
}

if ( ! function_exists( '__' ) ) {
    function __( $text, $domain = 'default' ) {
        return $text;
    }
}

if ( ! function_exists( 'esc_url' ) ) {
    function esc_url( $url ) {
        return $url;
    }
}

if ( ! function_exists( 'add_filter' ) ) {
    function add_filter( $hook, $callback, $priority = 10, $args = 1 ) {
        $GLOBALS['flywp_test_filters'][ $hook ] = $args;

        return true;
    }
}

if ( ! function_exists( 'flywp' ) ) {
    function flywp() {
        return $GLOBALS['flywp_test_plugin'];
    }
}
