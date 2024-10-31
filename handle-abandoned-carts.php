<?php
defined("ABSPATH") || exit();
require_once "RavMesserSubscriberCreator.php"; // Adjust the path as needed
require_once "rav-messer-api.php";

function woosponder_capture_checkout_details() {
    // Check if WooCommerce is active
    if (class_exists("WooCommerce")) {
        if (is_checkout() && !is_wc_endpoint_url()) {
            // Enqueue checkout JavaScript
            wp_enqueue_script(
                "wcal-checkout-js",
                plugin_dir_url(__FILE__) . "ajax-functions/wcal-checkout.js",
                ["jquery"],
                filemtime(plugin_dir_path(__FILE__) . "ajax-functions/wcal-checkout.js"),
                true
            );

            // Localize script with parameters for AJAX and user login status
            wp_localize_script("wcal-checkout-js", "wcal_checkout_params", [
                "ajax_url" => admin_url("admin-ajax.php"),
                "wcal_nonce" => wp_create_nonce("wcal-checkout-nonce"),
                "is_user_logged_in" => is_user_logged_in(),
            ]);
        }
    }
}
add_action("wp_enqueue_scripts", "woosponder_capture_checkout_details");

function woosponder_process_checkout_fields() {
    if (!check_ajax_referer("wcal-checkout-nonce", "security", false)) {
        wp_die("Nonce verification failed", "", ["response" => 403]);
    }

    $user_opted_out = is_user_logged_in()
        ? get_user_meta(get_current_user_id(), "woosponder_abandoned_cart_opt_out", true)
        : isset($_COOKIE["woosponder_abandoned_cart_opt_out"]);
    if ($user_opted_out) {
        wp_die("User opted out", "", ["response" => 200]);
    }

    $posted_data = $_POST;

    $email = isset($posted_data["email"]) ? sanitize_email($posted_data["email"]) : "";
    $phone = isset($posted_data["phone"]) ? sanitize_text_field($posted_data["phone"]) : "";
    $first_name = isset($posted_data["first_name"]) ? sanitize_text_field($posted_data["first_name"]) : "";
    $last_name = isset($posted_data["last_name"]) ? sanitize_text_field($posted_data["last_name"]) : "";
    $name = trim($first_name . " " . $last_name);

    // Retrieve interested tags from the AJAX request
    $interested_tags = isset($posted_data['interested_tags']) ? array_map('sanitize_text_field', $posted_data['interested_tags']) : [];

    // Track already processed list IDs to prevent redundant submissions
    $processedLists = [];

    if (!empty($email) && class_exists("WC_Cart")) {
        // Instantiate Woosponder_NewResponderApi
        $Woosponder_NewResponderApi = new Woosponder_NewResponderApi();

        foreach (WC()->cart->get_cart() as $cart_item) {
            $product_id = intval($cart_item["product_id"]);
            $woosponder_connections = get_post_meta($product_id, "woosponder_connections", true);

            if (!empty($woosponder_connections)) {
                $woosponder_connections = json_decode($woosponder_connections, true);

                if (is_array($woosponder_connections)) {
                    foreach ($woosponder_connections as $connection) {
                        $system_type = sanitize_text_field($connection['system_type']);
                        $list_ids = isset($connection['interested_list_ids']) ? array_map('sanitize_text_field', $connection['interested_list_ids']) : [sanitize_text_field($connection['interested_list_id'])];

                        foreach ($list_ids as $list_id) {
                            // Check if this list ID has already been processed to avoid redundant API calls
                            if (in_array($list_id, $processedLists)) {
                                continue; // Skip this list ID
                            }

                            if ($system_type == "new_responder" && !empty($list_id)) {
                                $tagsResult = woosponder_retrieveTagsForNewResponder($product_id); // Fetch tags for the product

                                // Merge interested tags with retrieved tags
                                $tagsResult["tags"] = array_merge($tagsResult["tags"], $interested_tags);

                                $subscriberDataNewResponder = [
                                    "first" => $first_name,
                                    "last" => $last_name,
                                    "email" => $email,
                                    "phone" => $phone,
                                    "list_ids" => [$list_id], // Ensure this is an array of list IDs
                                    "tags" => !empty($tagsResult["tags"]) ? $tagsResult["tags"] : [], // Numeric IDs
                                    "tags_names" => !empty($tagsResult["tags_names"]) ? $tagsResult["tags_names"] : [], // Tag names
                                ];

                                // Send this subscriber data to New Responder
                                $result = $Woosponder_NewResponderApi->addSubscriber($subscriberDataNewResponder);

                                // Mark this list as processed
                                $processedLists[] = $list_id;
                            } elseif ($system_type == "rav_messer" && !empty($list_id)) {
                                $subscriberData = [
                                    [
                                        "NAME" => $name ?: $email,
                                        "EMAIL" => $email,
                                        "PHONE" => $phone,
                                        "ACCOUNT_STATUS" => 0,
                                    ],
                                ];

                                $result = woosponder_createRavMesserSubscriber($subscriberData, $list_id);

                                // Mark this list as processed
                                $processedLists[] = $list_id;
                            }
                        }
                    }
                }
            }
        }
    }

    wp_die(); // Properly close the function
}

function woosponder_retrieveTagsForNewResponder($product_id) {
    // Retrieve serialized tags from the post meta
    $tagsSerialized = get_post_meta($product_id, "woosponder_connections", true);
    $connectionsArray = json_decode($tagsSerialized, true);
    $tags = ["tags" => [], "tags_names" => []];

    if (!empty($connectionsArray) && is_array($connectionsArray)) {
        foreach ($connectionsArray as $connection) {
            if (isset($connection['interested_tags']) && is_array($connection['interested_tags'])) {
                foreach ($connection['interested_tags'] as $tag) {
                    if (is_numeric($tag)) {
                        $tags["tags"][] = intval($tag);
                    } else {
                        $tags["tags_names"][] = sanitize_text_field($tag);
                    }
                }
            }
        }
    }

    return $tags;
}

function woosponder_handle_opt_out() {
    // Verify nonce for security
    if (!check_ajax_referer("wcal-checkout-nonce", "nonce", false)) {
        wp_die("Nonce verification failed", "", ["response" => 403]);
    }

    // Logic for handling opt-out
    if (is_user_logged_in()) {
        $user_id = get_current_user_id();
        update_user_meta($user_id, "woosponder_abandoned_cart_opt_out", true);
    } else {
        // For guests, consider using a cookie or other means to track opt-out status
        setcookie(
            "woosponder_abandoned_cart_opt_out",
            "yes",
            time() + 365 * DAY_IN_SECONDS,
            COOKIEPATH,
            COOKIE_DOMAIN
        );
    }

    // Send a success response back to the JavaScript
    wp_send_json_success(["message" => "Opt-out successful"]);
}

// Register the AJAX actions for logged-in and non-logged-in users
add_action("wp_ajax_woosponder_opt_out", "woosponder_handle_opt_out");
add_action("wp_ajax_nopriv_woosponder_opt_out", "woosponder_handle_opt_out");

// Hook functions to WordPress AJAX actions
add_action("wp_ajax_woosponder_process_checkout_fields", "woosponder_process_checkout_fields");
add_action("wp_ajax_nopriv_woosponder_process_checkout_fields", "woosponder_process_checkout_fields");