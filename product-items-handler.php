<?php
defined("ABSPATH") || exit();
require_once plugin_dir_path(__FILE__) . "rav-messer-api.php";

// Action hook to handle AJAX request for retrieving product items
add_action("wp_ajax_retrieve_product_items", "woosponder_retrieve_product_items");

function woosponder_retrieve_product_items() {
    // Security check: Verify nonce
    if (
        !isset($_POST["security"]) ||
        !wp_verify_nonce(
            sanitize_text_field(wp_unslash($_POST["security"])),
            "woosponder_ajax_nonce"
        )
    ) {
        wp_send_json_error("Nonce verification failed.");
        wp_die();
    }

    // Check if list ID is provided
    if (!isset($_POST["list_id"]) || empty($_POST["list_id"])) {
        wp_send_json_error("List ID is required");
        wp_die();
    }

    // Get the list ID from the AJAX request
    $listId = sanitize_text_field(wp_unslash($_POST["list_id"]));

    // Fetch product items based on the selected list
    $productItems = fetchProductItemsFromWooCommerce($listId); // Implement this function in rav-messer-api.php

    if ($productItems) {
        // Add existing connections to each product item
        $sanitizedProductItems = array_map(function ($item) {
            $item = array_map("sanitize_text_field", $item);
            $item['existing_connections'] = get_woosponder_connections($item['product_id']); // Ensure each product item has a 'product_id' key
            return $item;
        }, $productItems);

        wp_send_json_success($sanitizedProductItems);
    } else {
        wp_send_json_error("Failed to fetch product items");
    }

    wp_die();
}

// Helper function to fetch existing connections
function get_woosponder_connections($product_id) {
    $existing_connections = get_post_meta($product_id, "woosponder_connections", true);
    if (!$existing_connections) {
        return [];
    } else {
        return json_decode($existing_connections, true);
    }
}