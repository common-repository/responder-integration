<?php
defined("ABSPATH") || exit();

// Include the APIs
require_once "rav-messer-api.php"; // Ensure this path is correct
require_once "new-responder-api.php"; // Include New Responder API class

function woosponder_fetch_connections_ajax_handler() {
    // Verify nonce for security
    check_ajax_referer("woosponder_ajax_nonce", "security");

    // Initialize variables for list and tag mapping
    $list_mapping = [];
    $tag_mapping = [];

    // Initialize New Responder API
    $Woosponder_NewResponderApi = new Woosponder_NewResponderApi();

    // Fetch list names and tags from New Responder
    $newResponderLists = $Woosponder_NewResponderApi->fetchLists();
    $newResponderTags = $Woosponder_NewResponderApi->fetchSubscriberTags();

    // Update list mapping for New Responder lists
    foreach ($newResponderLists as $list) {
        $list_mapping[$list["id"]] = $list["name"];
    }

    // Update tag mapping for New Responder tags
    $tag_mapping = array_column($newResponderTags, "text", "id");

    // Attempt to fetch list names from Rav Messer API if credentials are set
    $user_key = get_option("woosponder_user_key");
    $user_secret = get_option("woosponder_user_secret");
    if (!empty($user_key) && !empty($user_secret)) {
        $api_response = woosponder_connectToRavMesser($user_key, $user_secret);
        if (woosponder_is_json($api_response)) {
            $lists_data = json_decode($api_response, true);
            foreach ($lists_data["LISTS"] as $list) {
                if (isset($list["ID"]) && isset($list["DESCRIPTION"])) {
                    $list_mapping[$list["ID"]] = $list["DESCRIPTION"];
                }
            }
        }
    }

    // Translation array for WooCommerce order statuses to Hebrew
    $status_translations = [
        "wc-pending" => "ממתין לתשלום",
        "wc-processing" => "בטיפול",
        "wc-on-hold" => "בהשהייה",
        "wc-completed" => "הושלם",
        "wc-cancelled" => "בוטל",
        "wc-refunded" => "הוחזר",
        "wc-failed" => "נכשל",
    ];

    $connections = [];

    // Fetch all products with connections
    $args = [
        "post_type" => "product",
        "posts_per_page" => -1,
        "meta_query" => [
            [
                "key" => "woosponder_connections",
                "compare" => "EXISTS",
            ],
        ],
    ];

    $query = new WP_Query($args);

    while ($query->have_posts()) {
        $query->the_post();
        $product_id = get_the_ID();

        // Retrieve existing connections
        $existing_connections = get_post_meta($product_id, "woosponder_connections", true);
        if (is_string($existing_connections)) {
            $existing_connections = json_decode($existing_connections, true);
        }

        if (is_array($existing_connections)) {
            foreach ($existing_connections as $index => $connection) {
                // Process buyer list
                if (isset($connection['buyer_list_id'])) {
                    $list_id = $connection['buyer_list_id'];
                    $system_type = $connection['system_type'];
                    $order_status = $connection['order_status'];
                    $tags = $connection['buyer_tags'] ?? [];

                    $tag_names = [];
                    if ($system_type === "new_responder") {
                        $tag_names = array_map(function ($tag) use ($tag_mapping) {
                            return isset($tag_mapping[$tag]) ? $tag_mapping[$tag] : $tag;
                        }, $tags);
                    }

                    $translated_status = isset($status_translations[$order_status]) ? $status_translations[$order_status] : "לא ידוע";

                    if (!empty($list_id) && isset($list_mapping[$list_id])) {
                        $connections[] = [
                            "product_id" => $product_id,
                            "product_name" => get_the_title($product_id),
                            "connection_type" => "buyer",
                            "list_id" => $list_id,
                            "list_name" => $list_mapping[$list_id],
                            "tags" => implode(", ", $tag_names),
                            "system_type" => $system_type,
                            "order_status" => $translated_status,
                            "index" => $index
                        ];
                    }
                }

                // Process interested list
                if (isset($connection['interested_list_id'])) {
                    $list_id = $connection['interested_list_id'];
                    $tags = $connection['interested_tags'] ?? [];
                    $system_type = $connection['system_type'];

                    $tag_names = [];
                    if ($system_type === "new_responder") {
                        $tag_names = array_map(function ($tag) use ($tag_mapping) {
                            return isset($tag_mapping[$tag]) ? $tag_mapping[$tag] : $tag;
                        }, $tags);
                    }

                    if (!empty($list_id) && isset($list_mapping[$list_id])) {
                        $connections[] = [
                            "product_id" => $product_id,
                            "product_name" => get_the_title($product_id),
                            "connection_type" => "interested",
                            "list_id" => $list_id,
                            "list_name" => $list_mapping[$list_id],
                            "tags" => implode(", ", $tag_names),
                            "system_type" => $system_type,
                            "index" => $index
                        ];
                    }
                }
            }
        }
    }

    // Reset post data
    wp_reset_postdata();

    // Return the enriched data as JSON
    wp_send_json_success($connections);
}

add_action("wp_ajax_fetch_connections", "woosponder_fetch_connections_ajax_handler");