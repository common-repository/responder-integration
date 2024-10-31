<?php
defined("ABSPATH") || exit();
/**
 * Plugin Name: Responder Integration
 * Plugin URI:  https://plugins.responder.co.il/
 * Description: Integrates WooCommerce with the Rav Messer mailing platform for advanced email marketing campaigns.
 * Version:     1.1.0
 * Author:      natashasconnections
 * Author URI:  https://www.responder.co.il/
 * License:     GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: responderintegration
 */

// Include necessary files


include_once plugin_dir_path(__FILE__) . "footer-content.php";
require_once plugin_dir_path(__FILE__) . "rav-messer-api.php";
require_once plugin_dir_path(__FILE__) . "new-responder-api.php";
include_once plugin_dir_path(__FILE__) . "lists-tab.php";
include_once plugin_dir_path(__FILE__) . "lists-handler.php";
include_once plugin_dir_path(__FILE__) . "field-mappings-ajax-handler.php";
include_once plugin_dir_path(__FILE__) . "handle_get_product_details.php";
include_once plugin_dir_path(__FILE__) . "fetch_updated_lists_callback.php";
include_once plugin_dir_path(__FILE__) . "handle_retrieve_custom_fields.php";
include_once plugin_dir_path(__FILE__) . "handle_save_mapping_fetch_product_details.php";
require_once plugin_dir_path(__FILE__) . "RavMesserSubscriberCreator.php";
require_once plugin_dir_path(__FILE__) . "woocommerce-hooks.php";
require_once plugin_dir_path(__FILE__) . "CreateMultiple.php";
require_once plugin_dir_path(__FILE__) . "NewResponderCreateMultiple.php";
require_once plugin_dir_path(__FILE__) . "handle-abandoned-carts.php"; // Include handle-abandoned-carts.php
require_once plugin_dir_path(__FILE__) . "woocommerce-integration.php"; // Include woocommerce-integration.php
require_once plugin_dir_path(__FILE__) . "system-selection.php";
include_once plugin_dir_path(__FILE__) . "active-connections-tab.php";
require_once plugin_dir_path(__FILE__) . "ajax-delete-connection.php";
require_once plugin_dir_path(__FILE__) . "ajax-fetch-connections.php";
require_once plugin_dir_path(__FILE__) . "handle_product_meta_check.php";
require_once plugin_dir_path(__FILE__) . "register-gdpr-settings.php";
require_once plugin_dir_path(__FILE__) . "frontend-display.php";
require_once plugin_dir_path(__FILE__) . "fetchSubscriberTags.php";

// Register AJAX action for fetching subscriber tags
add_action("wp_ajax_fetch_subscriber_tags", "woosponder_fetch_subscriber_tags");

function woosponder_add_editor_styles()
{
    add_editor_style(); // This line ensures editor styles are loaded. Adjust or remove if not needed.
}

function woosponder_custom_mce_buttons_2($buttons)
{
    array_unshift($buttons, "styleselect"); // Add style selector to the beginning of the toolbar

    array_push($buttons, "fontsizeselect");
    $initArray["fontsize_formats"] =
        "10px 12px 14px 16px 18px 20px 24px 28px 32px 36px";

    return $buttons;
}

add_action("admin_init", "woosponder_add_editor_styles");
add_filter("mce_buttons_2", "woosponder_custom_mce_buttons_2");

function woosponder_enqueue_styles()
{
    wp_enqueue_style(
        "woosponder-custom-styles",
        plugin_dir_url(__FILE__) . "woosponder-styles.css"
    );
}

add_action("wp_enqueue_scripts", "woosponder_enqueue_styles");

// Function to enqueue checkout scripts
function woosponder_enqueue_scripts()
{
    if (is_checkout()) {
        wp_enqueue_script(
            "wcal-checkout-js",
            plugin_dir_url(__FILE__) . "ajax-functions/wcal-checkout.js",
            ["jquery"],
            null,
            true
        );

        // Localize the script with AJAX URL and nonce
        wp_localize_script("wcal-checkout-js", "ajax_object", [
            "ajaxurl" => admin_url("admin-ajax.php"),
            "nonce" => wp_create_nonce("wcal_checkout_nonce"), // Create a specific nonce for checkout
            "gdpr_consent_nonce" => wp_create_nonce(
                "woosponder_gdpr_consent_nonce"
            ), // Corrected: Add GDPR consent nonce
        ]);
    }
}
add_action("wp_enqueue_scripts", "woosponder_enqueue_scripts");

