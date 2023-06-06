<?php

namespace FlyWP;

class Admin {

    /**
     * Admin page slug.
     */
    const PAGE_SLUG = 'flywp';

    public $fastcgi = null;

    /**
     * Admin constructor.
     */
    public function __construct() {
        add_action( 'admin_menu', [ $this, 'register_admin_page' ] );

        $this->fastcgi = new Admin\Fastcgi_Cache();
        new Admin\Adminbar();
    }

    /**
     * Register admin page.
     */
    public function register_admin_page() {
        $hook = add_submenu_page(
            'tools.php',
            __( 'FlyWP', 'flywp' ),
            __( 'FlyWP', 'flywp' ),
            'manage_options',
            self::PAGE_SLUG,
            [ $this, 'render_admin_page' ]
        );

        add_action( "admin_print_styles-{$hook}", [ $this, 'enqueue_styles' ] );
        add_action( "admin_print_scripts-{$hook}", [ $this, 'enqueue_js' ] );
        add_filter( 'removable_query_args', [ $this, 'removable_query_args' ] );
    }

    /**
     * Get admin page url.
     *
     * @return string
     */
    public function page_url() {
        return admin_url( 'tools.php?page=' . self::PAGE_SLUG );
    }

    /**
     * Add query args to removable query args.
     *
     * @param array $args
     *
     * @return array
     */
    public function removable_query_args( $args ) {
        $args[] = 'fly-notice';

        return $args;
    }

    /**
     * Enqueue admin styles.
     */
    public function enqueue_styles() {
        $min = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

        wp_enqueue_style( 'flywp-admin-styles', FLYWP_PLUGIN_URL . '/assets/css/app' . $min . '.css' );
    }

    /**
     * Enqueue admin scripts.
     */
    public function enqueue_js() {
        $min = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

        wp_enqueue_script( 'flywp-admin-js', FLYWP_PLUGIN_URL . '/assets/js/admin' . $min . '.js', [ 'jquery' ], FLYWP_VERSION, true );
    }

    /**
     * Render admin page.
     */
    public function render_admin_page() {
        include FLYWP_PLUGIN_DIR . '/views/admin.php';
    }
}
