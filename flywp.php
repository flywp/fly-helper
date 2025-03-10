<?php
/**
 * Plugin Name: FlyWP
 * Plugin URI: https://flywp.com
 * Description: Helper plugin for FlyWP
 * Version: 1.4.0
 * Author: FlyWP
 * Author URI: https://flywp.com/?utm_source=wporg&utm_medium=banner&utm_campaign=author-uri
 * License: GPL2
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require __DIR__ . '/vendor/autoload.php';

use WeDevs\WpUtils\ContainerTrait;
use WeDevs\WpUtils\HookTrait;
use WeDevs\WpUtils\SingletonTrait;

/**
 * Main FlyWP Class.
 *
 * @var admin   FlyWP\Admin
 * @var rest    FlyWP\Api
 * @var fastcgi FlyWP\Fastcgi_Cache
 * @var router  FlyWP\Router
 *
 * @class FlyWP
 */
final class FlyWP_Plugin {

    use SingletonTrait;
    use ContainerTrait;
    use HookTrait;

    /**
     * Plugin version.
     *
     * @var string
     */
    public $version = '1.4.0';

    /**
     * Plugin Constructor.
     *
     * @return void
     */
    private function __construct() {
        $this->define_constants();

        $this->add_action( 'plugins_loaded', 'init_plugin' );
        register_activation_hook( __FILE__, [ $this, 'activate' ] );
        register_deactivation_hook( __FILE__, [ $this, 'deactivate' ] );
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
     * Plugin activation hook.
     *
     * @return void
     */
    public function activate() {
        $router = new FlyWP\Router();
        $router->register_routes();

        flush_rewrite_rules( false );
    }

    /**
     * Plugin activation hook.
     *
     * @return void
     */
    public function deactivate() {
        ( new FlyWP\Api\UpdatesData() )->deactivate();
    }

    /**
     * Initialize plugin.
     *
     * @return void
     */
    public function init_plugin() {
        if ( ! $this->has_key() ) {
            $this->add_action( 'admin_notices', 'admin_notice' );

            return;
        }

        if ( is_admin() ) {
            $this->admin = new FlyWP\Admin();
        } else {
            $this->frontend = new FlyWP\Frontend();
        }

        $this->router       = new FlyWP\Router();
        $this->rest         = new FlyWP\Api();
        $this->fastcgi      = new FlyWP\Fastcgi_Cache();
        $this->opcache      = new FlyWP\Opcache();
        $this->flyapi       = new FlyWP\FlyApi();
        $this->email        = new FlyWP\Email();
        $this->optimize     = new FlyWP\Optimizations();
        $this->litespeed    = new FlyWP\Litespeed();
        $this->updates_data = new FlyWP\Api\UpdatesData();
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
     * Check if API key is set.
     *
     * @return bool
     */
    public function has_key() {
        return FLYWP_API_KEY !== '';
    }

    /**
     * Get API key.
     *
     * @return string
     */
    public function get_key() {
        return FLYWP_API_KEY;
    }
}

/**
 * Returns the main instance of FlyWP to prevent the need to use globals.
 *
 * @return FlyWP_Plugin
 */
function flywp() {
    return FlyWP_Plugin::instance();
}

// take off
flywp();
