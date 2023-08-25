<?php

/**
 * @package WooCommerce Notifications via Africa's Talking
 * @link https://osen.co.ke
 * @version 0.20.60
 * @since 0.20.40
 * @author Osen Concepts < hi@osen.co.ke >
 */

namespace Osen\Soft\Notify\Settings;

use Osen\Soft\Notify\Notifications\Service;

class Send extends Service
{

    public function __construct()
    {
        add_action('admin_init', [$this, 'soft_bulk_settings_init']);
        add_action('admin_menu', array($this, 'admin_menu'), 99);
        add_action('wp_ajax_process_soft_bulk_form', [$this, 'process_soft_bulk_form']);
        add_action('wp_ajax_nopriv_process_soft_bulk_form', [$this, 'process_soft_bulk_form']);
        add_action('wp_ajax_process_soft_bulk_sms_balance', [$this, 'process_soft_bulk_sms_balance']);
        add_action('wp_ajax_nopriv_process_soft_bulk_sms_balance', [$this, 'process_soft_bulk_sms_balance']);
    }

    public function admin_menu()
    {
        add_submenu_page(
            'woocommerce',
            __("Send Bulk SMS To Customers", "woocommerce"),
            __("Send Bulk SMS", "woocommerce"),
            "manage_options",
            "soft_bulk",
            [$this, "soft_bulk_options_page_html"]
        );
    }

    public function soft_bulk_settings_init()
    {

        register_setting('soft_bulk', 'soft_bulk_options');

        add_settings_section('soft_bulk_section_sms', __('Bulk SMS Sending.', 'woocommerce'), [$this, 'soft_bulk_section_soft_bulk_sms_cb'], 'soft_bulk');

        add_settings_field(
            'phone',
            __('Select Customers', 'woocommerce'),
            [$this, 'soft_bulk_fields_soft_bulk_sms_shortcode_cb'],
            'soft_bulk',
            'soft_bulk_section_sms',
            [
                'label_for'             => 'phone',
                'class'                 => 'soft_bulk_row',
                'soft_bulk_custom_data' => 'custom',
            ]
        );

        add_settings_field(
            'message',
            __('Message Content', 'woocommerce'),
            [$this, 'soft_bulk_fields_soft_bulk_sms_username_cb'],
            'soft_bulk',
            'soft_bulk_section_sms',
            [
                'label_for'             => 'message',
                'class'                 => 'soft_bulk_row',
                'soft_bulk_custom_data' => 'custom',
            ]
        );

        add_settings_field(
            'schedule',
            __('Send Later', 'woocommerce'),
            [$this, 'soft_bulk_fields_soft_bulk_sms_schedule_cb'],
            'soft_bulk',
            'soft_bulk_section_sms',
            [
                'label_for'             => 'schedule',
                'class'                 => 'soft_bulk_row',
                'soft_bulk_custom_data' => 'custom',
            ]
        );
    }

    public function soft_bulk_section_soft_bulk_sms_cb($args)
    { ?>
        <p>
            <b>SMS Balance: <span id="soft_bulk_sms_balance_amount"><?php echo esc_attr(\get_transient('soft_balance') ?? '0'); ?></span></b>
            <a id="soft_bulk_sms_balance" action="<?php echo esc_url(admin_url('admin-ajax.php')); ?>" class="button button-link">Update Balance</a>
        </p>
    <?php
    }

    public function soft_bulk_fields_soft_bulk_sms_shortcode_cb($args)
    {
        $customers     = [];
        $unpaid_orders = (array) wc_get_orders(array(
            'limit' => -1,
        ));

        foreach ($unpaid_orders as $order) {
            $customers[$order->get_billing_phone()] = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
        }
    ?>
        <select id="<?php echo esc_attr($args['label_for']); ?>" data-custom="<?php echo esc_attr($args['soft_bulk_custom_data']); ?>" name="<?php echo esc_attr($args['label_for']); ?>" class="regular-text option-tree-ui-select wc-enhanced-select" multiple>
            <option value="all">Send To All</option>
            <?php foreach ($customers as $phone => $customer) : ?>
                <option value="<?php echo $phone; ?>"><?php echo $customer; ?></option>
            <?php endforeach; ?>
        </select>
        <p class="description">
            <?php esc_html_e('Customer(s) to send SMS to. Leave Blank To Send To All.', 'soft_bulk'); ?>
        </p>
    <?php
    }

    public function soft_bulk_fields_soft_bulk_sms_username_cb($args)
    { ?>
        <textarea id="<?php echo esc_attr($args['label_for']); ?>" data-custom="<?php echo esc_attr($args['soft_bulk_custom_data']); ?>" name="<?php echo esc_attr($args['label_for']); ?>" class="regular-text" required placeholder="e.g Hi {first_name}, thank you for being a loyal customer."></textarea>
        <p class="description">
            You can use placeholders such as <code>{name}</code>, <code>{first_name}</code>, <code>{last_name}</code>, <code>{site}</code>, <code>{phone}</code> <br>to show customer name, website name and customer phone respectively.
        </p>
    <?php
    }

