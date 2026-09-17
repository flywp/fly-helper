<?php
/**
 * Plugin Name: FlyWP
 * Plugin URI: https://flywp.com
 * Description: Helper plugin for FlyWP
 * Version: 1.7.1
 * Author: FlyWP
 * Author URI: https://flywp.com/?utm_source=wporg&utm_medium=banner&utm_campaign=author-uri
 * License: GPL2
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/*
 * Stand down when this plugin is already loaded from somewhere else.
 *
 * A site can carry two copies. On Bedrock the plugin directory belongs to Composer, so FlyWP
 * keeps its own copy outside the checkout and mounts it in under a second slug. If both are
 * active, `FlyWP_Plugin`, `flywp()` and the bundled Composer autoloader's init class are each
 * declared twice and the site fatals.
 *
 * Whichever copy runs second gives way. That is deliberately all this decides: it needs no
 * knowledge of what the other copy is called, so a customer whose Composer install lands in a
 * directory named anything at all is still protected. Which copy *should* win is settled by
 * FlyWP before either is activated, by activating exactly one.
 *
 * Both conditions are checked because they become true at different moments, and this has to
 * run before `vendor/autoload.php` -- requiring a second copy of the autoloader is itself one
 * of the redeclarations being avoided.
 *
 * Known gap: a copy that stands down here has not called `register_activation_hook()`, so if
 * WordPress activates it during this same request its `activate()` never runs. Both copies
 * register the same rewrite endpoint and the next request loads the survivor normally, so
 * nothing is lost today -- but an activation-only step added later would not run.
 */
if ( defined( 'FLYWP_VERSION' ) || class_exists( 'FlyWP_Plugin', false ) ) {
    return;
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
    public $version = '1.7.1';

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

        if ( ! defined( 'FLYWP_LOGIN_PUBLIC_KEY' ) ) {
            define( 'FLYWP_LOGIN_PUBLIC_KEY', '' );
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
     * Plugin deactivation hook.
     *
     * @return void
     */
    public function deactivate() {
        $timestamp = wp_next_scheduled( FlyWP\Api\UpdatesData::CRON_HOOK );
        if ( $timestamp ) {
            wp_unschedule_event( $timestamp, FlyWP\Api\UpdatesData::CRON_HOOK );
        }
    }

    /**
     * Initialize plugin.
     *
     * @return void
     */
    public function init_plugin() {
        // Loaded ahead of the API-key gate below, as it does not use the API key. It answers
        // one POST path and does nothing on any other request.
        new FlyWP\Frontend\MagicLogin();

        // The router and the API also load ahead of the gate. `Router::register_routes()` is
        // what registers `fly-api` as a public query variable, and it has to run on every
        // request; when it does not, `WP::parse_request()` drops the unknown variable and
        // WordPress serves the front page instead. That is a 200 carrying the theme, which
        // reads to a caller as a working site rather than a plugin with no key. Loading these
        // unconditionally means an unkeyed site answers `/fly-api/*` with JSON and says so.
        //
        // This exposes nothing new: `Api` registers only the unauthenticated `ping` route
        // until a valid bearer token is presented, and every other route stays behind either
        // that check or the key gate below.
        $this->router = new FlyWP\Router();
        $this->rest   = new FlyWP\Api();

        if ( ! $this->has_key() ) {
            $this->add_action( 'admin_notices', 'admin_notice' );

            return;
        }

        if ( is_admin() ) {
            $this->admin = new FlyWP\Admin();
        } else {
            $this->frontend = new FlyWP\Frontend();
        }

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
        return $this->get_key() !== '';
    }

    /**
     * Get API key.
     *
     * @return string
     */
    public function get_key() {
        return FlyWP\KeyResolver::resolve( FLYWP_API_KEY, 'FLYWP_API_KEY' );
    }

    /**
     * Public key used to verify magic-login tokens.
     *
     * @return string
     */
    public function get_login_public_key() {
        return FlyWP\KeyResolver::resolve( FLYWP_LOGIN_PUBLIC_KEY, 'FLYWP_LOGIN_PUBLIC_KEY' );
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
