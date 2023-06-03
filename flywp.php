<?php
/**
 * Plugin Name: FlyWP
 * Plugin URI: https://flywp.com
 * Description: Helper plugin for FlyWP
 * Version: 0.1
 * Author: FlyWP
 * License: GPL2
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require __DIR__ . '/vendor/autoload.php';

final class FlyWP_Plugin {

    /**
     * Plugin version.
     *
     * @var string
     */
    public $version = '1.0.0';

    /**
     * The single instance of the class.
     *
     * @var FlyWP
     */
    private static $instance = null;

    /**
     * FastCGI Cache helper instance.
     *
     * @var FlyWP\Fastcgi_Cache
     */
    public $fastcgi = null;

    /**
     * Admin instance.
     *
     * @var FlyWP\Admin
     */
    public $admin = null;

    /**
     * Plugin Constructor.
     *
     * @return void
     */
    private function __construct() {
        $this->define_constants();

        add_action( 'plugins_loaded', [ $this, 'init_plugin' ] );
    }

    /**
     * Main FlyWP Instance.
     *
     * Ensures only one instance of FlyWP is loaded or can be loaded.
     *
     * @return flyWP - Main instance
     */
    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Initialize plugin.
     *
     * @return void
     */
    public function init_plugin() {
        if ( ! $this->has_api_key() ) {
            add_action( 'admin_notices', [ $this, 'admin_notice' ] );

            return;
        }

        if ( is_admin() ) {
            $this->admin = new FlyWP\Admin();
        } else {
            new FlyWP\Frontend();
        }

        $this->fastcgi = new FlyWP\Fastcgi_Cache();
    }

    /**
     * Show admin notice if API key is not set.
     *
     * @return void
     */
    public function admin_notice() {
        $message = __( 'Missing FlyWP API key, plugin requires an API key.', 'flywp' );

        echo '<div class="notice notice-error"><p>' . esc_html( $message ) . '</p></div>';
    }

    /**
     * Define plugin constants.
     *
     * @return void
     */
    private function define_constants() {
        define( 'FLYWP_VERSION', $this->version );
        define( 'FLYWP_PLUGIN_FILE', __FILE__ );
        define( 'FLYWP_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
        define( 'FLYWP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
        define( 'FLYWP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

        if ( ! defined( 'FLYWP_API_KEY' ) ) {
            define( 'FLYWP_API_KEY', '' );
        }
    }

    /**
     * Check if API key is set.
     *
     * @return bool
     */
    public function has_api_key() {
        return FLYWP_API_KEY !== '';
    }

    /**
     * Get API key.
     *
     * @return string
     */
    public function get_api_key() {
        return FLYWP_API_KEY;
    }
}

/**
 * Returns the main instance of FlyWP to prevent the need to use globals.
 *
 * @return FlyWP
 */
function flywp() {
    return FlyWP_Plugin::instance();
}

// take off
flywp();
