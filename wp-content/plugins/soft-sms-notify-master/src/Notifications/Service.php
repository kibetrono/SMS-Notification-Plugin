<?php

/**
 * @package WooCommerce Notifications via Africa's Talking
 * @link https://osen.co.ke
 * @version 0.20.60
 * @since 0.20.40
 * @author Osen Concepts < hi@osen.co.ke >
 */

namespace Osen\Soft\Notify\Notifications;

use WC_Customer;

class Service
{
    public $statuses = [
        'PENDING_WAITING_DELIVERY'            => " Message has been processed and sent to the soft instance i.e. mobile operator with request acknowledgment from their platform. Delivery report has not yet been received, and is awaited thus the status is still pending.",
        'PENDING_ENROUTE'                     => "Message has been processed and sent to the soft instance i.e. mobile operator.",
        'PENDING_ACCEPTED'                    => "Message has been accepted and processed, and is ready to be sent to the soft instance i.e. operator.",
        "REJECTED_NETWORK"                    => "Message has been received, but the network is either out of our coverage or not setup on your account. Your account manager can inform you on the coverage status or setup the network in question.",
        "REJECTED_PREFIX_MISSING"             => " Message has been received, but has been rejected as the number is not recognized due to either incorrect number prefix or number length. This information is different for each network and is regularly updated.",
        "REJECTED_DND"                        => " Message has been received, and rejected due to the user being subscribed to DND (Do Not Disturb) services, disabling any service traffic to their number.",
        "REJECTED_SOURCE"                     => " Your account is set to accept only registered sender ID-s while the sender ID defined in the request has not been registered on your account.",
        "REJECTED_NOT_ENOUGH_CREDITS"         => " Your account is out of credits for further submission - please top up your account. For further assistance in topping up or applying for online account topup service you may contact your account manager.",
        "REJECTED_SENDER"                     => " The sender ID has been blacklisted on your account - please remove the blacklist on your account or contact Support for further assistance.",
        "REJECTED_DESTINATION"                => " The destination number has been blacklisted either at the operator request or on your account - please contact Support for more information.",
        "REJECTED_PREPAID_PACKAGE_EXPIRED"    => " Account credits have been expired past their validity period - please topup your subaccount with credits to extend the validity period.",
        "REJECTED_DESTINATION_NOT_REGISTERED" => " Your account has been setup for submission only to a single number for testing purposes - kindly contact your manager to remove the limitation.",
        "REJECTED_ROUTE_NOT_AVAILABLE"        => " Mesage has been received on the system, however your account has not been setup to send messages i.e. no routes on your account are available for further submission. Your account manager will be able to setup your account based on your preference.",
        "REJECTED_FLOODING_FILTER"            => " Message has been rejected due to a anti-flooding mechanism. By default, a single number can only receive 20 varied messages and 6 identical messages per hour. If there is a requirement, the limitation can be extended per account on request to your account manager.",
        "REJECTED_SYSTEM_ERROR"               => " The request has been rejected due to an expected system system error, please retry submission or contact our technical support team for more details.",
        "REJECTED_DUPLICATE_MESSAGE_ID"       => "The request has been rejected due to a duplicate message ID specified in the submit request, while message ID-s should be a unique value",
        "REJECTED_INVALID_UDH"                => " Message has been received, while our system detected the message was formatted incorrectly because of either an invalid ESM class parameter (fully featured binary message API method) or an inaccurate amount of characters when using esmclass:64 (UDH). For more information feel free to visit the below articles or contact our Support team for clarification. https://en.wikipedia.org/wiki/User_Data_Header, https://en.wikipedia.org/wiki/Concatenated_SMS",
        "REJECTED_MESSAGE_TOO_LONG"           => " Message has been received, but the total message length is more than 25 parts or message text which exceeds 4000 bytes as per our system limitation.",
        "MISSING_TO"                          => " The request has been received, however the 'to' parameter has not been set or it is empty, i.e. there must be valid recipients to send the message.",
        "REJECTED_INVALID_DESTINATION"        => " The request has been received, however the destination is invalid - the number prefix is not correct as it does not match a valid number prefix by any mobile operator. Number length is also taken into consideration in verifying number number validity.",
    ];

    public function get_option($option, $section = 'soft-gateway', $default = '')
    {
        $options = get_option($section);
        return $options[$option] ?? $default;
    }

    public function wallet_balance()
    {
        $url = 'https://account.softwareske.com/smsAPI?balance';

        $response = wp_remote_get(
            \add_query_arg(
                array(
                    'apikey' => $this->get_option('key'),
                    'apitoken' => $this->get_option('secret')
                ),
                $url
            ),
            array(
                'headers' => array(
                    'Accept'        => 'application/json',
                ),
            )
        );

        if (is_wp_error($response)) {
            $balance = 'Could not connect to Soft SMS';
        } else {
            $response = json_decode($response['body'], true);
            $balance  = $response['balance'] ?? 0;

            \set_transient('soft_balance', $balance, 60 * 60);
        }

        return $balance;
    }

    /**
     * Parse customer message to be sent
     *
     * @param string $message
     * @param WC_Customer $customer
     * @return void
     */
    public function parse_customer_msg(string $message, \WC_Customer $customer): string
    {
        $first_name = $customer->get_billing_first_name();
        $last_name  = $customer->get_billing_last_name();
        $phone      = $customer->get_billing_phone();
        $email = $customer->get_billing_email();

        $variables  = array(
            "first_name" => $first_name,
            "last_name"  => $last_name,
            "phone"      => $phone,
            "email"      => $email,
            "site"       => get_bloginfo('name'),
        );

        foreach ($variables as $key => $value) {
            $message = str_replace('{' . $key . '}', $value, $message);
        }

        return $message;
    }

    /**
     * Send customer message
     *
     * @param string $message
     * @param bool|string $schedule
     * @param WC_Customer $customer
     * @return void
     */
    public function send($to, $message, $schedule = false, $multiple = false, $customers = [])
    {
        $receipients = strip_tags(trim($to));
        $from        = $this->get_option('shortcode');
        $phones      = array();

        if (strpos($receipients, ',') !== false) {
            $phones = explode(',', $receipients);
        } else {
            $phones = [$receipients];
        }

        $base = 'https://account.softwareske.com/smsAPI?sendsms';

        foreach ($phones as $index => $phone) {
            $phones[$index] = "254" . substr(trim($phone), -9);
        }

        $phones = \array_unique($phones);

        foreach ($phones as $phone) {
            $url    = \add_query_arg(array(
                'apikey'   => $this->get_option('key'),
                'apitoken' => $this->get_option('token'),
                'type'     => 'sms',
                'from'     => $from,
                'to'       => $phone,
                'text'     => $message,
                'route'    => 0,
            ), $base);

            if ($schedule) {
                $schedule = explode('T', $schedule);
                $url      = \add_query_arg(array('scheduledate' => $schedule[0]), $url);
            }

            $response = wp_remote_post(
                $url,
                array(
                    'headers' => array(
                        'Accept'       => 'application/json',
                    ),
                )
            );
        }

        return is_wp_error($response)
            ? array('errorCode' => 1, 'errorMessage' => $response->get_error_message())
            : json_decode($response['body'], true);
    }
}
