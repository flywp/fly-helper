<?php

namespace FlyWP\Api;

class Themes {

    /**
     * API constructor.
     */
    public function __construct() {
        flywp()->router->get( 'themes', [ $this, 'respond' ] );
    }

    /**
     * Handle request.
     *
     * @return void
     */
    public function respond( $args ) {
        $response = [];

        $themes  = wp_get_themes();
        $updates = get_site_transient( 'update_themes' );

        foreach ( $themes as $key => $theme ) {
            $update = $this->get_update( $key, $updates );

            $response[] = [
                'name'                 => $key,
                'version'              => $theme->get( 'Version' ),
                'description'          => $theme->get( 'Description' ),
                'theme_uri'            => $theme->get( 'ThemeURI' ),
                'author'               => $theme->get( 'Author' ),
                'author_uri'           => $theme->get( 'AuthorURI' ),
                'status'               => $this->get_status( $theme ),
                'update_available'     => $update ? true : false,
                'new_version'          => $update ? $update['new_version'] : null,
            ];
        }

        wp_send_json( $response );
    }

    /**
     * Get theme active status.
     *
     * @param string $file
     *
     * @return string
     */
    private function get_status( $theme ) {
        if ( $theme->get_stylesheet_directory() === get_stylesheet_directory() ) {
            return 'active';
        }

        if ( $theme->get_stylesheet_directory() === get_stylesheet_directory() ) {
            return 'parent';
        }

        return 'inactive';
    }

    /**
     * Check if a theme has an update available.
     *
     * @param string $key
     * @param object $updates
     *
     * @return array|bool
     */
    private function get_update( $key, $updates ) {
        if ( isset( $updates->response[ $key ] ) ) {
            return [
                'new_version' => $updates->response[ $key ]['new_version'],
                'package'     => $updates->response[ $key ]['package'],
            ];
        }

        return false;
    }
}
