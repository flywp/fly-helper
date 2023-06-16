<div class="flywp-settings" id="flywp-settings">
    <div class="fw-mb-8 fw-bg-white fw-border-b fw-border-solid fw-border-gray-200">
        <div class="fw-max-w-xl fw-mx-auto fw-px-4 sm:fw-px-0">
            <div class="fw-flex fw-py-2 fw-items-center fw-justify-between">
                <h1 class="fw-text-2xl fw-font-semibold fw-flex-1">
                    <img src="<?php echo esc_url( FLYWP_PLUGIN_URL . 'assets/images/flywp-logo.svg' ); ?>" alt="FlyWP" class="fw-h-11 fw-mr-2 fw-inline-block">
                </h1>

                <div class="">
                    <a href="https://app.flywp.com" target="_blank" class="button button-secondary"><span class="dashicons dashicons-external fw-mt-1"></span> <?php esc_html_e( 'FlyWP Dashboard', 'flywp' ); ?></a>
                </div>
            </div>
        </div>
    </div>

    <div class="fw-max-w-xl fw-mx-auto fw-px-4 sm:fw-px-0">
        <?php require __DIR__ . '/page-cache.php'; ?>
        <?php require __DIR__ . '/op-cache.php'; ?>
    </div>

</div>
