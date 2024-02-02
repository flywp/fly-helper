<?php

namespace FlyWP;

class Admin {

    /**
     * Admin page slug.
     */
    const PAGE_SLUG = 'flywp';

    /**
     * Screen name.
     *
     * @var string
     */
    const SCREEN_NAME = 'dashboard_page_flywp';

    public $fastcgi = null;

    /**
     * Admin constructor.
     */
    public function __construct() {
        add_action( 'admin_menu', [ $this, 'register_admin_page' ] );

        $this->fastcgi = new Admin\Fastcgi_Cache();

        new Admin\Adminbar();
        new Admin\Opcache();
        new Admin\Plugins();
        new Admin\Email();
    }

    /**
     * Register admin page.
     */
    public function register_admin_page() {
        $hook = add_dashboard_page(
            __( 'FlyWP', 'flywp' ),
            __( 'FlyWP', 'flywp' ),
            'manage_options',
            self::PAGE_SLUG,
            [$this, 'render_admin_page']
        );

        add_action( "admin_print_styles-{$hook}", [$this, 'enqueue_styles'] );
        add_action( "admin_print_scripts-{$hook}", [$this, 'enqueue_js'] );
        add_filter( 'removable_query_args', [$this, 'removable_query_args'] );
    }

    /**
     * Get admin page url.
     *
     * @return string
     */
    public function page_url() {
        return admin_url( 'index.php?page=' . self::PAGE_SLUG );
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

        wp_enqueue_style( 'flywp-admin-styles', FLYWP_PLUGIN_URL . '/assets/css/app' . $min . '.css', [], FLYWP_VERSION );
    }

    /**
     * Enqueue admin scripts.
     */
    public function enqueue_js() {
        $min = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

        wp_enqueue_script( 'flywp-admin-js', FLYWP_PLUGIN_URL . '/assets/js/admin' . $min . '.js', ['jquery'], FLYWP_VERSION, true );
    }

    /**
     * Render admin page.
     */
    public function render_admin_page() {
        $tabs = [
            'cache' => __( 'Caching', 'flywp' ),
            'email' => __( 'Email', 'flywp' ),
        ];

        $active_tab = isset( $_GET['tab'] ) && array_key_exists( $_GET['tab'], $tabs ) ? $_GET['tab'] : 'cache';

        include FLYWP_PLUGIN_DIR . '/views/admin.php';
    }
}