// Function to enqueue admin scripts and styles
function woosponder_enqueue_admin_scripts() {

    // Enqueue styles
    // First, enqueue Select2's stylesheet
    wp_enqueue_style("select2-css", plugin_dir_url(__FILE__) . "vendor/select2.min.css");

    // Then, enqueue your custom stylesheet, which will override Select2's styles
    wp_enqueue_style("woosponder-admin-styles", plugin_dir_url(__FILE__) . "woosponder-styles.css");

    // Enqueue JavaScript files
    wp_enqueue_script("woosponder-update-lists-dropdown", plugin_dir_url(__FILE__) . "ajax-functions/updateListsDropdown.js", ["jquery"], null, true);
    wp_enqueue_script("woosponder-system-selection-handler", plugin_dir_url(__FILE__) . "ajax-functions/systemSelectionHandler.js", ["jquery"], null, true);
    wp_enqueue_script("woosponder-product-details-ajax", plugin_dir_url(__FILE__) . "ajax-functions/product-details-ajax.js", ["jquery"], null, true);
    wp_enqueue_script("woosponder-mapping-interface", plugin_dir_url(__FILE__) . "ajax-functions/mapping-interface.js", ["jquery"], null, true);
    wp_enqueue_script("woosponder-active-connections-handler", plugin_dir_url(__FILE__) . "ajax-functions/active-connections-handler.js", ["jquery"], null, true);
    wp_enqueue_script("woosponder-search", plugin_dir_url(__FILE__) . "ajax-functions/woosponder-search.js", ["jquery"], null, true);
    wp_enqueue_script("select2-js", plugin_dir_url(__FILE__) . "vendor/select2.min.js", ["jquery"], null, true);

    // Create a nonce for general AJAX requests
    $nonce = wp_create_nonce("woosponder_ajax_nonce");

    // Create a separate nonce for the delete connection action
    $delete_nonce = wp_create_nonce("woosponder_delete_connection_nonce");

    // Create a new nonce specifically for 'fetch_subscriber_tags' action
    $fetch_tags_nonce = wp_create_nonce("fetch_subscriber_tags_nonce");

    // Localize the script with nonces and the AJAX URL
    wp_localize_script("woosponder-active-connections-handler", "ajax_object", [
        "ajaxurl" => admin_url("admin-ajax.php"),
        "nonce" => $nonce, // General nonce for other actions
        "delete_nonce" => $delete_nonce, // Specific nonce for delete action
        "fetch_tags_nonce" => $fetch_tags_nonce, // New nonce for fetch subscriber tags
    ]);
}
add_action("admin_enqueue_scripts", "woosponder_enqueue_admin_scripts");


// Function to enqueue admin styles - might be redundant if styles are already enqueued above

function woosponder_add_admin_menu()
{
    add_menu_page(
        "Woosponder Integration",
        "Woosponder",
        "manage_options",
        "woosponder",
        "woosponder_admin_page",
        "dashicons-email"
    );
}
add_action("admin_menu", "woosponder_add_admin_menu");

function woosponder_load_active_connections()
{
    // Call the function that outputs the 'Active Connections' content
    woosponder_fetch_active_connections();

    // Important: Always end an AJAX handler with wp_die() to ensure proper response format
    wp_die();
}

add_action(
    "wp_ajax_load_active_connections",
    "woosponder_load_active_connections"
);

//Checkout block register
function woosponder_custom_checkout_block_register_block()
{
    $activation_status = get_option(
        "woosponder_gdpr_activation_status",
        "deactivate"
    );
    if ($activation_status !== "activate") {
        return;
    }

    $asset_file = include plugin_dir_path(__FILE__) . "build/index.asset.php";

    wp_register_script(
        "custom-checkout-block-editor-script",
        plugins_url("build/index.js", __FILE__),
        $asset_file["dependencies"],
        $asset_file["version"]
    );

    register_block_type("custom/checkout-block", [
        "editor_script" => "custom-checkout-block-editor-script",
        "render_callback" => "woosponder_custom_checkout_block_render_callback",
    ]);
}

function woosponder_custom_checkout_block_render_callback($attributes, $content)
{
    $gdpr_message = is_user_logged_in()
        ? get_option("woosponder_gdpr_message_registered", "")
        : get_option("woosponder_gdpr_message_guest", "");

    $gdpr_opt_out_message = get_option(
        "woosponder_gdpr_opt_out_message",
        "I agree to the GDPR terms."
    );

    $gdpr_cart_opt_out_message = get_option(
        "woosponder_gdpr_cart_opt_out_message",
        "Thank you for your consent!"
    );

    $gdpr_consent_checkbox_html =
        '<div class="woosponder-gdpr-opt-out" id="woosponder_gdpr_container">' .
        '<label for="woosponder_gdpr_consent_checkbox">' .
        '<input type="checkbox" id="woosponder_gdpr_consent_checkbox" name="woosponder_gdpr_consent" value="1" required>' .
        "<span>" .
        esc_html($gdpr_opt_out_message) .
        "</span>" .
        "</label>" .
        "</div>";

    $thank_you_message_html =
        '<div id="gdprCartOptOutMessage" style="display:none;">' .
        esc_html($gdpr_cart_opt_out_message) .
        "</div>";

    $output =
        "<div class=\"custom-checkout-block\">" .
        wp_kses_post($gdpr_message) .
        $gdpr_consent_checkbox_html .
        $thank_you_message_html .
        "</div>";

    return $output;
}

add_action("init", "woosponder_custom_checkout_block_register_block");

