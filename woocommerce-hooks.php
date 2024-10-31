<?php
defined("ABSPATH") || exit();
// Assuming the inclusion of both API handling files for New Responder and Rav Messer
require_once plugin_dir_path(__FILE__) . "NewResponderCreateMultiple.php";
require_once plugin_dir_path(__FILE__) . "CreateMultiple.php";

function woosponder_order_status_change($order_id, $old_status, $new_status)
{
    $order = wc_get_order($order_id);
    if (!$order) {
        return;
    }

    $triggerAction = false; // Initialize trigger action flag

    // Initialize arrays for handling New Responder and Rav Messer lists
    $newResponderLists = [];
    $ravMesserLists = [];

    // Loop through items in the order
    foreach ($order->get_items() as $item) {
        $productID = $item->get_product_id();

        // Retrieve all connections for the product
        $connections = get_post_meta($productID, 'woosponder_connections', true);
        $connections = $connections ? json_decode($connections, true) : [];

        foreach ($connections as $connection) {
            $desired_status = isset($connection['order_status']) ? $connection['order_status'] : '';

            // Correct the status comparison
            if ("wc-" . $new_status === $desired_status) {
                $triggerAction = true; // Set flag to true if a product matches the desired status

                $systemType = isset($connection['system_type']) ? $connection['system_type'] : '';

                if ($systemType == "new_responder") {
                    $newResponderLists[$connection['buyer_list_id']][] = $productID;
                } elseif ($systemType == "rav_messer") {
                    $ravMesserLists[$connection['buyer_list_id']][] = $productID;
                }
            }
        }
    }

    if (!$triggerAction) {
        return; // Exit if no products match the desired action status
    }

    // Retrieve buyer details from the order
    $buyerEmail = $order->get_billing_email();
    $buyerName = $order->get_billing_first_name() . " " . $order->get_billing_last_name();
    $buyerPhone = $order->get_billing_phone();
    $transactionID = $order->get_transaction_id();
    $orderTotal = $order->get_total();
    $vat = $order->get_total_tax();
    $shipping = $order->get_shipping_total();

    // Fetch used coupon codes from the order
    $usedCoupons = $order->get_coupon_codes();
    $couponCodes = implode(", ", $usedCoupons); // Combine all used coupon codes into a string

    // Process New Responder subscriptions if there are matching lists
    if (!empty($newResponderLists)) {
        woosponder_createMultipleNewResponderSubscribers($order_id);
    }

    // Process Rav Messer subscriptions if there are matching lists
    if (!empty($ravMesserLists)) {
        woosponder_createMultipleSubscribers($order_id);
    }
}

// Hook into status changes for all orders, providing the ability to react to any status change
add_action(
    "woocommerce_order_status_changed",
    "woosponder_order_status_change",
    10,
    3
);