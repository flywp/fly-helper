<?php

namespace FlyWP;

class FlyApi {

    /**
     * Get the site's info.
     *
     * @return array|false
     */
    public function site_info() {
        return $this->get( '/info' );
    }

    /**
     * Set the site's cache status.
     *
     * @param string $action
     *
     * @return array|false
     */
    public function cache_toggle( $action = 'enable', $type = 'fastcgi' ) {
        return $this->post(
            '/cache-toggle',
            [
                'action' => $action,
                'type'   => $type,
            ]
        );
    }

    /**
     * Get the API endpoint.
     *
     * @return string
     */
    protected function get_endpoint() {
        return apply_filters( 'flywp_api_endpoint', FLYWP_API_ENDPOINT );
    }

    /**
     * Send a GET request to the API.
     *
     * @param string $path
     *
     * @return array|false
     */
    public function get( $path ) {
        $url = $this->get_endpoint() . $path;

        $response = wp_remote_get(
            $url,
            [
                'headers' => [
                    'Authorization' => 'Bearer ' . flywp()->get_key(),
                ],
            ]
        );

        if ( is_wp_error( $response ) ) {
            // Handle error if needed
            return false;
        }

        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );

        return $data;
    }

    /**
     * Send a POST request to the API.
     *
     * @param string $path
     * @param array $data
     *
     * @return array|false
     */
    public function post( $path, $data = [] ) {
        $url = $this->get_endpoint() . $path;

        $response = wp_remote_post(
            $url,
            [
                'headers' => [
                    'Authorization' => 'Bearer ' . flywp()->get_key(),
                ],
                'body'    => $data,
            ]
        );

        if ( is_wp_error( $response ) ) {
            error_log( print_r( $response, true ) );

            return false;
        }

        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );

        return $data;
    }
}
