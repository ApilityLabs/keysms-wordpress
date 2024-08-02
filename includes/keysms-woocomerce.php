<?php

namespace KeySMS;

use Automattic\WooCommerce\Admin\Overrides\Order;

if (!defined('ABSPATH')) {
    exit;
}

add_action('woocommerce_order_actions', 'KeySMS\\add_order_meta_box_actions');
add_action('woocommerce_order_action_keysms_send_sms', 'KeySMS\\send_sms');

function add_order_meta_box_actions($actions)
{
    $actions['keysms_send_sms'] = __('Send SMS to customer', 'keysms');
    return $actions;
}

/**
 * @param Order; $order 
 */
function send_sms($order)
{
    if (!current_user_can('edit_shop_orders')) {
        return;
    }

    // Get the phone number from the order
    $phone = $order->get_billing_phone();
    $firstname = $order->get_billing_first_name();
    $lastname = $order->get_billing_last_name();
    $name = $firstname . ' ' . $lastname;
    $orderId = $order->get_id();

    if (empty($phone)) {
        return;
    }

    // Redirect to the send SMS page
    $url = add_query_arg(
        [
            'page' => 'keysms',
            'phone' => $phone,
        ],
        get_admin_url() . 'admin.php'
    );

    wp_redirect($url);
    exit;
}
