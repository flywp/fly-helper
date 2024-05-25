<div class="fw-bg-white fw-shadow fw-rounded fw-sm:rounded-lg fw-mb-4">
    <form method="POST" action="">
        <div class="fw-flex fw-px-4 fw-py-4 fw-fw-sm:p-6 fw-items-center fw-justify-between fw-border-b fw-border-gray-200">
            <h2 class="fw-text-lg fw-font-semibold fw-m-0">
                <?php esc_html_e( 'WordPress Optimizations', 'flywp' ); ?>
            </h2>

            <?php if ( flywp()->optimize->enabled() ) { ?>
                <div class="fw-text-green-600">
                    <span class="dashicons dashicons-yes-alt"></span>
                    <?php esc_html_e( 'Enabled', 'flywp' ); ?>
                </div>
            <?php } else { ?>

                <div class="fw-text-gray-400">
                    <span class="dashicons dashicons-no-alt"></span>
                    <?php esc_html_e( 'Disabled', 'flywp' ); ?>
                </div>
            <?php } ?>
        </div>

        <?php if ( isset( $_GET['message'] ) && $_GET['message'] === 'optimizations-settings-saved' ) { ?>
            <div class="fw-bg-green-200 fw-text-green-800 fw-px-4 fw-py-1 fly-form-notice">
                <p><?php esc_html_e( 'Optimization settings have been saved.', 'flywp' ); ?></p>
            </div>
        <?php } ?>

        <div class="fw-mt-2 fw-px-4 fw-py-4 fw-fw-sm:p-6 fw-text-sm fw-text-gray-500 fw-space-y-8">

            <div class="fw-flex fw-gap-3 fw-mb-4">
                <label for="enabled" class="fw-block fw-switch fw-mt-1">
                    <input type="checkbox" id="enabled" name="enabled" class="fw-switch-input" <?php checked( flywp()->optimize->enabled() ); ?> />
                    <span class="fw-switch-toggle"></span>
                </label>

                <div class="fw-flex-1">
                    <div class="fw-font-medium fw-text-gray-700 fw-mb-1">
                        <?php echo esc_html( __( 'Enable WordPress Optimizations', 'flywp ' ) ); ?>
                    </div>

                    <div class="fw-text-gray-500">
                        <?php echo esc_html( __( 'Enable or disable WordPress optimizations.', 'flywp' ) ); ?>
                    </div>
                </div>
            </div>  

            <?php foreach ( self::options() as $section => $data ) { ?>
                <div class="">
                    <div class="fw-border-b fw-border-gray-200 fw-mb-6 fw-pb-3">
                        <h3 class="fw-text-base fw-font-semibold fw-m-0"><?php echo esc_html( $data['title'] ); ?></h3>
                        <p class="fw-text-gray-600 fw-m-0"><?php echo esc_html( $data['description'] ); ?></p>
                    </div>

                    <?php foreach ( $data['fields'] as $key => $field ) { ?>
                        <div class="fw-flex fw-gap-3 fw-mb-4">
                            <label for="<?php echo esc_attr( $section . '-' . $key ); ?>" class="fw-block fw-switch fw-mt-1">
                                <input type="checkbox" id="<?php echo esc_attr( $section . '-' . $key ); ?>" name="<?php echo esc_attr( $section . '[' . $key . ']' ); ?>" class="fw-switch-input" <?php checked( flywp()->optimize->feature_enabled( $key, $section ) ); ?> />
                                <span class="fw-switch-toggle"></span>
                            </label>

                            <div class="fw-flex-1">
                                <div class="fw-font-medium fw-text-gray-700 fw-mb-1">
                                    <?php echo esc_html( $field['label'] ); ?>
                                </div>

                                <div class="fw-text-gray-500">
                                    <?php echo esc_html( $field['description'] ); ?>
                                </div>
                            </div>
                        </div>  

                    <?php } ?>

                </div>

            <?php } ?>
        </div>

        <div class="fw-px-4 fw-py-3 fw-fw-sm:p-6 fw-mt-5 fw-bg-gray-100 fw-p-4 fw-border-t fw-rounded-br fw-rounded-bl fw-border-gray-200 fw-text-right">
            <button type="submit" class="button button-primary button-large !fw-bg-indigo-600 hover:!fw-bg-indigo-700" id="fastcgi-settings-button">
                <?php esc_html_e( 'Save Settings', 'flywp' ); ?>
            </button>

            <?php wp_nonce_field( 'flywp-optimizations-settings', 'flywp-optimizations-nonce' ); ?>
        </div>
    </form>
</div>
