<?php

/**
 * @package WooCommerce SMS Notifications
 * @link https://osen.co.ke
 * @version 1.0.0
 * @since 0.20.40
 * @author Osen Concepts < hi@osen.co.ke >
 * 
 * Plugin Name: WooCommerce SMS Notifications
 * Plugin URI: https://osen.co.ke
 * Description: Send Sms Notifications when WooCommerce order status changes or after registration.
 * Version: 1.0.0
 * Author: Osen Concepts
 * Author URI: https://osen.co.ke
 *
 * Requires at least: 4.6
 * Tested up to: 5.4
 * 
 * WC requires at least: 3.5.0
 * WC tested up to: 4.2
 * 
 * License: GPLv3
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html

 * Copyright 2021  Osen Concepts 

 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 3, as
 * published by the Free Software Foundation.

 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.

 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USAv
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

register_activation_hook(__FILE__, function () {
    if (!is_plugin_active('woocommerce/woocommerce.php')) {
        deactivate_plugins(plugin_basename(__FILE__));

        add_action('admin_notices', function () {
            $class = 'notice notice-error is-dismissible';
            $message = __('Please Install/Activate WooCommerce for this extension to work..', 'woocommerce');

            printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class), esc_html($message));
        });
    }
});

add_filter('plugin_action_links_' . plugin_basename(__FILE__), function ($links) {
    return array_merge(
        $links,
        array(
            '<a href="' . admin_url('admin.php?page=soft_notify') . '">&nbsp;Configure</a>',
        )
    );
});

function soft_enqueue_select2_jquery()
{
    wp_register_style('select2css', '//cdnjs.cloudflare.com/ajax/libs/select2/3.4.8/select2.css', false, '1.0', 'all');
    wp_register_script('select2', '//cdnjs.cloudflare.com/ajax/libs/select2/3.4.8/select2.js', array('jquery'), '1.0', true);
    wp_enqueue_style('select2css');
    wp_enqueue_script('select2');
}
add_action('admin_enqueue_scripts', 'soft_enqueue_select2_jquery');

add_action('admin_footer', function () { ?>
    <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('.option-tree-ui-select').select2();
        });
    </script>
<?php
});

// initialize plugin
use Osen\Soft\Notify\Notifications\Alert;
use Osen\Soft\Notify\Settings\Send;
use Osen\Soft\Notify\Settings\Admin;

require_once plugin_dir_path(__FILE__) . 'vendor/autoload.php';

add_action('plugins_loaded', function () {
    // Load admin settings
    new Admin;
    new Send;
});

add_action('init', function () {
    // Load our alert class
    new Alert;
});
