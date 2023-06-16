<?php

$notice = '';
$status = flywp()->opcache->get_status();
$config = flywp()->opcache->get_config();

$cache_messages = [
    'opcache-purged'   => __( 'PHP OPcache has been cleared.', 'flywp' ),
];

if ( isset( $_GET['fly-notice'] ) && isset( $cache_messages[ $_GET['fly-notice'] ] ) ) {
    $notice = $cache_messages[ $_GET['fly-notice'] ];
}
?>

<div class="fw-bg-white fw-shadow fw-rounded fw-sm:rounded-lg fw-mb-4">
    <div class="">
        <div class="fw-flex fw-px-4 fw-py-4 fw-fw-sm:p-6 fw-items-center fw-justify-between fw-border-b fw-border-gray-200">
            <h3 class="fw-text-lg fw-font-semibold fw-leading-6 fw-text-gray-900 fw-m-0">
                <?php esc_html_e( 'PHP OPcache', 'flywp' ); ?>
            </h3>

            <?php if ( flywp()->opcache->enabled() ) { ?>
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
            <div class="fw-bg-green-200 fw-text-green-800 fw-px-4 fw-py-1" id="fly-page-cache-notice">
                <p><?php echo esc_html( $notice ); ?></p>
            </div>
        <?php } ?>

        <div class="fw-mt-2 fw-px-4 fw-py-4 fw-fw-sm:p-6 fw-text-sm fw-text-gray-500">
            <p class="fw-mt-0"><?php esc_html_e( 'OPcache improves PHP performance by storing precompiled script bytecode in shared memory, thereby removing the need for PHP to load and parse scripts on each request.', 'flywp' ); ?></p>
            
            <?php if ( flywp()->opcache->enabled() ) { ?>
                <div class="md:fw-flex fw-w-full fw-mb-4 fw-gap-3 fw-items-center">
                    <div class="sm:fw-w-2/3 fw-mb-6 sm:fw-mb-0">
                        <dl class="fw-m-0 fw-mt-2">
                            <div class="fw-flex fw-justify-between sm:fw-justify-normal">
                                <dt class="fw-font-medium fw-text-gray-900 sm:fw-w-32 sm:fw-flex-none sm:fw-pr-6"><?php esc_html_e( 'Hit Rate', 'flywp' ); ?></dt>
                                <dd><?php echo number_format( $status['opcache_statistics']['opcache_hit_rate'], 2 ) . '%'; ?></dd>
                            </div>

                            <div class="fw-flex fw-justify-between sm:fw-justify-normal">
                                <dt class="fw-font-medium fw-text-gray-900 sm:fw-w-32 sm:fw-pr-6"><?php esc_html_e( 'Cached Scripts', 'flywp' ); ?></dt>
                                <dd><?php echo $status['opcache_statistics']['num_cached_scripts']; ?></dd>
                            </div>

                            <div class="fw-flex fw-justify-between sm:fw-justify-normal">
                                <dt class="fw-font-medium fw-text-gray-900 sm:fw-w-32 sm:fw-pr-6"><?php esc_html_e( 'Memory', 'flywp' ); ?></dt>
                                <dd><?php echo sprintf( '%s of %s', size_format( $status['memory_usage']['used_memory'] ), size_format( $config['directives']['opcache.memory_consumption'] ) ); ?></dd>
                            </div>

                            <div class="fw-flex fw-justify-between sm:fw-justify-normal">
                                <dt class="fw-font-medium fw-text-gray-900 sm:fw-w-32 sm:fw-pr-6"><?php esc_html_e( 'Keys', 'flywp' ); ?></dt>
                                <dd><?php echo sprintf( '%d of %d', $status['opcache_statistics']['num_cached_keys'], $status['opcache_statistics']['max_cached_keys'] ); ?></dd>
                            </div>

                            <div class="fw-flex fw-justify-between sm:fw-justify-normal">
                                <dt class="fw-font-medium fw-text-gray-900 sm:fw-w-32 sm:fw-pr-6"><?php esc_html_e( 'Engine', 'flywp' ); ?></dt>
                                <dd><?php echo sprintf( '%s (%s)', $config['version']['opcache_product_name'], $config['version']['version'] ); ?></dd>
                            </div>
                        </dl>
                    </div>

                    <div class="sm:fw-w-1/3 fw-text-center sm:fw-text-right">
                        <a href="<?php echo esc_url( flywp()->opcache->purge_cache_url() ); ?>" class="button button-primary button-hero"><?php esc_html_e( 'Clear Cache', 'flywp' ); ?></a>
                    </div>
                </div>
            <?php } ?>
        </div>
    </div>
</div>
