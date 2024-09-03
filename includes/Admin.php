<?php

namespace FlyWP;

class Admin {

    /**
     * Admin page slug.
     */
    public const PAGE_SLUG = 'flywp';

    /**
     * Screen name.
     *
     * @var string
     */
    public const SCREEN_NAME = 'dashboard_page_flywp';

    public $fastcgi = null;
    public $litespeed = null;

    /**
     * Admin constructor.
     */
    public function __construct() {
        add_action( 'admin_menu', [ $this, 'register_admin_page' ] );

        $this->fastcgi   = new Admin\Fastcgi_Cache();
        $this->litespeed = new Admin\Litespeed();

        new Admin\Adminbar();
        new Admin\Opcache();
        new Admin\Plugins();
        new Admin\Email();
        new Admin\Optimizations();
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

        wp_enqueue_script( 'flywp-admin-js', FLYWP_PLUGIN_URL . '/assets/js/admin' . $min . '.js', [ 'jquery' ], FLYWP_VERSION, true );
    }

    /**
     * Render admin page.
     */
    public function render_admin_page() {
        $tabs = [
            'cache'         => __( 'Caching', 'flywp' ),
            'email'         => __( 'Email', 'flywp' ),
            'optimizations' => __( 'Optimizations', 'flywp' ),
        ];

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $tab          = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : '';
        $active_tab   = array_key_exists( $tab, $tabs ) ? $tab : 'cache';
        $site_info    = $this->fetch_site_info();
        $app_site_url = $this->get_site_url( $site_info );

        include FLYWP_PLUGIN_DIR . '/views/admin.php';
    }

    /**
     * Fetch site info.
     *
     * @return array|false
     */
    private function fetch_site_info() {
        $transient_key = 'flywp_site_info';
        $site_info     = get_transient( $transient_key );

        if ( false === $site_info ) {
            $site_info = flywp()->flyapi->site_info();

            if ( isset( $site_info['error'] ) ) {
                return false;
            }

            set_transient( $transient_key, $site_info, DAY_IN_SECONDS );
        }

        return $site_info;
    }

    /**
     * Get site URL.
     *
     * @param array|false $info
     *
     * @return string
     */
    private function get_site_url( $info ) {
        if ( false === $info ) {
            return 'https://app.flywp.com';
        }

        return sprintf(
            'https://app.flywp.com/site/%d',
            $info['id']
        );
    }
}
