<?php
defined("ABSPATH") || exit();

function woosponder_save_mapping_fetch_product_details() {
    // Check for required parameters
    if (!isset($_POST["product_id"]) || !isset($_POST["connection"]["buyer_list_id"]) || !isset($_POST["connection"]["system_type"]) || !isset($_POST["connection"]["order_status"])) {
        wp_send_json_error("Missing product ID, list ID, system type, or order status.");
        wp_die();
    }

    // Sanitize the received POST data
    $product_id = intval($_POST["product_id"]);
    $buyer_list_id = sanitize_text_field($_POST["connection"]["buyer_list_id"]);
    $system_type = sanitize_text_field($_POST["connection"]["system_type"]);
    $order_status = sanitize_text_field($_POST["connection"]["order_status"]);
    $interested_list_id = isset($_POST["connection"]["interested_list_id"]) ? sanitize_text_field($_POST["connection"]["interested_list_id"]) : '';

    // Retrieve existing connections
    $existing_connections = get_post_meta($product_id, "woosponder_connections", true);
    if (!$existing_connections) {
        $existing_connections = [];
    } else {
        $existing_connections = json_decode($existing_connections, true);
    }

    // Initialize connection
    $connection = [
        "system_type" => $system_type,
        "buyer_list_id" => $buyer_list_id,
        "interested_list_id" => $interested_list_id,
        "field_mappings" => [],
        "order_status" => $order_status,
    ];

    // Add tags only if the system type is new_responder
    if ($system_type === 'new_responder') {
        // Combine new and existing tags
        $buyer_tags_existing = isset($_POST['connection']['buyer_tags']['existing']) ? array_map('sanitize_text_field', $_POST['connection']['buyer_tags']['existing']) : [];
        $buyer_tags_new = isset($_POST['connection']['buyer_tags']['new']) ? array_map('woosponder_utf8_decode', $_POST['connection']['buyer_tags']['new']) : [];
        $buyer_tags = array_merge($buyer_tags_existing, $buyer_tags_new);

        $interested_tags_existing = isset($_POST['connection']['interested_tags']['existing']) ? array_map('sanitize_text_field', $_POST['connection']['interested_tags']['existing']) : [];
        $interested_tags_new = isset($_POST['connection']['interested_tags']['new']) ? array_map('woosponder_utf8_decode', $_POST['connection']['interested_tags']['new']) : [];
        $interested_tags = array_merge($interested_tags_existing, $interested_tags_new);

        $connection["buyer_tags"] = $buyer_tags;
        $connection["interested_tags"] = $interested_tags;
    }

    // Check if there is any mappings data and add it to the connection
    if (isset($_POST["connection"]["field_mappings"])) {
        $mappings = $_POST["connection"]["field_mappings"];
        if (is_array($mappings)) {
            // Sanitize each mapping key and value
            $clean_mappings = array_map("sanitize_text_field", $mappings);
            $connection["field_mappings"] = $clean_mappings;
        } else {
            wp_send_json_error("Invalid mappings data.");
            wp_die();
        }
    }

    // Remove any existing interested connection for the same product
    foreach ($existing_connections as $key => $existing_connection) {
        if ($existing_connection['system_type'] === $system_type && !empty($existing_connection['interested_list_id']) && $existing_connection['interested_list_id'] === $interested_list_id) {
            unset($existing_connections[$key]);
        }
    }

    // Append new connection
    $existing_connections[] = $connection;

    // Save the updated connections
    update_post_meta($product_id, "woosponder_connections", wp_json_encode($existing_connections, JSON_UNESCAPED_UNICODE));

    // Get product object
    $product = wc_get_product($product_id);
    if (!$product) {
        wp_send_json_error("Invalid product ID.");
        wp_die();
    }

    // Prepare product details
    $product_details = [
        "_name" => sanitize_text_field($product->get_name()),
        "_sku" => sanitize_text_field($product->get_sku()),
        "_regular_price" => floatval($product->get_price()),
        "_stock" => intval($product->get_stock_quantity()),
    ];

    // Check if the product has a sale price and add it to the array
    if ($product->is_on_sale()) {
        $product_details["_sale_price"] = floatval($product->get_sale_price());
    }

    wp_send_json_success($product_details);
    wp_die();
}

// Hook the function to handle the AJAX request
add_action("wp_ajax_save_mapping_fetch_product_details", "woosponder_save_mapping_fetch_product_details");

function woosponder_utf8_decode($data) {
    return mb_convert_encoding($data, 'UTF-8', 'auto');
}