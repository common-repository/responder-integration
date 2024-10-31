<?php
defined("ABSPATH") || exit();

add_action("wp_ajax_woosponder_save_system_type", "woosponder_handle_save_system_type");

function woosponder_handle_save_system_type() {
    // Security check: Verify nonce
    if (!check_ajax_referer("woosponder_ajax_nonce", "security", false)) {
        wp_send_json_error("Nonce verification failed.");
        exit();
    }

    // Check if the user has the required capability
    if (!current_user_can("manage_options")) {
        wp_send_json_error("Insufficient permissions.");
        exit();
    }

    // Retrieve the product ID and connection data
    $product_id = isset($_POST["product_id"]) ? intval($_POST["product_id"]) : 0;
    $connection = isset($_POST["connection"]) ? wp_unslash($_POST["connection"]) : null;

    if (!$product_id) {
        wp_send_json_error("Invalid product ID.");
        exit();
    }

    if (!$connection) {
        wp_send_json_error("Connection data is missing.");
        exit();
    }

    // Sanitize connection data
    $connection = array_map('sanitize_text_field', $connection);

    // Retrieve existing connections
    $existing_connections = get_post_meta($product_id, "woosponder_connections", true);
    if (!$existing_connections) {
        $existing_connections = [];
    } else {
        if (is_array($existing_connections)) {
            $existing_connections = json_decode(json_encode($existing_connections), true);
        } else {
            $existing_connections = json_decode($existing_connections, true);
        }
    }

    // Check if the connection already exists
    $index = false;
    foreach ($existing_connections as $key => $existing_connection) {
        if (
            $existing_connection['system_type'] === $connection['system_type'] &&
            $existing_connection['buyer_list_id'] === $connection['buyer_list_id'] &&
            $existing_connection['order_status'] === $connection['order_status']
        ) {
            $index = $key;
            break;
        }
    }

    if ($index !== false) {
        // Update existing connection
        $existing_connections[$index] = $connection;
    } else {
        // Append new connection
        $existing_connections[] = $connection;
    }

    // Save the updated connections
    $update_result = update_post_meta($product_id, "woosponder_connections", wp_json_encode($existing_connections, JSON_UNESCAPED_UNICODE));

    if ($update_result === false) {
        wp_send_json_error("Failed to save connection.");
    } else {
        wp_send_json_success("Connection saved successfully.");
    }
}