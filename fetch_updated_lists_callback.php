<?php
defined("ABSPATH") || exit();
// Include the API files for Rav Messer and New Responder
require_once "rav-messer-api.php";
require_once "new-responder-api.php";

function woosponder_fetch_updated_lists_callback()
{
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

    // Check user permissions
    if (!current_user_can("manage_options")) {
        wp_send_json_error("Permission denied.");
        wp_die();
    }

    // Sanitize the selected system input
    $selected_system = isset($_POST["selected_system"])
        ? sanitize_text_field(wp_unslash($_POST["selected_system"]))
        : "";

    if ($selected_system === "rav_messer") {
        $user_key = sanitize_text_field(get_option("woosponder_user_key"));
        $user_secret = sanitize_text_field(get_option("woosponder_user_secret"));

        if (empty($user_key) || empty($user_secret)) {
            wp_send_json_error("User key or secret not set");
            wp_die();
        }

        $api_response = woosponder_rsp_woosponder_connectToRavMesser($user_key, $user_secret);

        if (woosponder_rsp_woosponder_is_json($api_response)) {
            $lists = json_decode($api_response, true);
            if ($lists && isset($lists["LISTS"])) {
                wp_send_json_success($lists["LISTS"]);
            } else {
                wp_send_json_error("Error decoding lists or lists key not found");
            }
        } else {
            wp_send_json_error("API response is not in JSON format");
        }
    } elseif ($selected_system === "new_responder") {
        $Woosponder_NewResponderApi = new Woosponder_NewResponderApi();
        $lists = $Woosponder_NewResponderApi->fetchLists();

        if ($lists) {
            wp_send_json_success($lists);
        } else {
            wp_send_json_error("Error fetching lists from New Responder");
        }
    } else {
        wp_send_json_error("No system selected or system not recognized");
    }

    wp_die();
}

// Ensure this function is tied to the correct WordPress AJAX action
add_action("wp_ajax_fetch_updated_lists", "woosponder_fetch_updated_lists_callback");