<?php

/**
 * @package WooCommerce Notifications via Africa's Talking
 * @link https://osen.co.ke
 * @version 0.20.60
 * @since 0.20.40
 * @author Osen Concepts < hi@osen.co.ke >
 */

namespace Osen\Soft\Notify\Settings;

class Admin
{

    /**
     * @param Base $settings
     */
    private $settings;

    /**
     * @param array $status
     */
    private $statuses = [];

    public function __construct()
    {
        $this->settings = new Base;
        $this->statuses = \array_merge(['wc-created' => 'Created'], wc_get_order_statuses());

        add_action('admin_init', array($this, 'admin_init'));
        add_action('admin_menu', array($this, 'admin_menu'), 99);
        
    }

    public function admin_init()
    {
        
        //set the settings
        $this->settings->set_sections($this->get_settings_sections());
        $this->settings->set_fields($this->get_settings_fields());

        //initialize settings
        $this->settings->admin_init();
    }

    public function admin_menu()
    {
        add_submenu_page(
            'woocommerce',
            'SMS Notifications',
            'SMS Notifications',
            'manage_options',
            'soft_notify',
            array($this, 'settings_page')
        );
    }

    public function get_settings_sections()
    {
        
        $sections = array(
            array(
                'id'      => 'soft-gateway',
                'title'   => __('Gateway Options', 'woocommerce'),
                'heading' => __('Gateway Options', 'woocommerce'),
                'desc'    => 'Setup your configuration here.',
            ),
            array(
                'id'      => 'soft-registration',
                'title'   => __('Registration', 'woocommerce'),
                'heading' => __('On Customer Registration', 'woocommerce'),
                'desc'    => 'You can use placeholders such as <code>{first_name}</code>, <code>{last_name}</code>, <code>{site}</code>, <code>{email}</code>, <code>{phone}</code> <br>to show customer name, website name and customer phone respectively.',
            ),
        );

        foreach ($this->statuses as $key => $status) {
            if ($this->get_option("enable-section-$key") == 'on') {
                $sections[] = array(
                    'id'      => "soft-{$key}",
                    'title'   => 'Order ' . ucwords($status),
                    'heading' => 'On ' . ucwords($status) . ' Status',
                    'desc'    => 'You can use placeholders such as <code>{first_name}</code>, <code>{last_name}</code>, <code>{order}</code>, <code>{site}</code>, <code>{email}</code>, <code>{phone}</code><br> to show customer name, order number, website name and customer phone respectively.',
                );
            }
        }

        return $sections;
    }

    /**
     * Returns all the settings fields
     *
     * @return array settings fields
     */
    public function get_settings_fields()
    {

        $settings_fields = array(
            'soft-gateway'      => array(
                array(
                    'name'              => 'key',
                    'label'             => __('API Key', 'woocommerce'),
                    'type'              => 'text',
                    'placeholder'       => 'Your API Key',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                array(
                    'name'              => 'token',
                    'label'             => __('API Token', 'woocommerce'),
                    'type'              => 'text',
                    'placeholder'       => 'Your API Token',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                array(
                    'name'              => 'shortcode',
                    'label'             => __('Sender ID', 'woocommerce'),
                    'type'              => 'text',
                    'placeholder'       => 'Your Sender ID',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                array(
                    'name'        => 'phones',
                    'label'       => __('Admin Contacts', 'woocommerce'),
                    'desc'        => __('Comma-separated list of phone numbers to notify on status change', 'woocommerce'),
                    'type'        => 'textarea',
                    'placeholder' => 'E.g 254...',
                ),

            ),

            'soft-registration' => array(
                array(
                    'name'  => 'customer_enable',
                    'label' => __('Customer Enable', 'woocommerce'),
                    'desc'  => __('Notify customer on registration', 'woocommerce'),
                    'type'  => 'checkbox',
                ),
                array(
                    'name'    => 'customer_msg',
                    'label'   => __('Customer Message', 'woocommerce'),
                    'desc'    => __('Message to send to customer on registration', 'woocommerce'),
                    'type'    => 'textarea',
                    'default' => 'Hello {first_name} {last_name}, thank you for registering on {site}. Your email is {email}, and your phone number is {phone}.',
                ),
                array(
                    'name'  => 'admin_enable',
                    'label' => __('Admin Enable', 'woocommerce'),
                    'desc'  => __('Notify admin(s) on registration', 'woocommerce'),
                    'type'  => 'checkbox',
                ),
                array(
                    'name'    => 'admin_msg',
                    'label'   => __('Admin Message', 'woocommerce'),
                    'desc'    => __('Message to send to admin(s) on customer registration', 'woocommerce'),
                    'type'    => 'textarea',
                    'rows'    => 2,
                    'default' => 'A new customer has just registered on {site}.',
                ),
            ),
        );



        foreach ($this->statuses as $key => $status) {
            \array_push($settings_fields['soft-gateway'], array(
                'name'  => "enable-section-$key",
                'label' => "Order $status",
                'desc'  => __(" Show {$status} tab", 'woocommerce'),
                'type'  => 'checkbox',
            ));
        }

        foreach ($this->statuses as $key => $status) {
            $settings_fields["soft-{$key}"] = array(
                array(
                    'name'  => 'customer_enable',
                    'label' => __('Customer Enable', 'woocommerce'),
                    'desc'  => __('Notify customer when order is ' . \strtolower($status), 'woocommerce'),
                    'type'  => 'checkbox',
                ),
                array(
                    'name'    => 'customer_msg',
                    'label'   => __('Customer Message', 'woocommerce'),
                    'desc'    => __('Message to send to customer when order status is ' . \strtolower($status), 'woocommerce'),
                    'type'    => 'textarea',
                    'default' => 'Hello {first_name} {last_name}, the status of your order {order} on {site} is ' . strtolower($status) . '. Your email is {email}, and your phone number is {phone}.',
                ),
                array(
                    'name'  => 'admin_enable',
                    'label' => __('Admin Enable', 'woocommerce'),
                    'desc'  => __('Notify admin(s) when order is ' . \strtolower($status), 'woocommerce'),
                    'type'  => 'checkbox',
                ),
                array(
                    'name'    => 'admin_msg',
                    'label'   => __('Admin Message', 'woocommerce'),
                    'desc'    => __('Message to send to admin(s) when order status is ' . \strtolower($status), 'woocommerce'),
                    'type'    => 'textarea',
                    'rows'    => 2,
                    'default' => 'An order on {site} has ' . \strtolower($status) . ' status.',
                ),
            );
        }

        return $settings_fields;
    }

    public function get_option($option, $section = 'soft-gateway', $default = '')
    {
        $options = get_option($section);
        return $options[$option] ?? $default;
    }

    public function settings_page()
    {
        echo '<div class="wrap">';

        echo '<h1>' . esc_html(get_admin_page_title()) . '</h1>';

        $this->settings->show_navigation();
        $this->settings->show_forms();

        echo '</div>';
    }
}
