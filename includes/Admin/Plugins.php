<?php

namespace FlyWP\Admin;

class Plugins {

    /**
     * Class constructor.
     */
    public function __construct() {
        add_filter( 'plugin_action_links_' . FLYWP_PLUGIN_BASENAME, [ $this, 'links' ] );
    }

    /**
     * Add plugin action links.
     *
     * @param array $links
     *
     * @return array
     */
    public function links( $links ) {
        $links[] = sprintf(
            '<a href="%s">%s</a>',
            flywp()->admin->page_url(),
            __( 'Settings', 'flywp' )
        );

        return $links;
    }
}
