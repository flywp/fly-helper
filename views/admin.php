<div class="flywp-settings" id="flywp-settings">
    <div class="fw-mb-8 fw-bg-white fw-border-b fw-border-solid fw-border-gray-200">
        <div class="fw-max-w-xl fw-mx-auto fw-px-4 sm:fw-px-0">
            <div class="fw-flex fw-py-2 fw-items-center fw-justify-between">
                <h1 class="fw-text-2xl fw-font-semibold fw-flex-1">
                    <img src="<?php echo esc_url( FLYWP_PLUGIN_URL . 'assets/images/flywp-logo.svg' ); ?>" alt="FlyWP" class="fw-h-11 fw-mr-2 fw-inline-block">
                </h1>

                <div class="">
                    <a href="<?php echo esc_url( $app_site_url ); ?>" target="_blank" class="button button-secondary"><span class="dashicons dashicons-external fw-mt-1"></span> <?php esc_html_e( 'FlyWP Dashboard', 'flywp' ); ?></a>
                </div>
            </div>

            <div class="fw-flex -fw-mb-px fw-gap-2">
                <?php foreach ( $tabs as $key => $label ) { ?>
                    <a href="<?php echo esc_url( add_query_arg( [
                        'tab' => $key,
                    ], $this->page_url() ) ); ?>" class="fw-block fw-px-4 fw-py-3 fw-text-sm -m fw-text-gray-800 fw-no-underline fw-outline-none focus:fw-outline-none <?php echo $key === $active_tab ? 'fw-border-b-2 fw-border-indigo-500 fw-font-semibold' : ''; ?>"><?php echo $label; ?></a>
                <?php } ?>
            </div>
        </div>
    </div>

    <div class="fw-max-w-xl fw-mx-auto fw-px-4 sm:fw-px-0">
        <?php
        do_action( 'flywp_admin_tab_content', $active_tab );

        if ( $active_tab === 'cache' ) {
            require __DIR__ . '/page-cache.php';
            require __DIR__ . '/op-cache.php';
        } elseif ( $active_tab === 'email' ) {
            require __DIR__ . '/email.php';
        }
        ?>
    </div>

</div>
