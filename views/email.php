<?php
$settings = flywp()->email->settings();
?>
<div class="fw-bg-white fw-shadow fw-rounded fw-sm:rounded-lg fw-mb-6">
    <div class="">
        <div class="fw-flex fw-px-6 fw-py-4 fw-fw-sm:p-6 fw-items-center fw-justify-between fw-border-b fw-border-gray-200">
            <h3 class="fw-text-lg fw-font-semibold fw-leading-6 fw-text-gray-900 fw-m-0">
                <?php esc_html_e( 'Email Settings', 'flywp' ); ?>
            </h3>

            <div class="">
                <a href="https://app.flywp.com/" target="_blank" class="fw-no-underline fw-text-indigo-600">
                    <span class="dashicons dashicons-external"></span>
                    <?php esc_html_e( 'SMTP Configuration', 'flywp' ); ?>
                </a>
            </div>
        </div>

        <?php if ( isset( $_GET['message'] ) && $_GET['message'] === 'email-settings-saved' ) { ?>
            <div class="fw-bg-green-200 fw-text-green-800 fw-px-4 fw-py-1" id="fly-page-cache-notice">
                <p><?php esc_html_e( 'Email settings have been saved.', 'flywp' ); ?></p>
            </div>
        <?php } ?>

        <form method="POST" action="">
            <div class="fw-mt-2 fw-px-6 fw-py-4 fw-fw-sm:p-6 fw-text-sm fw-text-gray-500">

                <!--
                <div class="fw-text-sm fw-border fw-border-yellow-200 fw-rounded fw-px-4 fw-py-4 fw-bg-yellow-100 fw-text-yellow-900 fw-mb-4">
                    <?php esc_html_e( 'With FlyWP, you don’t need a 3rd-party SMTP plugin to send emails. Just configure your connection from the FlyWP dashboard, all your emails will go through the configured gateway without needing a plugin.', 'flywp' ); ?>
                </div>
                -->

                <div class="fw-mb-4">
                    <label for="from-name" class="fw-font-medium fw-text-gray-700"><?php esc_html_e( 'From Name', 'flywp' ); ?></label>
                    <input type="text" id="from-name" name="from_name" value="<?php echo esc_attr( $settings['from_name'] ); ?>" class="fw-block fw-w-full fw-mt-1 fw-py-2 fw-px-3 fw-border fw-border-gray-200 fw-rounded-md fw-shadow-sm focus:fw-outline-none focus:fw-ring-indigo-500 focus:fw-border-indigo-500 sm:fw-text-sm" />

                    <p class="description fw-mt-1">
                        <?php esc_html_e( 'This name will be used as the sender name for all outgoing emails.', 'flywp' ); ?>
                    </p>
                </div>

                <div class="fw-mb-1">
                    <label for="from-email" class="fw-font-medium fw-text-gray-700"><?php esc_html_e( 'From Email', 'flywp' ); ?></label>
                    <input type="email" id="from-email" name="from_email" value="<?php echo esc_attr( $settings['from_email'] ); ?>" class="fw-block fw-w-full fw-mt-1 fw-py-2 fw-px-3 fw-border fw-border-gray-200 fw-rounded-md fw-shadow-sm focus:fw-outline-none focus:fw-ring-indigo-500 focus:fw-border-indigo-500 sm:fw-text-sm" />

                    <p class="description fw-mt-1">
                        <?php esc_html_e( 'This email will be used as the sender email for all outgoing emails.', 'flywp' ); ?>
                    </p>
                </div>
            </div>

            <div class="fw-px-6 fw-py-4 fw-fw-sm:p-6 fw-mt-5 fw-bg-gray-50 fw-p-4 fw-border-t fw-rounded-br fw-rounded-bl fw-border-gray-200 fw-text-right">
                <button type="submit" class="button button-primary button-large !fw-bg-indigo-600 hover:!fw-bg-indigo-700" id="fastcgi-settings-button">
                    <?php esc_html_e( 'Save Changes', 'flywp' ); ?>
                </button>

                <?php wp_nonce_field( 'flywp-email-settings', 'flywp-email-nonce' ); ?>
            </div>
        </form>
    </div>
</div>

<div class="fw-bg-white fw-shadow fw-rounded fw-sm:rounded-lg fw-mb-6">
    <div class="">
        <div class="fw-px-6 fw-py-4 fw-fw-sm:p-6 fw-border-b fw-border-gray-200">
            <h3 class="fw-text-lg fw-font-semibold fw-leading-6 fw-text-gray-900 fw-m-0">
                <?php esc_html_e( 'Test Email', 'flywp' ); ?>
            </h3>
        </div>

        <?php if ( isset( $_GET['message'] ) && $_GET['message'] === 'test-mail-sent' ) { ?>
            <div class="fw-bg-green-200 fw-text-green-800 fw-px-4 fw-py-1" id="fly-page-cache-notice">
                <p><?php esc_html_e( 'Test email has been sent.', 'flywp' ); ?></p>
            </div>
        <?php } ?>

        <?php if ( isset( $_GET['message'] ) && $_GET['message'] === 'test-mail-failed' ) { ?>
            <div class="fw-bg-red-200 fw-text-red-800 fw-px-4 fw-py-1" id="fly-page-cache-notice">
                <p><?php esc_html_e( 'Test email could not be sent.', 'flywp' ); ?></p>
            </div>
        <?php } ?>

        <form method="POST" action="">
            <div class="fw-mt-2 fw-px-6 fw-py-4 fw-fw-sm:p-6 fw-text-sm fw-text-gray-500">
                <div class="fw-mb-4">
                    <label for="to-email" class="fw-font-medium fw-text-gray-700"><?php esc_html_e( 'Send To', 'flywp' ); ?></label>
                    <input type="email" id="to-email" name="to_email" value="<?php echo esc_attr( wp_get_current_user()->user_email ); ?>" class="fw-block fw-w-full fw-mt-1 fw-py-2 fw-px-3 fw-border fw-border-gray-200 fw-rounded-md fw-shadow-sm focus:fw-outline-none focus:fw-ring-indigo-500 focus:fw-border-indigo-500 sm:fw-text-sm" />

                    <p class="description fw-mt-1">
                        <?php esc_html_e( 'Enter email address where the test email will be sent.', 'flywp' ); ?>
                    </p>
                </div>

                <div class="fw-flex fw-gap-2">
                    <label for="html-email" class="fw-w-9 fw-block fw-switch">
                        <input type="checkbox" id="html-email" name="html_email" class="fw-switch-input" checked="checked" />
                        <span class="fw-switch-toggle"></span>
                    </label>

                    <div class="fw-flex-1">
                        <div class="fw-font-medium fw-text-gray-700">
                            <?php esc_html_e( 'Send HTML Email', 'flywp' ); ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="fw-px-6 fw-py-4 fw-fw-sm:p-6 fw-mt-5 fw-bg-gray-50 fw-p-4 fw-border-t fw-rounded-br fw-rounded-bl fw-border-gray-200 fw-text-right">
                <button type="submit" class="button button-primary button-large !fw-bg-indigo-600 hover:!fw-bg-indigo-700" id="fastcgi-settings-button">
                    <?php esc_html_e( 'Send Test Email', 'flywp' ); ?>
                </button>

                <?php wp_nonce_field( 'flywp-email-test', 'flywp-email-test-nonce' ); ?>
            </div>
        </form>
    </div>
</div>
