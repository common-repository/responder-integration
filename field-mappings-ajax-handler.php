<?php
defined("ABSPATH") || exit();

// Hook for handling the AJAX request for saving field mappings
add_action("wp_ajax_woosponder_save_system_type", "woosponder_save_system_type");

function woosponder_save_system_type() {
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

    // Retrieve and sanitize the product ID
    $product_id = isset($_POST["product_id"]) ? intval($_POST["product_id"]) : 0;

    // Retrieve and sanitize the connection details
    $connection = isset($_POST["connection"]) ? $_POST["connection"] : [];
    $system_type = isset($connection["system_type"]) ? sanitize_text_field($connection["system_type"]) : "";
    $buyer_list_id = isset($connection["buyer_list_id"]) ? sanitize_text_field($connection["buyer_list_id"]) : "";
    $interested_list_id = isset($connection["interested_list_id"]) ? sanitize_text_field($connection["interested_list_id"]) : "";
    $order_status = isset($connection["order_status"]) ? sanitize_text_field($connection["order_status"]) : "";
    $field_mappings = isset($connection["field_mappings"]) ? woosponder_sanitize_mappings_data($connection["field_mappings"]) : [];

    // Handle tags (no separation between new and existing)
    $buyer_tags = isset($connection["buyer_tags"]) ? array_map('sanitize_text_field', $connection["buyer_tags"]) : [];
    $interested_tags = isset($connection["interested_tags"]) ? array_map('sanitize_text_field', $connection["interested_tags"]) : [];

    // Validate the existence of the product ID and system type
    if (!$product_id || empty($system_type)) {
        wp_send_json_error("Invalid product ID or system type is missing.");
        exit();
    }

    // Prepare the data structure
    $connection_data = [
        "system_type" => $system_type,
        "buyer_list_id" => $buyer_list_id,
        "interested_list_id" => $interested_list_id,
        "field_mappings" => $field_mappings,
        "order_status" => $order_status,
        "buyer_tags" => $buyer_tags,
        "interested_tags" => $interested_tags,
    ];

    // Save the data structure as JSON
    update_post_meta($product_id, "woosponder_connections", wp_json_encode([$connection_data], JSON_UNESCAPED_UNICODE));

    wp_send_json_success("System types, list IDs, mappings, and tags saved successfully.");
}

// Sanitize and validate mappings data
function woosponder_sanitize_mappings_data($data) {
    $clean_data = [];
    foreach ($data as $key => $value) {
        $clean_data[sanitize_text_field($key)] = sanitize_text_field($value);
    }
    return $clean_data;
}