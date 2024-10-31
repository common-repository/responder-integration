<?php
defined("ABSPATH") || exit();

// Action hook to handle AJAX request for retrieving custom fields
add_action("wp_ajax_retrieve_custom_fields", "woosponder_retrieve_custom_fields");

function woosponder_retrieve_custom_fields()
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

    // Check if necessary POST data is provided
    if (!isset($_POST["list_id"]) || !isset($_POST["system_selection"])) {
        wp_send_json_error("List ID and System Selection are required");
        wp_die();
    }

    // Sanitize the received POST data
    $listId = sanitize_text_field(wp_unslash($_POST["list_id"]));
    $selectedSystem = sanitize_text_field(wp_unslash($_POST["system_selection"]));

    if ($selectedSystem == "new_responder") {
        // Correct the path as needed for your new-responder-api.php file
        require_once "new-responder-api.php";
        $Woosponder_NewResponderApi = new Woosponder_NewResponderApi();
        $customFieldsResponse = $Woosponder_NewResponderApi->fetchListFields($listId);

        if (!empty($customFieldsResponse)) {
            wp_send_json_success($customFieldsResponse);
        } else {
            wp_send_json_error("Failed to fetch custom fields");
        }
    } else {
        // Existing logic for Rav Messer
        $userKey = sanitize_text_field(get_option("woosponder_user_key"));
        $userSecret = sanitize_text_field(get_option("woosponder_user_secret"));

        $customFieldsResponse = woosponder_fetchCustomFieldsFromRavMesser($listId, $userKey, $userSecret);

        if ($customFieldsResponse) {
            wp_send_json_success(json_decode($customFieldsResponse, true));
        } else {
            wp_send_json_error("Failed to fetch custom fields");
        }
    }

    wp_die();
}