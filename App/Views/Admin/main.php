<?php
defined('ABSPATH') or die;
?>
<div id="wljm-main-page">
    <div class="wljm-main-header">
        <h1><?php echo WLJM_PLUGIN_NAME; ?> </h1>
        <div><b><?php echo "v" . WLJM_PLUGIN_VERSION; ?></b></div>
    </div>
    <div class="wljm-webhook-section">
        <?php if (isset($review_keys) && !empty($review_keys)): ?>
            <h3><?php esc_html_e('WebHooks', 'wp-loyalty-judge-me') ?></h3>
            <?php foreach ($review_keys as $key): ?>
                <div class="wljm-webhook-<?php echo str_replace('/', '-', $key); ?>">
                    <label><?php esc_html_e('WebHook Key:', 'wp-loyalty-judge-me') ?><?php echo $key; ?></label>
                    <?php if (isset($webhook_list[$key]) && !empty($webhook_list[$key])): ?>
                        <p><?php esc_html_e('WebHook id:', 'wp-loyalty-judge-me') ?><?php echo $webhook_list[$key]->id; ?></p>
                        <p><?php esc_html_e('WebHook url:', 'wp-loyalty-judge-me') ?><?php echo $webhook_list[$key]->url; ?></p>
                    <?php else: ?>
                        <p><?php _e('Create Webhook', 'wp-loyalty-judge-me') ?></p>
                        <button type="button" id="wljm-webhook-<?php echo str_replace('/', '-', $key) ?>"></button>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>