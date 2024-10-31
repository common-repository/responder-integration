<?php
defined("ABSPATH") || exit();

add_action("wp_ajax_get_product_details", "woosponder_get_product_details");

function woosponder_get_product_details() {
    // Security check: Verify nonce
    if (!isset($_POST["security"]) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST["security"])), "woosponder_ajax_nonce")) {
        wp_send_json_error("Nonce verification failed.");
        wp_die();
    }

    // Check if product ID is provided
    if (!isset($_POST["product_id"])) {
        wp_send_json_error("Product ID is required");
        wp_die();
    }

    // Sanitize and retrieve the product ID from the AJAX request
    $product_id = intval(wp_unslash($_POST["product_id"]));

    // Fetch product details using WooCommerce CRUD methods
    $product = wc_get_product($product_id);
    if (!$product) {
        wp_send_json_error("Product not found.");
        wp_die();
    }

    // Fetching existing product details
    $product_details = [
        "_name" => sanitize_text_field($product->get_name()),
        "_sku" => sanitize_text_field($product->get_sku()),
        "_regular_price" => floatval($product->get_regular_price()), // Price should be treated as float
        "_stock" => intval($product->get_stock_quantity()),
        "_sale_price" => floatval($product->get_sale_price()), // Price should be treated as float
    ];

    // Fetching existing connections
    $existing_connections = get_post_meta($product_id, "woosponder_connections", true);
    if (!$existing_connections) {
        $existing_connections = [];
    } else {
        $existing_connections = json_decode($existing_connections, true);
    }
    $product_details["existing_connections"] = $existing_connections;

    // Send back the combined product details
    wp_send_json_success($product_details);
}