<?php
defined("ABSPATH") || exit();

function woosponder_generateUUID()
{
    $uuid = sprintf(
        "%04x%04x-%04x-%04x-%04x-%04x%04x%04x",
        wp_rand(0, 0xffff),
        wp_rand(0, 0xffff),
        wp_rand(0, 0xffff),
        wp_rand(0, 0x0fff) | 0x4000,
        wp_rand(0, 0x3fff) | 0x8000,
        wp_rand(0, 0xffff),
        wp_rand(0, 0xffff),
        wp_rand(0, 0xffff)
    );
    return $uuid;
}

function woosponder_createAuthDataHeader($userKey, $userSecret)
{
    // Predefined client key and secret
    $c_key = "52F818A14401EDEDCA72B7FA16876A51";
    $c_secret = "4A6357F9C287878A9C78026D4A59D6DC";

    // Generating a nonce (unique identifier) using the woosponder_generateUUID function
    $nonce = woosponder_generateUUID();
    $timestamp = time();

    // Hashing client and user secrets with nonce
    $c_secret_hash = md5($c_secret . $nonce);
    $u_secret_hash = md5($userSecret . $nonce);

    // Constructing the authorization header
    $authHeader = "c_key=$c_key,c_secret=$c_secret_hash,u_key=$userKey,u_secret=$u_secret_hash,nonce=$nonce,timestamp=$timestamp";

    // Return both the header and the client credentials
    return [$authHeader, $c_key, $c_secret];
}

function woosponder_connectToRavMesser($userKey, $userSecret)
{
    // Generate the auth header with user-supplied key and secret
    list($authHeader, $clientKey, $clientSecret) = woosponder_createAuthDataHeader($userKey, $userSecret);

    // Setup the request URL
    $url = "http://api.responder.co.il/main/lists/";

    // Setup the request arguments, including the authorization header
    $args = [
        "headers" => ["Authorization" => $authHeader],
        "timeout" => 30, // Optional: Define a timeout for the request
    ];

    // Make the HTTP GET request to the URL
    $response = wp_remote_get($url, $args);

    // Check for an error in the response
    if (is_wp_error($response)) {
        $error_message = $response->get_error_message();
        return "Connection error: " . $error_message;
    }

    // Retrieve the body of the response
    $response_body = wp_remote_retrieve_body($response);

    // Decode the response and check for 'LISTS'
    $responseData = json_decode($response_body, true);
    if (!empty($responseData) && isset($responseData["LISTS"])) {
        // Update any relevant options or perform actions based on the successful connection
        update_option("woosponder_lists", $responseData["LISTS"]);
    }

    return $response_body;
}

// Fetching Custom Fields
function woosponder_send_get_request($url, $headers)
{
    $args = [
        "headers" => $headers,
        "timeout" => 30, // Optional: Set your timeout duration
        "sslverify" => false, // Important: Consider the security implications of this in production environments
    ];

    $response = wp_remote_get($url, $args);

    if (is_wp_error($response)) {
        $error_message = $response->get_error_message();
        return "WP Error: " . $error_message;
    } else {
        $response_body = wp_remote_retrieve_body($response);
        return $response_body;
    }
}

function woosponder_fetchCustomFieldsFromRavMesser($listId)
{
    $userKey = get_option("woosponder_user_key");
    $userSecret = get_option("woosponder_user_secret");

    if (empty($userKey) || empty($userSecret)) {
        return "User API credentials are not set.";
    }

    list($authHeader) = woosponder_createAuthDataHeader($userKey, $userSecret);
    $url = "http://api.responder.co.il/main/lists/$listId/personal_fields";
    $headers = ["Authorization" => $authHeader];

    $response = woosponder_send_get_request($url, $headers);

    return $response;
}

function woosponder_retrieveAuthHeader($userKey, $userSecret)
{
    // Use the client key and client secret defined in this file
    $clientKey = "YOUR_CLIENT_KEY"; // Actual client key
    $clientSecret = "YOUR_CLIENT_SECRET"; // Actual client secret
}