function woosponder_fetch_gdpr_message()
{
    $activation_status = get_option(
        "woosponder_gdpr_activation_status",
        "deactivate"
    );
    if ($activation_status !== "activate") {
        wp_send_json_error(["message" => "GDPR feature is deactivated"]);
        return;
    }

    $message_key = is_user_logged_in()
        ? "woosponder_gdpr_message_registered"
        : "woosponder_gdpr_message_guest";
    $message = get_option($message_key, "");

    $gdpr_opt_out_message = get_option("woosponder_gdpr_opt_out_message", "");
    $gdpr_cart_opt_out_message = get_option(
        "woosponder_gdpr_cart_opt_out_message",
        "Thank you for your consent!"
    );

    wp_send_json_success([
        "message" => $message,
        "optOutMessage" => $gdpr_opt_out_message,
        "thankYouMessage" => $gdpr_cart_opt_out_message,
    ]);
}

add_action("wp_ajax_fetch_gdpr_message", "woosponder_fetch_gdpr_message");
add_action("wp_ajax_nopriv_fetch_gdpr_message", "woosponder_fetch_gdpr_message");

function woosponder_enqueue_custom_checkout_js()
{
    $activation_status = get_option(
        "woosponder_gdpr_activation_status",
        "deactivate"
    );
    if ($activation_status !== "activate" || !is_checkout()) {
        return;
    }

    wp_enqueue_script(
        "custom-checkout-js",
        plugin_dir_url(__FILE__) . "js/custom-checkout.js",
        ["jquery"],
        "1.0.0",
        true
    );

    wp_localize_script("custom-checkout-js", "checkoutParams", [
        "ajaxurl" => admin_url("admin-ajax.php"),
        "isUserLoggedIn" => is_user_logged_in(),
    ]);
}

add_action("wp_enqueue_scripts", "woosponder_enqueue_custom_checkout_js");

// Woosponder tabs
function woosponder_admin_page()
{
    // Sanitize the input
    $active_tab = isset($_GET["tab"]) ? sanitize_key($_GET["tab"]) : "about";

    // Validate the input
    $valid_tabs = [
        "about",
        "connection",
        "gdpr",
        "lists",
        "active_connections",
    ];
    if (!in_array($active_tab, $valid_tabs, true)) {
        $active_tab = "about";
    }
    ?>
    <div class="wrap">
        <h1 class="woosponder-header">&nbsp;&nbsp;רב מסר - תוסף ווקומרס</h1>
        <h2 class="nav-tab-wrapper" style="direction: rtl; text-align: right;">
            <a href="?page=woosponder&tab=about" class="nav-tab <?php echo esc_attr(
                $active_tab == "about" ? "nav-tab-active" : ""
            ); ?>">לפני שמתחילים</a>
            <a href="?page=woosponder&tab=connection" class="nav-tab <?php echo esc_attr(
                $active_tab == "connection" ? "nav-tab-active" : ""
            ); ?>">חיבור לרב מסר</a>
            <a href="?page=woosponder&tab=gdpr" class="nav-tab <?php echo esc_attr(
                $active_tab == "gdpr" ? "nav-tab-active" : ""
            ); ?>">הודעת פרטיות</a>
            <a href="?page=woosponder&tab=lists" class="nav-tab <?php echo esc_attr(
                $active_tab == "lists" ? "nav-tab-active" : ""
            ); ?>">יצירת חיבור</a>
            <a href="?page=woosponder&tab=active_connections" class="nav-tab <?php echo esc_attr(
                $active_tab == "active_connections" ? "nav-tab-active" : ""
            ); ?>" id="woosponder-active-connections-tab">חיבורים פעילים</a>
        </h2>
        <div id="tab-content">
            <?php switch ($active_tab) {
                case "about":
                    include_once plugin_dir_path(__FILE__) . "about-tab.php";
                    break;
                case "connection":
                    include_once plugin_dir_path(__FILE__) .
                        "connection-tab.php";
                    woosponder_connection_tab_content();
                    break;
                case "gdpr":
                    include_once plugin_dir_path(__FILE__) .
                        "admin-gdpr-settings.php";
                    break;
                case "lists":
                    include_once plugin_dir_path(__FILE__) . "lists-tab.php";
                    woosponder_lists_tab_content();
                    break;
                case "active_connections":
                    include_once plugin_dir_path(__FILE__) .
                        "active-connections-tab.php";
                    break;
                default:
                    include_once plugin_dir_path(__FILE__) . "about-tab.php";
                    break;
            } ?>
        </div>
        <?php woosponder_footer_content(); ?>
    </div>
    <?php
}

function woosponder_is_json($string)
{
    json_decode($string);
    return json_last_error() == JSON_ERROR_NONE;
}

// Register AJAX actions
add_action(
    "wp_ajax_fetch_updated_lists",
    "woosponder_fetch_updated_lists_callback"
);
add_action("wp_ajax_retrieve_custom_fields", "woosponder_retrieve_custom_fields");
add_action(
    "wp_ajax_save_mapping_fetch_product_details",
    "woosponder_save_mapping_fetch_product_details"
);
add_action("wp_ajax_get_product_details", "woosponder_get_product_details");
add_action(
    "wp_ajax_fetch_connections",
    "woosponder_fetch_connections_ajax_handler"
);