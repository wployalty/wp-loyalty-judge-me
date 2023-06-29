<?php
/**
 * Plugin Name: WPLoyalty - Judge.me Compatibility
 * Plugin URI: https://www.wployalty.net
 * Description: WPLoyalty - Judge.me Compatibility
 * Version: 1.0.0
 * Author: Wployalty
 * Slug: wp-loyalty-judge-me
 * Text Domain: wp-loyalty-judge-me
 * Domain Path: /i18n/languages/
 * Requires at least: 4.9.0
 * WC requires at least: 6.5
 * WC tested up to: 7.8
 * Contributors: Alagesan
 * Author URI: https://wployalty.net/
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * WPLoyalty: 1.2.4
 * WPLoyalty Page Link: wp-loyalty-judge-me
 */

defined('ABSPATH') or die;
if (!function_exists('isWployaltyActiveOrNot')) {
    function isWployaltyActiveOrNot()
    {
        $active_plugins = apply_filters('active_plugins', get_option('active_plugins', array()));
        if (is_multisite()) {
            $active_plugins = array_merge($active_plugins, get_site_option('active_sitewide_plugins', array()));
        }
        return in_array('wp-loyalty-rules/wp-loyalty-rules.php', $active_plugins, false) || in_array('wp-loyalty-rules-lite/wp-loyalty-rules-lite.php', $active_plugins, false) || in_array('wployalty/wp-loyalty-rules-lite.php', $active_plugins, false);
    }
}
if (!function_exists('isWoocommerceActive')) {
    function isWoocommerceActive()
    {
        $active_plugins = apply_filters('active_plugins', get_option('active_plugins', array()));
        if (is_multisite()) {
            $active_plugins = array_merge($active_plugins, get_site_option('active_sitewide_plugins', array()));
        }
        return in_array('woocommerce/woocommerce.php', $active_plugins) || array_key_exists('woocommerce/woocommerce.php', $active_plugins);
    }
}
if (!isWoocommerceActive() || !isWployaltyActiveOrNot()) {
    return;
}
//Define the plugin version
defined('WLJM_PLUGIN_VERSION') or define('WLJM_PLUGIN_VERSION', '1.0.0');
defined('WLJM_PLUGIN_NAME') or define('WLJM_PLUGIN_NAME', 'WPLoyalty - Judge.me Compatibility');
defined('WLJM_TEXT_DOMAIN') or define('WLJM_TEXT_DOMAIN', 'wp-loyalty-judge-me');
defined('WLJM_PLUGIN_SLUG') or define('WLJM_PLUGIN_SLUG', 'wp-loyalty-judge-me');
defined('WLJM_PLUGIN_PATH') or define('WLJM_PLUGIN_PATH', __DIR__ . '/');
defined('WLJM_PLUGIN_URL') or define('WLJM_PLUGIN_URL', plugin_dir_url(__FILE__));
defined('WLJM_PLUGIN_FILE') or define('WLJM_PLUGIN_FILE', __FILE__);

add_action('before_woocommerce_init', function () {
    if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
    }
});
if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
    return;
}
require __DIR__ . '/vendor/autoload.php';
$plugin_rel_path = 'wp-loyalty-judge-me/i18n/languages/';
load_plugin_textdomain('wp-loyalty-judge-me', false, $plugin_rel_path);
if (class_exists('\Wljm\App\Router')) {
    $router = new \Wljm\App\Router();
    $router->init();
}