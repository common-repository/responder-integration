<?php
class Woosponder_NewResponderApi
{
    private $bearerToken;

    public function __construct()
    {
        $this->bearerToken = get_option("woosponder_new_responder_bearer_token");
    }

    public function requestNewResponderOAuthToken($userToken)
    {
        $clientId = "45"; // Replace with your actual client_id
        $clientSecret = "ainq4WQqA9u7w7e9Cfc3rf3RuXt6q7MV2om296sp"; // Replace with your actual client_secret
        $url = "https://graph.responder.live/v2/oauth/token";

        $postData = [
            "grant_type" => "client_credentials",
            "scope" => "*",
            "client_id" => $clientId,
            "client_secret" => $clientSecret,
            "user_token" => $userToken,
        ];

        $args = [
            "body" => wp_json_encode($postData),
            "headers" => [
                "Content-Type" => "application/json",
                "Accept" => "application/json",
            ],
            "method" => "POST",
        ];

        $response = wp_remote_post($url, $args);

        if (is_wp_error($response)) {
            $err = $response->get_error_message();
            return ["success" => false, "error" => $err];
        } else {
            $body = wp_remote_retrieve_body($response);
            $responseData = json_decode($body, true);
            if (!empty($responseData["token"])) {
                $this->bearerToken = $responseData["token"];
                update_option("woosponder_new_responder_bearer_token", $this->bearerToken);
                return ["success" => true, "access_token" => $this->bearerToken];
            } else {
                $errorDetail = isset($responseData["message"]) ? $responseData["message"] : "Unknown error during connection.";
                return ["success" => false, "error" => $errorDetail];
            }
        }
    }

    public function fetchLists()
    {
        $url = "https://graph.responder.live/v2/lists";
        $args = [
            "method" => "GET",
            "headers" => [
                "Authorization" => "Bearer " . $this->bearerToken,
                "Content-Type" => "application/json",
            ],
        ];

        $response = wp_remote_request($url, $args);

        if (is_wp_error($response)) {
            $err = $response->get_error_message();
            return [];
        }

        $body = wp_remote_retrieve_body($response);
        $decodedBody = json_decode($body, true);

        if (isset($decodedBody["data"]) && is_array($decodedBody["data"])) {
            $filteredLists = array_filter($decodedBody["data"], function ($list) {
                return $list["dynamic_status"] !== 1; // Exclude dynamic lists
            });
            return array_map(function ($list) {
                return ["id" => $list["id"], "name" => $list["name"]];
            }, $filteredLists);
        } else {
            return [];
        }
    }

    public function fetchListFields($listId)
    {
        $url = "https://graph.responder.live/v2/lists/" . $listId . "/fields";
        $args = [
            "method" => "GET",
            "headers" => [
                "Authorization" => "Bearer " . $this->bearerToken,
                "Content-Type" => "application/json",
            ],
        ];

        $response = wp_remote_request($url, $args);

        if (is_wp_error($response)) {
            $err = $response->get_error_message();
            return [];
        }

        $body = wp_remote_retrieve_body($response);
        $decodedBody = json_decode($body, true);

        if (isset($decodedBody["data"]) && is_array($decodedBody["data"])) {
            return $decodedBody["data"];
        } else {
            return [];
        }
    }

    public function fetchSubscriberTags()
    {
        $url = "https://graph.responder.live/v2/tag"; // Adjust the URL if necessary
        $args = [
            "method" => "GET",
            "headers" => [
                "Authorization" => "Bearer " . $this->bearerToken,
                "Content-Type" => "application/json",
            ],
        ];

        $response = wp_remote_request($url, $args);

        if (is_wp_error($response)) {
            $err = $response->get_error_message();
            return [];
        }

        $body = wp_remote_retrieve_body($response);
        $decodedBody = json_decode($body, true);

        if (isset($decodedBody["data"]) && is_array($decodedBody["data"])) {
            return array_map(function ($tag) {
                return ["id" => $tag["id"], "text" => $tag["name"]]; // Adjust the field name if necessary
            }, $decodedBody["data"]);
        } else {
            return [];
        }
    }

    public function addSubscriber($subscriberData)
    {
        $url = "https://graph.responder.live/v2/subscribers"; // Adjust the endpoint if necessary

        // Use wp_json_encode instead of json_encode
        $body = wp_json_encode($subscriberData); // Ensure $subscriberData is formatted correctly according to the API's schema

        $args = [
            "method" => "POST",
            "headers" => [
                "Authorization" => "Bearer " . $this->bearerToken,
                "Content-Type" => "application/json",
                "Accept" => "application/json",
            ],
            "body" => $body,
        ];

        $response = wp_remote_post($url, $args);

        if (is_wp_error($response)) {
            $err = $response->get_error_message();
            return ["success" => false, "error" => $err];
        }

        $body = wp_remote_retrieve_body($response);
        $decodedBody = json_decode($body, true);

        if (isset($decodedBody["status"]) && $decodedBody["status"] === true) {
            return ["success" => true, "data" => $decodedBody];
        } else {
            $err = isset($decodedBody["error"]) ? $decodedBody["error"] : "API request failed";
            return ["success" => false, "error" => $err];
        }
    }
}