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
            <table>
                <tr>
                    <th><?php esc_html_e('WebHook id:', 'wp-loyalty-judge-me') ?></th>
                    <th><?php esc_html_e('WebHook Key', 'wp-loyalty-judge-me') ?></th>
                    <th><?php esc_html_e('WebHook url', 'wp-loyalty-judge-me') ?></th>
                    <th><?php esc_html_e('WebHook Actions', 'wp-loyalty-judge-me') ?></th>
                </tr>
                <?php foreach ($review_keys as $key): ?>
                    <tr class="wljm-webhook-<?php echo str_replace('/', '-', $key); ?>">
                        <td>
                            <?php if (isset($webhook_list[$key]) && !empty($webhook_list[$key])): ?>
                                <?php echo $webhook_list[$key]->id; ?>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td><?php echo $key; ?></td>
                        <td><?php if (isset($webhook_list[$key]) && !empty($webhook_list[$key])): ?>
                                <?php echo $webhook_list[$key]->url; ?>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (isset($webhook_list[$key]) && !empty($webhook_list[$key])): ?>
                                <button type="button"
                                        id="wljm-webhook-delete"
                                        data-webhook-key="<?php echo $key; ?>"><?php _e('Delete', 'wp-loyalty-judge-me') ?></button>
                            <?php else: ?>
                                <button type="button"
                                        id="wljm-webhook-create"
                                        data-webhook-key="<?php echo $key; ?>"><?php _e('Create', 'wp-loyalty-judge-me') ?></button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>
    </div>
</div>