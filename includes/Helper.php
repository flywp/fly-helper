<?php

namespace FlyWP;

class Helper {

    /**
     * Print_r with pre tags.
     *
     * @param mixed $var
     */
    public static function print_r( $var = null ) {
        echo '<pre>';
        print_r( $var );
        echo '</pre>';
    }

    /**
     * Var_dump and die.
     *
     * @param mixed $var
     */
    public static function dd( $var = null ) {
        self::print_r( $var );
        die();
    }

    /**
     * Check if server is running Nginx.
     *
     * @return bool
     */
    public static function is_nginx() {
        return isset( $_SERVER['SERVER_SOFTWARE'] ) && false !== strpos( wp_unslash( $_SERVER['SERVER_SOFTWARE'] ), 'nginx' );
    }

    /**
     * Check if server is running LiteSpeed.
     *
     * @return bool
     */
    public static function is_litespeed() {
        return isset( $_SERVER['SERVER_SOFTWARE'] ) && false !== strpos( wp_unslash( $_SERVER['SERVER_SOFTWARE'] ), 'LiteSpeed' );
    }
}
