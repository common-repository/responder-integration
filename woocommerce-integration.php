<?php
defined("ABSPATH") || exit();
/**
 * Function to get cart contents for the WooCommerce REST API.
 *
 * @param WP_REST_Request $request The request object.
 * @return WP_REST_Response|WP_Error The response object or WP_Error on failure.
 */
function woosponder_get_cart_contents($request)
{
    global $woocommerce;

    // Check if WooCommerce cart exists and is not empty
    if (isset($woocommerce->cart) && !$woocommerce->cart->is_empty()) {
        $product_ids = [];

        // Loop through each cart item
        foreach (
            $woocommerce->cart->get_cart()
            as $cart_item_key => $cart_item
        ) {
            $product_id = $cart_item["product_id"]; // Get the product ID
            $product_ids[] = $product_id; // Add the product ID to the array
        }

        // Return the product IDs in the response
        return new WP_REST_Response($product_ids, 200);
    } else {
        // Return an error if the cart is empty or doesn't exist
        return new WP_Error("wcal_no_cart", "No cart contents found.", [
            "status" => 404,
        ]);
    }
}