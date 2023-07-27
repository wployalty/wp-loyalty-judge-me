<?php
defined('ABSPATH') or die;
?>
<div id="wljm-main-page">
    <div class="wljm-main-header">
        <h1><?php echo WLJM_PLUGIN_NAME; ?> </h1>
        <div><b><?php echo "v" . WLJM_PLUGIN_VERSION; ?></b></div>
    </div>
    <div class="wljm-parent">
        <div class="wljm-body-content">
            <div id="wljm-settings" class="wljm-body-active-content active-content">
                <div class="wljm-heading-data">
                    <div class="headings">
                        <div class="heading-section">
                            <h3><?php esc_html_e("WebHooks", 'wp-loyalty-judge-me'); ?></h3>
                        </div>
                        <div class="heading-buttons">
                            <a type="button" class="wljm-button-action non-colored-button"
                               href="<?php echo isset($back_to_apps_url) && !empty($back_to_apps_url) ? $back_to_apps_url : '#'; ?>">
                                <i class="wlr wlrf-back"></i>
                                <span><?php esc_html_e("Back to WPLoyalty", 'wp-loyalty-judge-me'); ?></span>
                            </a>
                            <!--<button class="wljm-button-action colored-button" id="wljm-setting-submit-button">
                                <i class="wlr wlrf-save"></i><?php /*esc_html_e('Save', 'wp-loyalty-judge-me'); */ ?>
                            </button>-->
                        </div>
                    </div>
                </div>
                <div class="wljm-body-data">
                    <div
                        style="background-color: #ffba00;border-radius: 10px;border-color: #f5c6cb;padding: 14px;font-weight: bold;">
                        <p><?php _e('NOTE: You should have set up the Jofficial udge.me for WooCommerce plugin properly before attempting to create the webhooks. This add-on depends on the configuration from the official Judge.me plugin for WooCommerce.', 'wp-loyalty-judge-me'); ?></p>
                        <p><?php _e('Please click on the "Create" button to create a Webhook in your Judge.me account so that when a review is posted, WPloyalty can listen and reward the customer with points / rewards as per your configuration.', 'wp-loyalty-judge-me') ?></p>
                    </div>
                    <!--<form id="wljm-settings-form" method="post">
                        <div>
                            <div class="menu-title">
                                <p><?php /*esc_html_e('Is domain allowed (https):', 'wp-loyalty-judge-me'); */ ?></p>
                            </div>
                            <?php /*$is_ssl = isset($settings) && is_array($settings) && isset($settings['is_ssl']) && !empty($settings['is_ssl']) ? $settings['is_ssl'] : 'no'; */ ?>
                            <div class="menu-lists">
                                <select name="is_ssl">
                                    <option
                                        value="no" <?php /*echo $is_ssl == 'no' ? 'selected="selected"' : ''; */ ?>><?php /*esc_html_e('No', 'wp-loyalty-judge-me'); */ ?></option>
                                    <option
                                        value="yes" <?php /*echo $is_ssl == 'yes' ? 'selected="selected"' : ''; */ ?>><?php /*esc_html_e('Yes', 'wp-loyalty-judge-me'); */ ?></option>
                                </select>
                            </div>
                        </div>
                        <input type="hidden" name="action" value="wljm_save_settings">
                        <input type="hidden" name="wljm_nonce"
                               value="<?php /*echo isset($setting_nonce) && !empty($setting_nonce) ? esc_attr($setting_nonce) : ''; */ ?>">
                    </form>-->
                </div>
            </div>
            <div class="wljm-webhook-section table-content">
                <?php if (isset($review_keys) && !empty($review_keys)): ?>
                    <table>
                        <tr>
                            <th><?php esc_html_e('Webhook ID', 'wp-loyalty-judge-me') ?></th>
                            <th><?php esc_html_e('Webhook Key', 'wp-loyalty-judge-me') ?></th>
                            <th><?php esc_html_e('WebHook URL', 'wp-loyalty-judge-me') ?></th>
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
    </div>
</div>