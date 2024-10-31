<?php
defined("ABSPATH") || exit();
// lists-tab.php
function woosponder_lists_tab_content()
{
    // Fetch user key and secret stored in WordPress options
    $user_key = get_option("woosponder_user_key", "");
    $user_secret = get_option("woosponder_user_secret", "");

    // Get WooCommerce products
    $products = wc_get_products(["return" => "ids"]);

    // HTML output for the lists tab content
    echo '<div class="woosponder-wrap">';

    // Product selection dropdown
    echo '<div class="woosponder-products-tab">';
    echo '<h3>' . esc_html('בחירת מוצר*') . '</h3>';
    echo '<select id="product_selection" name="product_selection" class="woosponder-select">';
    echo '<option value="">' . esc_html('בחרו מוצר') . '</option>';
    foreach ($products as $product_id) {
        $product = wc_get_product($product_id);
        echo '<option value="' . esc_attr($product_id) . '">' . esc_html($product->get_name()) . '</option>';
    }
    echo '</select>';
    echo '</div>';

    // Add the order status dropdown here
    echo '<div class="order-status-section">';
    echo '<h4>' . esc_html('בחירת סטטוס הזמנה*') . '</h4>';
    echo '<p>' . esc_html('כשההזמנה תעבור לסטטוס הנבחר, הנרשם יצורף לרשימה') . '</p>';
    echo '<select id="order_status_dropdown" name="order_status_dropdown" class="woosponder-select">';
    echo '<option value="">' . esc_html('בחרו סטטוס הזמנה') . '</option>';
    $orderStatuses = [
        "wc-pending" => "ממתין לתשלום",
        "wc-processing" => "בטיפול",
        "wc-on-hold" => "בהשהייה",
        "wc-completed" => "הושלם",
        "wc-cancelled" => "בוטל",
        "wc-refunded" => "הוחזר",
        "wc-failed" => "נכשל",
    ];
    foreach ($orderStatuses as $value => $text) {
        echo '<option value="' . esc_attr($value) . '">' . esc_html($text) . '</option>';
    }
    echo '</select>';
    echo '</div>';

    // System selection dropdown
    echo '<div class="woosponder-lists-tab">';
    echo '<h3>' . esc_html('בחירת מערכת*') . '</h3>';
    echo '<select id="system_selection" name="system_selection" class="woosponder-select">';
    echo '<option value="">' . esc_html('בחרו מערכת') . '</option>';
    echo '<option value="rav_messer">' . esc_html('רב מסר') . '</option>';
    echo '<option value="new_responder">' . esc_html('רב מסר - המערכת החדשה') . '</option>';
    echo '</select>';
    echo '</div>';

    // Lists selection dropdown - initially empty
    echo '<div class="woosponder-lists-tab">';
    echo '<h3>' . esc_html('רשימת רוכשים*') . '</h3>';
    echo '<select id="list_selection" name="list_selection" class="woosponder-select">';
    echo '<option value="">' . esc_html('בחרו רשימת רוכשים') . '</option>';
    // The content of this dropdown will be populated by JavaScript
    echo '</select>';
    echo '</div>';

    // New dropdown for interested customers - initially empty
    echo '<div class="woosponder-lists-tab">';
    echo '<h3>' . esc_html('רשימת מתעניינים (אופציונלי)') . '</h3>';
    echo '<select id="list_selection_interested" name="list_selection_interested" class="woosponder-select">';
    echo '<option value="">' . esc_html('בחרו רשימת מתעניינים') . '</option>';
    // The content of this dropdown will be populated by JavaScript
    echo '</select>';
    echo '</div>';

    // Save button
    echo '<div class="woosponder-save-mapping">';
    echo '<button id="save_custom_fields_mapping" class="button button-primary">' . esc_html('שמור תיאום') . '</button>';
    echo '</div>';

    // Success message container
    echo '<div id="success_message_container" style="margin-top: 20px;"></div>';

    // Mapping interface container, hidden initially
    echo '<div id="mapping_interface" style="margin-top: 20px; display: none;">';
    // The content of this div will be populated by JavaScript
    echo '</div>';

    echo '</div>'; // Close the woosponder-wrap div
}