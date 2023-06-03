<?php

$notice   = '';

$cache_messages = [
    'fastcgi-saved'    => __( 'Cache settings has been updated.', 'flywp' ),
    'fastcgi-purged'   => __( 'Cache has been cleared.', 'flywp' ),
    'fastcgi-enabled'  => __( 'Page Caching has been enabled.', 'flywp' ),
    'fastcgi-disabled' => __( 'Page Caching has been disabled.', 'flywp' ),
];

if ( isset( $_GET['fly-notice'] ) && isset( $cache_messages[$_GET['fly-notice']] ) ) {
    $notice = $cache_messages[$_GET['fly-notice']];
}
?>

<div class="fw-bg-white fw-shadow fw-rounded fw-sm:rounded-lg fw-mb-4">
    <div class="">
        <div class="fw-flex fw-px-4 fw-py-5 fw-fw-sm:p-6 fw-items-center fw-justify-between fw-border-b fw-border-gray-200">
            <h3 class="fw-text-xl fw-font-semibold fw-leading-6 fw-text-gray-900 fw-m-0">
                <?php _e( 'Page Cache', 'flywp' ); ?>
            </h3>

            <?php if ( flywp()->fastcgi->enabled() ) { ?>
                <div class="fw-text-green-600">
                    <span class="dashicons dashicons-yes-alt"></span>
                    <?php _e( 'Enabled', 'flywp' ); ?>
                </div>
            <?php } else { ?>

                <div class="fw-text-gray-400">
                    <span class="dashicons dashicons-no-alt"></span>
                    <?php _e( 'Disabled', 'flywp' ); ?>
                </div>
            <?php } ?>
        </div>

        <?php if ( $notice ) { ?>
            <div class="fw-bg-green-200 fw-text-green-800 fw-px-4 fw-py-1" id="fly-page-cache-notice">
                <p><?php echo esc_html( $notice ); ?></p>
            </div>
        <?php } ?>

        <div class="fw-mt-2 fw-px-4 fw-py-4 fw-fw-sm:p-6 fw-text-sm fw-text-gray-500 fw-flex">
            <div class="fw-w-2/3">
                <p class="fw-mt-0"><?php _e( 'Nginx FastCGI Cache is a very fast page caching system that boosts up your website page load speed. It\'s recommended to enable the page cache.', 'flywp' ); ?></p>
            </div>

            <?php if ( flywp()->fastcgi->enabled() ) { ?>
                <div class="fw-w-1/3 fw-text-right">
                    <a href="<?php echo esc_url( $this->fastcgi->purge_cache_url() ); ?>" class="button button-primary button-hero"><?php _e( 'Clear Cache', 'flywp' ); ?></a>
                </div>
            <?php } ?>
        </div>
        
        <div class="fw-px-4 fw-py-3 fw-fw-sm:p-6 fw-mt-5 fw-bg-gray-100 fw-p-4 fw-border-t fw-rounded-br fw-rounded-bl fw-border-gray-200 fw-text-right">
            <?php if ( flywp()->fastcgi->enabled() ) { ?>
                <a href="<?php echo esc_url( $this->fastcgi->enable_disable_url( 'disable' ) ); ?>" class="button button-secondary button-large">
                    <?php _e( 'Disable', 'flywp' ); ?>
                </a>
            <?php } else { ?>

                <a href="<?php echo esc_url( $this->fastcgi->enable_disable_url( 'enable' ) ); ?>" class="button button-primary button-large">
                    <?php _e( 'Enable Cache', 'flywp' ); ?>
                </a>
            <?php } ?>
            
            <button type="button" class="button button-secondary button-large fw-flex fw-items-center" id="fastcgi-settings-button">
                <span class="dashicons dashicons-admin-generic button-active fw-mt-1"></span>
                <?php _e( 'Settings', 'flywp' ); ?>
            </button>
        </div>

        <div class="fw-px-4 fw-py-3 fw-hidden" id="fastcgi-settings">
            <form method="POST" action="">
                <p><strong><?php _e( 'Homepage Settings', 'flywp' ); ?></strong></p>
                
                <label for="home-purge-created" class="fw-block fw-pb-3">
                    <input name="home-purge-created" type="checkbox" <?php checked( flywp()->fastcgi->get_setting( 'home_created' ), true ); ?> id="home-purge-created" value="1">
                    Flush homepage when a post is published or modified
                </label>
                    
                <label for="home-purge-deleted" class="fw-block fw-pb-3">
                    <input name="home-purge-deleted" type="checkbox" <?php checked( flywp()->fastcgi->get_setting( 'home_deleted' ), true ); ?> id="home-purge-deleted" value="1">
                    Flush homepage when a post is deleted
                </label>

                <p><strong>Single Post</strong></p>
                
                <label for="single-post-created" class="fw-block fw-pb-3">
                    <input name="single-post-created" type="checkbox" <?php checked( flywp()->fastcgi->get_setting( 'single_modified' ), true ); ?> id="single-post-created" value="1">
                    Flush the single post page when it's modified
                </label>
                
                <label for="single-post-comment" class="fw-block fw-pb-3">
                    <input name="single-post-comment" type="checkbox" <?php checked( flywp()->fastcgi->get_setting( 'single_comment' ), true ); ?> id="single-post-comment" value="1">
                    Flush the single post page when a comment is added or deleted
                </label>

                <div class="fw-pt-4 fw-text-right">
                    <button type="button" class="button button-secondary button-large" id="fastcgi-settings-close">
                        <?php _e( 'Close', 'flywp' ); ?>
                    </button>
                    <?php submit_button( __( 'Save Changes', 'flywp' ), 'primary', 'submit', false ); ?>
                    <?php wp_nonce_field( 'flywp-fastcgi-nonce', 'flywp-fastcgi-nonce' ); ?>
                </div>
            </form>
        </div>
    </div>
</div>