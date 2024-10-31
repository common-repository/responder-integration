<?php
function woosponder_delete_connection() {
    // Verify the nonce for security
    if (!check_ajax_referer('woosponder_delete_connection_nonce', 'security', false)) {
        wp_send_json_error('Nonce verification failed.');
        wp_die();
    }

    // Sanitize and validate the product ID, connection type, and connection index from the AJAX request
    $product_id = isset($_POST["product_id"]) ? intval($_POST["product_id"]) : 0;
    $connection_type = isset($_POST["connection_type"]) ? sanitize_text_field($_POST["connection_type"]) : "";
    $connection_index = isset($_POST["connection_index"]) ? intval($_POST["connection_index"]) : -1;

    // Validate the sanitized inputs
    if ($product_id > 0 && !empty($connection_type) && $connection_index >= 0) {
        // Retrieve existing connections
        $existing_connections = get_post_meta($product_id, "woosponder_connections", true);
        if (is_string($existing_connections)) {
            $existing_connections = json_decode($existing_connections, true);
        }

        if (!is_array($existing_connections) || !isset($existing_connections[$connection_index])) {
            wp_send_json_error("Invalid connection index.");
            wp_die();
        }

        // Get the specific connection
        $connection = $existing_connections[$connection_index];

        // Remove the specific type from the connection
        if ($connection_type === 'buyer' && isset($connection['buyer_list_id'])) {
            unset($connection['buyer_list_id'], $connection['field_mappings'], $connection['order_status'], $connection['buyer_tags']);
        } elseif ($connection_type === 'interested' && isset($connection['interested_list_id'])) {
            unset($connection['interested_list_id'], $connection['interested_tags']);
        } else {
            wp_send_json_error("Invalid connection type for the specified index.");
            wp_die();
        }

        // If both buyer and interested lists are removed, delete the whole connection
        if (empty($connection['buyer_list_id']) && empty($connection['interested_list_id'])) {
            unset($existing_connections[$connection_index]);
        } else {
            // Otherwise, update the existing connection
            $existing_connections[$connection_index] = $connection;
        }

        // If there are no connections left, delete the meta key
        if (empty($existing_connections)) {
            delete_post_meta($product_id, "woosponder_connections");
        } else {
            update_post_meta($product_id, "woosponder_connections", json_encode($existing_connections));
        }

        wp_send_json_success("Connection deleted successfully.");
    } else {
        wp_send_json_error("Invalid product ID, connection type, or connection index.");
    }

    // Always die in an AJAX handler
    wp_die();
}

// Hook the function to wp_ajax action that handles AJAX requests for logged-in users
add_action("wp_ajax_woosponder_delete_connection", "woosponder_delete_connection");