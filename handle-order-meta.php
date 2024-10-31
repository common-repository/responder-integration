<?php
defined("ABSPATH") || exit();

/**
 * Hook into WooCommerce order creation to store order ID and custom field mapping.
 */
add_action(
    "woocommerce_checkout_update_order_meta",
    "woosponder_store_order_id_in_order_meta"
);

/**
 * Store the order ID and optional custom field mapping in the order meta.
 *
 * @param int $order_id The ID of the order.
 */
function woosponder_store_order_id_in_order_meta($order_id)
{
    // Check if the custom field mapping for order ID is set by the user
    // This assumes that the value is sent via a form and available in $_POST
    $custom_field_mapping = isset($_POST["order_id_mapping_field"])
        ? sanitize_text_field($_POST["order_id_mapping_field"])
        : "";

    // Store the order ID in the order meta
    update_post_meta(
        $order_id,
        "_custom_order_id",
        sanitize_text_field($order_id)
    );

    // Optionally, store the mapping field if set
    if (!empty($custom_field_mapping)) {
        update_post_meta(
            $order_id,
            "_custom_order_id_mapping_field",
            $custom_field_mapping
        );
    }
}

/**
 * Retrieve stored order ID and custom field mapping from order meta.
 *
 * @param int $order_id The ID of the order.
 * @return array The order ID and custom field mapping.
 */
function woosponder_retrieve_order_id_and_mapping($order_id)
{
    // Sanitize the order ID before using it in get_post_meta
    $order_id = intval($order_id);

    $order_id_stored = sanitize_text_field(get_post_meta($order_id, "_custom_order_id", true));
    $custom_field_mapping = sanitize_text_field(get_post_meta($order_id, "_custom_order_id_mapping_field", true));

    return [
        "order_id" => $order_id_stored,
        "custom_field_mapping" => $custom_field_mapping,
    ];
}