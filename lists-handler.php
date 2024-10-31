<?php
defined("ABSPATH") || exit();
// Include your rav-messer-api.php and new-responder-api.php files
require_once "rav-messer-api.php";
require_once "new-responder-api.php";

// Handler for Rav Messer lists
add_action("wp_ajax_fetch_rav_messer_lists", "woosponder_fetch_rav_messer_lists");
function woosponder_fetch_rav_messer_lists()
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

    // Sanitize options
    $user_key = sanitize_text_field(get_option("woosponder_user_key"));
    $user_secret = sanitize_text_field(get_option("woosponder_user_secret"));

    if (empty($user_key) || empty($user_secret)) {
        wp_send_json_error("User key/secret not set");
        wp_die();
    }

    $apiResponse = woosponder_connectToRavMesser($user_key, $user_secret);

    $decodedResponse = json_decode($apiResponse, true);

    if (isset($decodedResponse["LISTS"])) {
        $lists = $decodedResponse["LISTS"];
        $formattedLists = array_map(function ($list) {
            return [
                "id" => sanitize_text_field($list["ID"]),
                "name" => sanitize_text_field($list["DESCRIPTION"]),
            ];
        }, $lists);

        wp_send_json_success($formattedLists);
    } else {
        wp_send_json_error("No lists found in API response");
    }

    wp_die();
}

// Handler for New Responder lists
add_action("wp_ajax_fetch_new_responder_lists", "woosponder_fetch_new_responder_lists");
function woosponder_fetch_new_responder_lists()
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

    $Woosponder_NewResponderApi = new Woosponder_NewResponderApi();
    $lists = $Woosponder_NewResponderApi->fetchLists();

    if (!empty($lists)) {
        $formattedLists = array_map(function ($list) {
            return [
                "id" => sanitize_text_field($list["id"]),
                "name" => sanitize_text_field($list["name"]),
            ];
        }, $lists);

        wp_send_json_success($formattedLists);
    } else {
        wp_send_json_error("No lists found");
    }

    wp_die();
}
