<?php
defined("ABSPATH") || exit();
require_once "new-responder-api.php"; // Ensure this path is correct

add_action("wp_ajax_fetch_subscriber_tags", "woosponder_fetch_subscriber_tags");

function woosponder_fetch_subscriber_tags() {
    // Check if the nonce is set and verify it for security
    if (!isset($_POST["nonce"]) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST["nonce"])), "fetch_subscriber_tags_nonce")) {
        wp_send_json_error("Invalid nonce");
        return;
    }

    // Ensure the user has the required capability to perform this action
    if (!current_user_can("manage_options")) {
        wp_send_json_error("Insufficient permissions");
        return;
    }

    // Get the search term from the POST data
    $searchTerm = isset($_POST["searchTerm"]) ? sanitize_text_field(wp_unslash($_POST["searchTerm"])) : "";

    // Attempt to fetch the tags
    $api = new Woosponder_NewResponderApi();
    $tags = $api->fetchSubscriberTags(); // Assume this fetches all tags

    if (!$tags) {
        wp_send_json_error("Error fetching tags");
        return;
    }

    // Filter tags based on searchTerm
    $filteredTags = array_filter($tags, function ($tag) use ($searchTerm) {
        return empty($searchTerm) || stripos($tag["text"], $searchTerm) !== false;
    });

    // Sort the filtered tags by their similarity to searchTerm
    usort($filteredTags, function ($a, $b) use ($searchTerm) {
        similar_text($searchTerm, $a["text"], $percentA);
        similar_text($searchTerm, $b["text"], $percentB);
        return $percentB <=> $percentA;
    });

    // Transform the tags to the expected format for Select2
    $formattedTags = array_map(function ($tag) {
        return ["id" => sanitize_text_field($tag["id"]), "text" => sanitize_text_field($tag["text"])];
    }, $filteredTags);

    if (!empty($formattedTags)) {
        wp_send_json_success($formattedTags);
    } else {
        wp_send_json_error("No matching tags found");
    }

    wp_die(); // Ensure execution is halted here
}