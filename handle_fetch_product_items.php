<?php
defined("ABSPATH") || exit();

// Include necessary WooCommerce functions
if (!function_exists("wc_get_product")) {
    include_once plugin_dir_path(__FILE__) . "../woocommerce/includes/wc-product-functions.php";
}

// Action hook to handle AJAX request for retrieving product items
add_action("wp_ajax_retrieve_product_items", "woosponder_retrieve_product_items");

function woosponder_retrieve_product_items() {
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
    $product_id = intval($_POST["product_id"]);

    // Fetch product details using WooCommerce functions
    $product = wc_get_product($product_id);
    if (!$product) {
        wp_send_json_error("Product not found.");
        wp_die();
    }

    // Prepare the product items data
    $product_items = [
        "_name" => sanitize_text_field($product->get_name()),
        "_sku" => sanitize_text_field($product->get_sku()),
        "_regular_price" => sanitize_text_field($product->get_price()),
        "_stock" => sanitize_text_field($product->get_stock_quantity()),
        "_sale_price" => sanitize_text_field($product->get_sale_price()),
    ];

    // Check if order ID is provided
    if (isset($_POST["order_id"])) {
        $order_id = intval($_POST["order_id"]);
        $product_items["order_id"] = $order_id;
    }

    // Fetch existing connections
    $existing_connections = get_woosponder_connections($product_id);
    $product_items["existing_connections"] = $existing_connections;

    // Send back the product items
    wp_send_json_success($product_items);
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