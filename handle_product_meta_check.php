<?php
defined("ABSPATH") || exit();

// Action hook to handle AJAX request for checking product meta
add_action("wp_ajax_check_product_meta", "woosponder_check_product_meta");

function woosponder_check_product_meta() {
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

    // Check if product ID is provided
    if (!isset($_POST["product_id"])) {
        wp_send_json_error("Product ID is required");
        wp_die();
    }

    // Sanitize and retrieve the product ID from the AJAX request
    $product_id = intval(wp_unslash($_POST["product_id"]));

    // Retrieve the woosponder_connections meta key
    $existing_meta = get_post_meta($product_id, 'woosponder_connections', true);

    // Ensure existing_meta is an array
    if ($existing_meta) {
        $existing_meta = json_decode($existing_meta, true);
        if (!is_array($existing_meta)) {
            $existing_meta = [];
        }
    } else {
        $existing_meta = [];
    }

    // Send back the existing meta information
    wp_send_json_success(["existing_meta" => $existing_meta]);

    wp_die(); // Always end AJAX functions with wp_die()
}