<?php
/**
 * @author      Wployalty (Alagesan)
 * @license     http://www.gnu.org/licenses/gpl-2.0.html
 * @link        https://www.wployalty.net
 * */

namespace Wljm\App;

use Wljm\App\Controllers\Controller;

defined('ABSPATH') or die;

class Router
{
    private static $controller;

    function init()
    {
        self::$controller = empty(self::$controller) ? new Controller() : self::$controller;
        if (is_admin()) {
            add_action('admin_menu', array(self::$controller, 'addMenu'));
            add_action('network_admin_menu', array(self::$controller, 'addMenu'));
            add_action('admin_enqueue_scripts', array(self::$controller, 'adminScripts'), 100);
            add_action('admin_footer', array(self::$controller, 'menuHideProperties'));
            add_action('wp_ajax_wljm_webhook_delete', array(self::$controller, 'deleteWebHook'));
            add_action('wp_ajax_wljm_webhook_create', array(self::$controller, 'createWebHook'));
        } else {
            add_action('wp_enqueue_scripts', array(self::$controller, 'addFrontEndScripts'));
        }
        add_action('rest_api_init', array(self::$controller, 'register_wp_api_endpoints'));
    }
}