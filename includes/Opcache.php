<?php

namespace FlyWP;

class Opcache {

    public static function is_enabled() {
        // Check if OPcache is enabled
        if ( ! function_exists( 'opcache_get_status' ) ) {
            return false;
        }

        $status = opcache_get_status();

        return $status['opcache_enabled'] === true;
    }
}
