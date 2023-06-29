<?php
/**
 * @author      Wployalty (Alagesan)
 * @license     http://www.gnu.org/licenses/gpl-2.0.html
 * @link        https://www.wployalty.net
 * */

namespace Wljm\App\Controllers;

use Wlr\App\Helpers\Input;
use Wlr\App\Helpers\Template;
use Wlr\App\Helpers\Woocommerce;

defined('ABSPATH') or die;

class Controller
{
    function addMenu()
    {
        if (Woocommerce::hasAdminPrivilege()) {
            add_menu_page(__('WPLoyalty: Judge.me', 'wp-loyalty-judge-me'), __('WPLoyalty: Judge.me', 'wp-loyalty-judge-me'), 'manage_woocommerce', WLJM_PLUGIN_SLUG, array($this, 'manageLoyaltyPages'), 'dashicons-megaphone', 57);
        }
    }

    function manageLoyaltyPages()
    {
        if (!Woocommerce::hasAdminPrivilege()) {
            wp_die(esc_html(__("Don't have access permission", 'wp-loyalty-judge-me')));
        }
        $input = new Input();
        //it will automatically add new table column,via auto generate alter query
        if ($input->get('page', NULL) == WLJM_PLUGIN_SLUG) {
            $template = new Template();
            $path = WLJM_PLUGIN_PATH . 'App/Views/Admin/main.php';
            $main_page_params = array();
            $template->setData($path, $main_page_params)->display();
        } else {
            wp_die(esc_html(__('Page query params missing...', 'wp-loyalty-judge-me')));
        }
    }

    function removeAdminNotice()
    {
        remove_all_actions('admin_notices');
    }

    function adminScripts()
    {
        if (!Woocommerce::hasAdminPrivilege()) {
            return;
        }
        $input = new Input();
        if ($input->get('page', NULL) != WLJM_PLUGIN_SLUG) {
            return;
        }
        $this->removeAdminNotice();
        wp_enqueue_style(WLJM_PLUGIN_SLUG . '-wljm-admin', WLJM_PLUGIN_URL . 'Assets/Admin/Css/wljm-admin.css', array(), WLJM_PLUGIN_VERSION . '&t=' . time());
        wp_enqueue_script(WLJM_PLUGIN_SLUG . '-wljm-admin', WLJM_PLUGIN_URL . 'Assets/Admin/Js/wljm-admin.js', array(), WLJM_PLUGIN_VERSION . '&t=' . time());
        /*wp_enqueue_style(WLR_PLUGIN_SLUG . '-alertify', WLR_PLUGIN_URL . 'Assets/Admin/Css/alertify.min.css', array(), WLR_PLUGIN_VERSION);
        wp_enqueue_script(WLR_PLUGIN_SLUG . '-alertify', WLR_PLUGIN_URL . 'Assets/Admin/Js/alertify.min.js', array(), WLR_PLUGIN_VERSION . '&t=' . time());*/
        $localize = array(
            'home_url' => get_home_url(),
            'admin_url' => admin_url(),
            'ajax_url' => admin_url('admin-ajax.php'),
        );
        wp_localize_script(WLJM_PLUGIN_SLUG . '-wljm-admin', 'wljm_localize_data', $localize);
    }

    function menuHideProperties()
    {
        ?>
        <style>
            #toplevel_page_wp-loyalty-judge-me {
                display: none !important;
            }
        </style>
        <?php
    }
}