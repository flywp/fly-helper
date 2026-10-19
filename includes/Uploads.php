<?php

namespace FlyWP;

/**
 * Refuses new uploads while the site's White Label customer has used all of their disk space.
 *
 * FlyWP writes `FLYWP_UPLOADS_BLOCKED` ('1' or '0') when the customer's disk total fills or has
 * room again. The files already there stay, and the site keeps serving. Only uploads through
 * WordPress stop: WP-CLI and plugins that write files are not blocked.
 *
 * Loaded ahead of the API-key gate: the flag does not need the key.
 */
class Uploads {

    /**
     * Class constructor.
     */
    public function __construct() {
        if ( ! self::blocked() ) {
            return;
        }

        add_filter( 'wp_handle_upload_prefilter', [ $this, 'refuse' ] );
        add_filter( 'wp_handle_sideload_prefilter', [ $this, 'refuse' ] );
    }

    /**
     * Whether uploads are refused: the constant, or on Bedrock the environment.
     *
     * @return bool
     */
    public static function blocked() {
        if ( defined( 'FLYWP_UPLOADS_BLOCKED' ) ) {
            $value = FLYWP_UPLOADS_BLOCKED;
        } else {
            $value = getenv( 'FLYWP_UPLOADS_BLOCKED' );

            if ( $value === false ) {
                $value = isset( $_ENV['FLYWP_UPLOADS_BLOCKED'] ) ? $_ENV['FLYWP_UPLOADS_BLOCKED'] : '';
            }
        }

        return in_array( strtolower( trim( (string) $value ) ), [ '1', 'true' ], true );
    }

    /**
     * The upload, with the error WordPress shows instead of saving it.
     *
     * @param array $file
     *
     * @return array
     */
    public function refuse( $file ) {
        $brand = flywp()->brand();

        $file['error'] = $brand->is_set()
            /* translators: %s: the name of the hosting company */
            ? sprintf( __( 'Your storage is full. Remove the files you do not need, or ask %s for more space.', 'flywp' ), $brand->name() )
            : __( 'Your storage is full. Remove the files you do not need, or move to a larger plan.', 'flywp' );

        return $file;
    }
}
