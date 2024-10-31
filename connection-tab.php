<?php
defined("ABSPATH") || exit();
function woosponder_connection_tab_content()
{
    // Initialize connection status variables at the start
    $rav_messer_connected = false;
    $new_responder_connected = false;

    // Initialize feedback messages with empty strings
    $rav_messer_feedbackMessage = "";
    $new_responder_feedbackMessage = "";

    // Sanitize the options retrieved from the database
    $user_key = sanitize_text_field(get_option("woosponder_user_key", ""));
    $user_secret = sanitize_text_field(get_option("woosponder_user_secret", ""));
    $user_token_new_system = sanitize_text_field(get_option("woosponder_new_system_user_token", ""));
    $active_system = sanitize_text_field(get_option("woosponder_active_system", "none"));

    if ($_SERVER["REQUEST_METHOD"] == "POST") {

        if (
            isset($_POST["woosponder_connection_submit"]) &&
            check_admin_referer(
                "woosponder_connection_action",
                "woosponder_connection_nonce"
            )
        ) {
            $active_system = "rav_messer";

            // Sanitize the input data
            $userKey = sanitize_text_field($_POST["user_key"]);
            $userSecret = sanitize_text_field($_POST["user_secret"]);
            update_option("woosponder_user_key", $userKey);
            update_option("woosponder_user_secret", $userSecret);

            require_once plugin_dir_path(__FILE__) . "rav-messer-api.php";
            $apiResponse = woosponder_connectToRavMesser($userKey, $userSecret);
            $responseData = json_decode($apiResponse, true);
            if (isset($responseData["LISTS"])) {
                $rav_messer_connected = true;
                update_option("woosponder_lists", $responseData["LISTS"]);
            } else {
                $rav_messer_connected = false;
                $errorMessage = "היי, משהו השתבש. אנא וודאו שהמפתח והסוד מוזנים בשדות הנכונים במלואם.";
                $rav_messer_feedbackMessage = esc_html($errorMessage);
            }
        } elseif (
            isset($_POST["woosponder_new_system_submit"]) &&
            check_admin_referer(
                "woosponder_new_system_action",
                "woosponder_new_system_nonce"
            )
        ) {
            $active_system = "new_responder";

            // Sanitize the input data
            $newSystemUserToken = sanitize_text_field($_POST["user_token_new_system"]);
            update_option("woosponder_new_system_user_token", $newSystemUserToken);

            require_once plugin_dir_path(__FILE__) . "new-responder-api.php";
            $Woosponder_NewResponderApi = new Woosponder_NewResponderApi();
            $oauthResponse = $Woosponder_NewResponderApi->requestNewResponderOAuthToken($newSystemUserToken);

            if (!empty($oauthResponse["access_token"])) {
                update_option("woosponder_new_system_access_token", sanitize_text_field($oauthResponse["access_token"]));
                $new_responder_connected = true;
            } else {
                $errorMessage = isset($oauthResponse["error"]) ? sanitize_text_field($oauthResponse["error"]) : "Failed to connect to the New Responder due to an unknown error.";
                $new_responder_connected = false;
                $new_responder_feedbackMessage = esc_html("היי, משהו השתבש. אנא וודאו שהטוקן מוזן במלואו.");
            }
        }

        update_option("woosponder_active_system", sanitize_text_field($active_system));
    }
    // Display forms and connection status messages
    ?>
    <div class="woosponder-wrap">
        <div>
            <h2>חיבור לרב מסר</h2>
            <form method="post">
                <label for="user_key">מפתח:</label>
                <input type="text" id="user_key" name="user_key" value="<?php echo esc_attr($user_key); ?>">
                <label for="user_secret">סוד:</label>
                <input type="text" id="user_secret" name="user_secret" value="<?php echo esc_attr($user_secret); ?>">
                <?php wp_nonce_field("woosponder_connection_action", "woosponder_connection_nonce"); ?>
                <?php if ($rav_messer_connected): ?>
                    <div class="message" style="color: green;"><strong>החיבור לרב מסר בוצע בהצלחה!</strong></div>
                <?php else: ?>
                    <div class="error-message" style="color: red;"><?php echo esc_html($rav_messer_feedbackMessage); ?></div>
                <?php endif; ?>
                <input type="submit" name="woosponder_connection_submit" value="שמירה">
            </form>
        </div>
        <div>
            <h2>חיבור לרב מסר - המערכת החדשה</h2>
            <form method="post">
                <label for="user_token_new_system">מפתח:</label>
                <input type="text" id="user_token_new_system" name="user_token_new_system" value="<?php echo esc_attr($user_token_new_system); ?>">
                <?php wp_nonce_field("woosponder_new_system_action", "woosponder_new_system_nonce"); ?>
                <?php if (!$new_responder_connected && !empty($new_responder_feedbackMessage)): ?>
                    <div class="error-message" style="color: red;"><?php echo esc_html($new_responder_feedbackMessage); ?></div>
                <?php elseif ($new_responder_connected): ?>
                    <div class="message" style="color: green;"><strong>החיבור לרב מסר - המערכת החדשה בוצע בהצלחה!</strong></div>
                <?php endif; ?>
                <input type="submit" name="woosponder_new_system_submit" value="שמירה">
            </form>
        </div>
    </div>
    <?php
}