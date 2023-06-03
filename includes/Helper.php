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
}
