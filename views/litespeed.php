<?php

$notice = '';

$cache_messages = [
    'lscache-enabled'  => __( 'Page Caching has been enabled.', 'flywp' ),
    'lscache-disabled' => __( 'Page Caching has been disabled.', 'flywp' ),
];

if ( isset( $_GET['fly-notice'] ) && isset( $cache_messages[$_GET['fly-notice']] ) ) {
    $notice = $cache_messages[$_GET['fly-notice']];
}
?>

<div class="fw-bg-white fw-shadow fw-rounded fw-sm:rounded-lg fw-mb-6">
    <div class="">
        <div class="fw-flex fw-px-4 fw-py-4 fw-fw-sm:p-6 fw-items-center fw-justify-between fw-border-b fw-border-gray-200">
            <h3 class="fw-text-lg fw-font-semibold fw-leading-6 fw-text-gray-900 fw-m-0">
                <?php esc_html_e( 'LiteSpeed Cache', 'flywp' ); ?>
            </h3>

            <?php if ( flywp()->litespeed->cache_enabled() ) { ?>
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

        <?php if ( $notice ) { ?>
            <div class="fw-bg-green-200 fw-text-green-800 fw-px-4 fw-py-1 fly-form-notice">
                <p><?php echo esc_html( $notice ); ?></p>
            </div>
        <?php } ?>

        <div class="fw-mt-2 fw-px-4 fw-py-4 fw-fw-sm:p-6 fw-text-sm fw-text-gray-500 md:fw-flex">
            <div class="md:fw-w-2/3">
                <p class="fw-mt-0 fw-text-sm"><?php esc_html_e( 'LiteSpeed Cache is a very fast page caching system that boosts up your website page load speed.', 'flywp' ); ?></p>
            </div>

            <?php if ( flywp()->litespeed->cache_enabled() ) { ?>
                <div class="md:fw-w-1/3 fw-text-center md:fw-text-right">
                    <a href="<?php echo esc_url( flywp()->litespeed->purge_cache_url() ); ?>" class="button button-primary button-hero !fw-bg-indigo-600 hover:!fw-bg-indigo-700"><?php esc_html_e( 'Clear Cache', 'flywp' ); ?></a>
                </div>
            <?php } ?>
        </div>


        <div class="fw-px-4 fw-py-3 fw-fw-sm:p-6 fw-mt-5 fw-bg-gray-100 fw-p-4 fw-border-t fw-rounded-br fw-rounded-bl fw-border-gray-200 fw-text-right">
            <?php if ( flywp()->litespeed->cache_enabled() ) { ?>
                <a href="<?php echo esc_url( $this->litespeed->enable_disable_url( 'disable' ) ); ?>" class="button button-secondary button-large">
                    <?php esc_html_e( 'Disable', 'flywp' ); ?>
                </a>
            <?php } elseif ( flywp()->litespeed->is_plugin_active() ) { ?>
                <a href="<?php echo esc_url( $this->litespeed->enable_disable_url( 'enable' ) ); ?>" class="button button-primary button-large">
                    <?php esc_html_e( 'Enable Cache', 'flywp' ); ?>
                </a>
            <?php } ?>

            <?php $settings = flywp()->litespeed->settings_url(); ?>
            
            <a class="button button-secondary button-large fw-flex fw-items-center" href="<?php echo esc_attr( $settings['url'] ); ?>" target="_blank">
                <span class="dashicons dashicons-admin-generic button-active fw-mt-1"></span>
                <?php echo esc_html( $settings['text'] ); ?>
            </a>
        </div>

    </div>
</div>