    public function soft_bulk_fields_soft_bulk_sms_schedule_cb($args)
    { ?>
        <input type="datetime-local" id="<?php echo esc_attr($args['label_for']); ?>" data-custom="<?php echo esc_attr($args['soft_bulk_custom_data']); ?>" name="<?php echo esc_attr($args['label_for']); ?>" class="regular-text" />
        <p class="description">
            Select date you would like to send SMS to customers.
        </p>
    <?php
    }

    /**
     * top level menu:
     * callback functions
     */
    public function soft_bulk_options_page_html()
    {
        // check user capabilities
        if (!current_user_can('manage_options')) {
            return;
        } ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form id="soft_bulk_ajax_form" action="<?php echo esc_url(admin_url('admin-ajax.php')); ?>" method="POST">
                <?php
                // output setting sections and their fields
                // (sections are registered for "soft_bulk", each field is registered to a specific section)
                do_settings_sections('soft_bulk');

                wp_nonce_field('process_soft_bulk_form', 'soft_bulk_form_nonce');
                ?>
                <input type="hidden" name="action" value="process_soft_bulk_form">
                <button class="button button-primary">Send Message</button>
            </form>
            <?php
            //add_settings_error('soft_bulk_messages', 'soft_bulk_message', __('WPay C2B Settings Updated', 'woocommerce'), 'updated');
            //settings_errors('soft_bulk_messages');
            ?>
            <script id="soft_bulk-ajax" type="text/javascript">
                jQuery(document).ready(function($) {
                    $('#soft_bulk_ajax_form').submit(function(e) {
                        e.preventDefault();

                        var form = $(this);

                        $.post(form.attr('action'), form.serialize(), function(data) {
                            if (data['messages']) {
                                // if (data['status'] == 'success') {
                                $('#wpbody-content .wrap h1').after(
                                    '<div class="updated settings-success notice is-dismissible"><p>' +
                                    messages.length + ' messages sent succesfuly' +
                                    '</p><button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button></div>'
                                );
                                // } else {
                                //     $('#wpbody-content .wrap h1').after(
                                //         '<div class="error settings-error notice is-dismissible"><p>' + data['data'] + '.</p><button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button></div>'
                                //     );
                                // }
                            } else {
                                $('#wpbody-content .wrap h1').after(
                                    '<div class="error settings-error notice is-dismissible"><p>An Error Occured.</p><button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button></div>'
                                );
                            }
                        }, 'json');
                    });


                    $('#soft_bulk_sms_balance').click(function(e) {
                        e.preventDefault();

                        var btn = $(this);

                        $.post(btn.attr('action'), {
                            action: 'process_soft_bulk_sms_balance',
                            soft_bulk_sms_balance_nonce: '<?php echo wp_create_nonce('process_soft_bulk_sms_balance'); ?>'
                        }, function(data) {
                            if (data['balance']) {
                                $('#soft_bulk_sms_balance_amount').text(data['balance']);
                            }
                        }, 'json');
                    });
                });
            </script>
        </div>
<?php
    }

    /**
     * Parse message to be sent
     *
     * @param WC_Order $order
     * @param string $message
     * @return void
     */
    public function parse(\WC_Order $order, string $message): string
    {
        $first_name = $order->get_billing_first_name();
        $last_name  = $order->get_billing_last_name();
        $order_no   = $order->get_order_number();
        $phone      = $order->get_billing_phone();
        $amount     = $order->get_total();
        $email = $order->get_billing_email();

        $variables = array(
            "first_name" => $first_name,
            "last_name"  => $last_name,
            "order"      => $order_no,
            "phone"      => $phone,
            "amount"     => $amount,
            "email"     => $email,
            "site"       => get_bloginfo('name'),
        );

        foreach ($variables as $key => $value) {
            
            $message = str_replace('{' . $key . '}', $value, $message);
        }

        return $message;
    }

    public function process_soft_bulk_form()
    {
        if (!isset($_POST['soft_bulk_form_nonce']) || !wp_verify_nonce($_POST['soft_bulk_form_nonce'], 'process_soft_bulk_form')) {
            exit(wp_send_json(['errorCode' => 'The form is not valid']));
        }

        $multiple = false;
        $message  = sanitize_textarea_field($_POST['message']);
        $phone    = sanitize_text_field($_POST['phone']);
        $customers = [];
        $schedule = $_POST['schedule'] ?? false;

        if ($phone == 'all' || is_null($phone)) {
            $phones = [];
            $customers = get_users(array('role' => 'customer'));
            $orders = wc_get_orders(array(
                'limit' => -1,
            ));

            foreach ($orders as $order) {
                $PhoneNumber = str_replace("+", "", $order->get_billing_phone());
                $phones[]    = $PhoneNumber;
            }

            $phone    = implode(',', array_unique($phones));
            $multiple = true;
        }

        wp_send_json($this->send($phone, $message, $schedule, $multiple, $customers));
    }

    function process_soft_bulk_sms_balance()
    {
        if (!isset($_POST['soft_bulk_sms_balance_nonce']) || !wp_verify_nonce($_POST['soft_bulk_sms_balance_nonce'], 'process_soft_bulk_sms_balance')) {
            exit(wp_send_json(['errorCode' => 'The form is not valid']));
        }

        return wp_send_json(['balance' => $this->wallet_balance()]);
    }
}
