<?php
defined("ABSPATH") || exit();

function woosponder_display_gdpr_messages_on_checkout() {
    // Check if the current user (or guest) has opted out
    $user_opted_out = is_user_logged_in()
        ? get_user_meta(get_current_user_id(), "woosponder_abandoned_cart_opt_out", true)
        : isset($_COOKIE["woosponder_abandoned_cart_opt_out"]);

    // If the user has opted out, don't display any GDPR messages
    if ($user_opted_out) {
        return;
    }

    // Get the saved GDPR messages
    $gdpr_message_guest = get_option("woosponder_gdpr_message_guest", "");
    $gdpr_message_registered = get_option("woosponder_gdpr_message_registered", "");
    $gdpr_opt_out_message = get_option("woosponder_gdpr_opt_out_message", "");
    $gdpr_cart_opt_out_message = get_option("woosponder_gdpr_cart_opt_out_message", "");

    // Determine if the user is logged in or not and select appropriate message
    $message_to_display = is_user_logged_in() ? $gdpr_message_registered : $gdpr_message_guest;

    // Output the GDPR message
    if (!empty($message_to_display)) {
        echo '<div class="woosponder-gdpr-message">' . wp_kses_post($message_to_display) . "</div>";
    }

    // Replace the opt-out link with a checkbox for GDPR consent
    if (!empty($gdpr_opt_out_message)) {
        echo '<div class="woosponder-gdpr-opt-out" id="woosponder_gdpr_container">';
        echo '<label for="woosponder_gdpr_consent_checkbox">';
        echo '<input type="checkbox" id="woosponder_gdpr_consent_checkbox" name="woosponder_gdpr_consent" value="1">';
        echo wp_kses_post($gdpr_opt_out_message);
        echo "</label>";
        echo "</div>";
    }

    // Output the GDPR Cart Tracking Opt-Out Message (hidden initially)
    if (!empty($gdpr_cart_opt_out_message)) {
        echo '<div class="woosponder-gdpr-cart-opt-out-message" id="gdprCartOptOutMessage" style="display: none;">' . wp_kses_post($gdpr_cart_opt_out_message) . "</div>";
    }
}

add_action("woocommerce_after_checkout_billing_form", "woosponder_display_gdpr_messages_on_checkout");

function woosponder_gdpr_consent() {
    // Verify the nonce for security
    if (!check_ajax_referer("woosponder_gdpr_consent_nonce", "nonce", false)) {
        wp_send_json_error(["message" => "Nonce verification failed."]);
        wp_die();
    }

    if (is_user_logged_in()) {
        $user_id = get_current_user_id();
        $consent = isset($_POST["consent"]) ? sanitize_text_field($_POST["consent"]) : "no";

        // Update the user meta with GDPR consent status
        update_user_meta($user_id, "woosponder_gdpr_consent", $consent);
        wp_send_json_success(["message" => "GDPR consent status updated."]);
    } else {
        wp_send_json_error(["message" => "User not logged in."]);
    }

    wp_die(); // Ensures AJAX request is terminated properly
}

add_action("wp_ajax_store_gdpr_consent", "woosponder_gdpr_consent");
add_action("wp_ajax_nopriv_store_gdpr_consent", "woosponder_gdpr_consent"); // If you want to handle this for non-logged-in users
?>