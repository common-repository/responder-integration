<?php
defined("ABSPATH") || exit();

function woosponder_createRavMesserSubscriber($subscribersData, $listId)
{
    // Fetch and check API credentials
    $userKey = get_option("woosponder_user_key");
    $userSecret = get_option("woosponder_user_secret");
    if (empty($userKey) || empty($userSecret)) {
        return false;
    }

    $allSubscribersCreated = true;
    $existingSubscribers = []; // Array to hold existing subscribers

    // Track processed subscribers to prevent duplicate processing
    $processedSubscribers = [];

    foreach ($subscribersData as $subscriberDetails) {
        $email = $subscriberDetails["EMAIL"];
        if (empty($subscriberDetails["NAME"]) || empty($email)) {
            $allSubscribersCreated = false;
            continue; // Skip this subscriber and continue with the next
        }

        // Check if this subscriber has already been processed
        if (isset($processedSubscribers[$listId][$email])) {
            continue;
        }

        $url = "http://api.responder.co.il/main/lists/$listId/subscribers";

        list($authHeader) = woosponder_createAuthDataHeader($userKey, $userSecret);
        $args = [
            "headers" => [
                "Authorization" => $authHeader,
                "Content-Type" => "application/x-www-form-urlencoded",
            ],
            "body" => "subscribers=" . urlencode(wp_json_encode([$subscriberDetails])),
            "method" => "POST",
        ];

        $response = wp_remote_post($url, $args);
        if (is_wp_error($response)) {
            $allSubscribersCreated = false;
            continue; // Skip to the next subscriber
        }

        $responseData = json_decode(wp_remote_retrieve_body($response), true);

        if (!empty($responseData["EMAILS_EXISTING"])) {
            // Capture existing subscriber email for update
            foreach ($responseData["EMAILS_EXISTING"] as $existingEmail) {
                $existingSubscribers[] = [
                    "email" => $existingEmail,
                    "data" => $subscriberDetails,
                ];
            }
            $allSubscribersCreated = false;
        } elseif (empty($responseData["SUBSCRIBERS_CREATED"])) {
            $allSubscribersCreated = false;
        } else {
            // Mark this subscriber as processed
            $processedSubscribers[$listId][$email] = true;
        }
    }

    // Handle updates for existing subscribers
    if (!empty($existingSubscribers)) {
        woosponder_updateExistingSubscribers($existingSubscribers, $listId);
    }

    return $allSubscribersCreated;
}

function woosponder_updateExistingSubscribers($existingSubscribers, $listId)
{
    $userKey = get_option("woosponder_user_key");
    $userSecret = get_option("woosponder_user_secret");
    if (empty($userKey) || empty($userSecret)) {
        return false;
    }

    $allUpdatesSuccessful = true;

    foreach ($existingSubscribers as $subscriber) {
        $url = "http://api.responder.co.il/main/lists/$listId/subscribers";

        // Add IDENTIFIER to the subscriber data
        $subscriber["data"]["IDENTIFIER"] = $subscriber["email"];

        list($authHeader) = woosponder_createAuthDataHeader($userKey, $userSecret);
        $args = [
            "headers" => [
                "Authorization" => $authHeader,
                "Content-Type" => "application/x-www-form-urlencoded",
            ],
            "body" => "subscribers=" . urlencode(wp_json_encode([$subscriber["data"]])),
            "method" => "PUT",
        ];

        $response = wp_remote_request($url, $args);
        if (is_wp_error($response)) {
            $allUpdatesSuccessful = false;
            continue;
        }

        $responseData = json_decode(wp_remote_retrieve_body($response), true);

        if (empty($responseData["SUBSCRIBERS_UPDATED"])) {
            $allUpdatesSuccessful = false;
        }
    }

    return $allUpdatesSuccessful;
}