<?php
defined("ABSPATH") || exit();
// NewResponderCreateMultiple.php file
require_once "new-responder-api.php";

if (!function_exists('woosponder_createMultipleNewResponderSubscribers')) {
    function woosponder_createMultipleNewResponderSubscribers($order_id)
    {
        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }

        $api = new Woosponder_NewResponderApi();

        // Fetch buyer details
        $buyerEmail = $order->get_billing_email();
        $buyerName = $order->get_billing_first_name() . " " . $order->get_billing_last_name();
        $buyerPhone = $order->get_billing_phone();
        $transactionID = $order->get_transaction_id();
        $orderTotal = $order->get_total();
        $vat = $order->get_total_tax();
        $shipping = $order->get_shipping_total();
        $usedCoupons = $order->get_coupon_codes(); // Fetch used coupon codes
        $couponCodes = implode(", ", $usedCoupons); // Combine coupon codes into a string

        // Get current order status
        $currentStatus = 'wc-' . $order->get_status();

        // Keep track of processed subscribers to avoid redundant API calls
        static $processedSubscribers = []; // Use static to persist across function calls
        $listProductData = []; // To aggregate product details for each list

        // Loop through items in the order
        foreach ($order->get_items() as $item) {
            $productID = $item->get_product_id();

            // Retrieve all connections for the product
            $connections = get_post_meta($productID, 'woosponder_connections', true);
            $connections = $connections ? json_decode($connections, true) : [];

            foreach ($connections as $connection) {
                if ($connection['system_type'] !== 'new_responder' || empty($connection['buyer_list_id'])) {
                    continue;
                }

                // Check if the connection's order status matches the current order status
                if ($connection['order_status'] === $currentStatus) {
                    $listID = $connection['buyer_list_id'];

                    if (!isset($listProductData[$listID])) {
                        $listProductData[$listID] = [];
                    }

                    // Combine product details
                    $listProductData[$listID][] = [
                        'productID' => $productID,
                        'connection' => $connection
                    ];
                }
            }
        }

        // Prepare subscriber data and send to New Responder
        foreach ($listProductData as $listID => $products) {
            // Check if we already processed this subscriber for this list
            if (!empty($processedSubscribers[$listID][$buyerEmail])) {
                continue;
            }

            $subscriberData = woosponder_prepareNewResponderSubscriberData(
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

            $response = $api->addSubscriber($subscriberData);

            if (!empty($response["success"])) {
                $processedSubscribers[$listID][$buyerEmail] = true; // Mark this subscriber as processed
            }
        }
    }
}

if (!function_exists('woosponder_prepareNewResponderSubscriberData')) {
    function woosponder_prepareNewResponderSubscriberData(
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
        $tagsIds = [];
        $tagsNames = [];
        $personalFields = [];
        
        // Initialize flags for order-specific fields
        $orderSpecificFieldsAdded = [
            "_order_id" => false,
            "_transaction_id" => false,
            "_total" => false,
            "_total_tax" => false,
            "_shipping_total" => false,
            "_used_coupons" => false,
        ];

        foreach ($products as $productData) {
            $productID = $productData['productID'];
            $connection = $productData['connection'];
            $fieldMappings = isset($connection['field_mappings']) ? $connection['field_mappings'] : [];

            $product = wc_get_product($productID);
            if (!$product) {
                continue;
            }

            foreach ($fieldMappings as $wooFieldName => $newResponderFieldId) {
                $fieldValue = "";

                if (!array_key_exists($wooFieldName, $orderSpecificFieldsAdded)) {
                    $fieldValue = $wooFieldName === "_name" ? $product->get_name() : get_post_meta($productID, $wooFieldName, true);
                } else {
                    if ($orderSpecificFieldsAdded[$wooFieldName]) {
                        continue; // Skip if already added
                    }

                    switch ($wooFieldName) {
                        case "_order_id":
                            $fieldValue = $order_id;
                            break;
                        case "_transaction_id":
                            $fieldValue = $transactionID;
                            break;
                        case "_total":
                            $fieldValue = $orderTotal;
                            break;
                        case "_total_tax":
                            $fieldValue = $vat;
                            break;
                        case "_shipping_total":
                            $fieldValue = $shipping;
                            break;
                        case "_used_coupons":
                            $fieldValue = $couponCodes;
                            break;
                    }
                    $orderSpecificFieldsAdded[$wooFieldName] = true;
                }

                if (!empty($fieldValue)) {
                    if (isset($personalFields[$newResponderFieldId])) {
                        $personalFields[$newResponderFieldId] .= ', ' . $fieldValue;
                    } else {
                        $personalFields[$newResponderFieldId] = $fieldValue;
                    }
                }
            }

            // Collect tags for each product
            $tagsArray = isset($connection['buyer_tags']) ? $connection['buyer_tags'] : [];
            if (is_array($tagsArray)) {
                foreach ($tagsArray as $tag) {
                    if (is_numeric($tag)) {
                        $tagsIds[] = (int) $tag;
                    } else {
                        $tagsNames[] = $tag;
                    }
                }
            }
        }

        // Deduplicate tags
        $tagsIds = array_unique($tagsIds);
        $tagsNames = array_unique($tagsNames);

        $subscriberData = [
            "first" => explode(" ", trim($name))[0],
            "last" => substr($name, strpos($name, " ") + 1),
            "email" => $email,
            "phone" => $phone,
            "list_ids" => [$products[0]['connection']['buyer_list_id']],
            "personal_fields" => $personalFields,
            "tags" => $tagsIds,
            "tags_names" => $tagsNames,
            "override" => true,
            "rejoin" => true,
            "unsubscribed" => false,
            "disable_notification" => true,
        ];

        return $subscriberData;
    }
}