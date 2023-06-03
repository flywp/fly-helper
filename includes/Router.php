<?php

namespace FlyWP;

use WP;

class Router {

    /**
     * The single instance of the class.
     *
     * @var Router
     */
    private static $instance;

    /**
     * Base route for API.
     *
     * @var string
     */
    private $base_route = 'fly-api';

    /**
     * Registered routes.
     *
     * @var array
     */
    private $routes = [];

    /**
     * Get the singleton instance of the class.
     *
     * @return Router
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Initialize the class.
     *
     * @return void
     */
    public function init() {
        add_action( 'init', [ $this, 'register_routes' ], 0 );
        add_action( 'wp', [ $this, 'handle_request' ], 0 );
    }

    /**
     * Register routes.
     *
     * @return void
     */
    public function register_routes() {
        add_rewrite_endpoint( $this->base_route, EP_ROOT );
    }

    /**
     * Handle API request.
     *
     * @param WP $wp
     *
     * @return void
     */
    public function handle_request() {
        global $wp;

        if ( isset( $wp->query_vars[ $this->base_route ] ) ) {
            $route = $wp->query_vars[ $this->base_route ];

            // Check if the requested route exists
            if ( isset( $this->routes[ $route ] ) ) {
                $callback       = $this->routes[ $route ];
                $request_method = $_SERVER['REQUEST_METHOD'];

                // Check if the route is allowed for the current request method
                if ( $this->is_route_method_allowed( $callback, $request_method ) ) {
                    $request_args = $this->get_request_args( $request_method );

                    // var_dump( $request_args );
                    // die;

                    // Call the callback function with request arguments
                    if ( is_callable( $callback['callback'] ) ) {
                        if ( is_array( $callback['callback'] ) ) {
                            $class  = $callback['callback'][0];
                            $method = $callback['callback'][1];

                            if ( is_object( $class ) && method_exists( $class, $method ) ) {
                                call_user_func_array( [ $class, $method ], [$request_args] );
                            }
                        } else {
                            call_user_func_array( $callback['callback'], [$request_args] );
                        }
                    }

                    exit;
                } else {
                    status_header( 405 );
                    die( 'Method Not Allowed' );
                }
            }

            // If the route doesn't exist, return a 404 response
            status_header( 404 );
            die( 'Invalid API route' );
        }
    }

    /**
     * Register a GET route.
     *
     * @param string $route
     * @param mixed  $callback
     *
     * @return void
     */
    public function get( $route, $callback ) {
        $this->register_route( $route, $callback, 'GET' );
    }

    /**
     * Register a POST route.
     *
     * @param string $route
     * @param mixed  $callback
     *
     * @return void
     */
    public function post( $route, $callback ) {
        $this->register_route( $route, $callback, 'POST' );
    }

    /**
     * Register a route.
     *
     * @param string $route
     * @param mixed  $callback
     * @param string $method
     *
     * @return void
     */
    private function register_route( $route, $callback, $method = 'GET' ) {
        $this->routes[ $route ] = [
            'callback' => $callback,
            'method'   => strtoupper( $method ),
        ];
    }

    /**
     * Get registered routes.
     *
     * @return array
     */
    public function get_routes() {
        return $this->routes;
    }

    /**
     * Check if the route is allowed for the current request method.
     *
     * @param array  $callback
     * @param string $request_method
     *
     * @return bool
     */
    private function is_route_method_allowed( $callback, $request_method ) {
        return  $callback['method'] === 'ANY' || $callback['method'] === $request_method;
    }

    /**
     * Get request arguments.
     *
     * @param string $request_method
     *
     * @return array
     */
    private function get_request_args( $request_method ) {
        $request_args = [];

        if ( 'GET' === $request_method ) {
            $request_args = $_GET;
        } elseif ( 'POST' === $request_method ) {
            $request_args = $_POST;
        } else {
            $request_args = $_REQUEST;
        }

        return $request_args;
    }
}
