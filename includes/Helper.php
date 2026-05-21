<?php

namespace FlyWP;

class Helper {

    /**
     * Print_r with pre tags.
     *
     * @param mixed $data
     */
    public static function print_r( $data = null ) {
        echo '<pre>';
        print_r( $data );
        echo '</pre>';
    }

    /**
     * Var_dump and die.
     *
     * @param mixed $data
     */
    public static function dd( $data = null ) {
        self::print_r( $data );
        die();
    }

    /**
     * Check if server is running Nginx.
     *
     * @return bool
     */
    public static function is_nginx() {
        return isset( $_SERVER['SERVER_SOFTWARE'] ) && false !== strpos( sanitize_text_field( wp_unslash( $_SERVER['SERVER_SOFTWARE'] ) ), 'nginx' );
    }

    /**
     * Check if server is running LiteSpeed.
     *
     * @return bool
     */
    public static function is_litespeed() {
        return isset( $_SERVER['SERVER_SOFTWARE'] ) && false !== strpos( sanitize_text_field( wp_unslash( $_SERVER['SERVER_SOFTWARE'] ) ), 'LiteSpeed' );
    }
}
