<?php
defined("ABSPATH") || exit();
require_once "RavMesserSubscriberCreator.php"; // Ensure this is correctly pathed to your API handling file

if (!function_exists('woosponder_createMultipleSubscribers')) {
    function woosponder_createMultipleSubscribers($order_id)
    {
        // Fetch the order
        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }

        // Fetch and sanitize buyer details
        $buyerEmail = sanitize_email($order->get_billing_email());
        $buyerName = sanitize_text_field($order->get_billing_first_name() . " " . $order->get_billing_last_name());
        $buyerPhone = sanitize_text_field($order->get_billing_phone());
        $transactionID = sanitize_text_field($order->get_transaction_id());
        $orderTotal = floatval($order->get_total());
        $vat = floatval($order->get_total_tax());
        $shipping = floatval($order->get_shipping_total());

        // Fetch and sanitize used coupon codes
        $usedCoupons = $order->get_coupon_codes();
        $couponCodes = implode(", ", array_map('sanitize_text_field', $usedCoupons)); // Combine all coupons into a sanitized string

        // Get current order status
        $currentStatus = 'wc-' . sanitize_text_field($order->get_status());

        // Prepare products array for combining details
        $productsByList = [];

        // Loop through items in the order
        foreach ($order->get_items() as $item) {
            $productID = intval($item->get_product_id());

            // Retrieve all connections for the product
            $connections = get_woosponder_connections($productID);

            foreach ($connections as $connection) {
                if ($connection['system_type'] !== 'rav_messer' || empty($connection['buyer_list_id'])) {
                    continue;
                }

                // Check if the connection's order status matches the current order status
                if ($connection['order_status'] === $currentStatus) {
                    $listID = $connection['buyer_list_id'];

                    // Initialize product data for the list
                    if (!isset($productsByList[$listID])) {
                        $productsByList[$listID] = [];
                    }

                    $productsByList[$listID][] = [
                        'productID' => $productID,
                        'connection' => $connection
                    ];
                }
            }
        }

        // Keep track of successfully processed subscribers to avoid redundant API calls
        $processedSubscribers = [];

        // Process subscribers for each list
        foreach ($productsByList as $listID => $products) {
            // Check if we already processed this subscriber for this list
            if (!empty($processedSubscribers[$listID][$buyerEmail])) {
                continue;
            }

            $subscriberData = woosponder_prepareSubscriberData(
                $products,
                $buyerEmail,
                $buyerName,
                $buyerPhone,
                $order_id,
                $transactionID,
                $orderTotal,
                $vat,
                $shipping,
                $couponCodes
            );

            $response = woosponder_createRavMesserSubscriber($subscriberData, $listID);

            // Mark this subscriber as processed for this list
            $processedSubscribers[$listID][$buyerEmail] = true;
        }
    }
}

if (!function_exists('woosponder_prepareSubscriberData')) {
    function woosponder_prepareSubscriberData(
        $products,
        $email,
        $name,
        $phone,
        $order_id,
        $transactionID,
        $orderTotal,
        $vat,
        $shipping,
        $couponCodes
    ) {
        $aggregatedFields = [];

        // Initialize flags for order-specific fields
        $orderIDAdded = false;
        $transactionIDAdded = false;
        $orderTotalAdded = false;
        $vatAdded = false;
        $shippingAdded = false;
        $couponCodesAdded = false;

        foreach ($products as $productData) {
            $productID = $productData['productID'];
            $connection = $productData['connection'];
            $fieldMappings = isset($connection['field_mappings']) ? $connection['field_mappings'] : [];

            $product = wc_get_product($productID);
            if (!$product) {
                continue;
            }

            foreach ($fieldMappings as $wooFieldName => $ravMesserFieldId) {
                $fieldValue = "";

                switch ($wooFieldName) {
                    case '_name':
                        $fieldValue = sanitize_text_field($product->get_name());
                        break;
                    case '_order_id':
                        if (!$orderIDAdded) {
                            $fieldValue = sanitize_text_field($order_id);
                            $orderIDAdded = true;
                        }
                        break;
                    case '_transaction_id':
                        if (!$transactionIDAdded) {
                            $fieldValue = sanitize_text_field($transactionID);
                            $transactionIDAdded = true;
                        }
                        break;
                    case '_total':
                        if (!$orderTotalAdded) {
                            $fieldValue = floatval($orderTotal);
                            $orderTotalAdded = true;
                        }
                        break;
                    case '_total_tax':
                        if (!$vatAdded) {
                            $fieldValue = floatval($vat);
                            $vatAdded = true;
                        }
                        break;
                    case '_shipping_total':
                        if (!$shippingAdded) {
                            $fieldValue = floatval($shipping);
                            $shippingAdded = true;
                        }
                        break;
                    case '_used_coupons':
                        if (!$couponCodesAdded) {
                            $fieldValue = sanitize_text_field(is_array($couponCodes)
                                ? implode(", ", $couponCodes)
                                : $couponCodes);
                            $couponCodesAdded = true;
                        }
                        break;
                    case '_sale_price':
                        $fieldValue = floatval($product->get_sale_price());
                        break;
                    case '_stock':
                        $fieldValue = intval($product->get_stock_quantity());
                        break;
                    case '_regular_price':
                        $fieldValue = floatval($product->get_regular_price());
                        break;
                    case '_sku':
                        $fieldValue = sanitize_text_field($product->get_sku());
                        break;
                }

                if (!empty($fieldValue)) {
                    if (isset($aggregatedFields[$ravMesserFieldId])) {
                        $aggregatedFields[$ravMesserFieldId] .= ", " . $fieldValue;
                    } else {
                        $aggregatedFields[$ravMesserFieldId] = $fieldValue;
                    }
                }
            }
        }

        // Ensure crucial subscriber data is included
        $subscriberData = [
            'NAME' => $name ?: $email,
            'EMAIL' => $email,
            'PHONE' => $phone,
            'ACCOUNT_STATUS' => 1,
            'DAY' => 0,
            'PERSONAL_FIELDS' => $aggregatedFields
        ];

        return [$subscriberData];
    }
}

if (!function_exists('get_woosponder_connections')) {
    function get_woosponder_connections($product_id) {
        $connections = get_post_meta($product_id, 'woosponder_connections', true);
        return $connections ? json_decode($connections, true) : [];
    }
}