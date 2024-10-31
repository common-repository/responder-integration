<?php
defined("ABSPATH") || exit();
if (!current_user_can("manage_options")) {
    return;
}

$activation_status = get_option(
    "woosponder_gdpr_activation_status",
    "deactivate"
);

if (isset($_POST["submit_gdpr_settings"])) {
    check_admin_referer(
        "woosponder_gdpr_settings_action",
        "woosponder_gdpr_settings_nonce"
    );

    // Sanitize and update the options
    update_option(
        "woosponder_gdpr_message_guest",
        wp_kses_post($_POST["woosponder_gdpr_message_guest"])
    );
    update_option(
        "woosponder_gdpr_message_registered",
        wp_kses_post($_POST["woosponder_gdpr_message_registered"])
    );
    update_option(
        "woosponder_gdpr_opt_out_message",
        wp_kses_post($_POST["woosponder_gdpr_opt_out_message"])
    );
    update_option(
        "woosponder_gdpr_cart_opt_out_message",
        wp_kses_post($_POST["woosponder_gdpr_cart_opt_out_message"])
    );

    // Sanitize and update activation status
    $activation_status = sanitize_text_field(
        $_POST["woosponder_gdpr_activation"] ?? "deactivate"
    );
    update_option("woosponder_gdpr_activation_status", $activation_status);

    // Display the success message
    echo '<div style="color: green; font-weight: bold; margin-top: 20px;">בחירתך נשמרה בהצלחה.</div>';
}

// Load the existing messages from the database
$gdpr_message_guest = get_option("woosponder_gdpr_message_guest", "");
$gdpr_message_registered = get_option("woosponder_gdpr_message_registered", "");
$gdpr_opt_out_message = get_option("woosponder_gdpr_opt_out_message", "");
$gdpr_cart_opt_out_message = get_option(
    "woosponder_gdpr_cart_opt_out_message",
    ""
);
?>
<div class="small-heading">
    <h2>הגדרות הודעת פרטיות</h2>
    <div class="woosponder-gdpr-settings">
        <form method="post" action="">
            <?php wp_nonce_field(
                "woosponder_gdpr_settings_action",
                "woosponder_gdpr_settings_nonce"
            ); ?>

            <h2>הודעה שתוצג לאורחים בעת מעקב אחר עגלות הקניות שלהם:</h2>
            <p><strong>בהתאם לתקנות ה-GDPR, יש לצרף הודעה בטופס ההזמנה כדי ליידע את האורחים אודות השימוש במידע שלהם.<br>
            לדוגמה: "מעוניינים לקבל עדכונים על מבצעים חמים, הטבות בלעדיות, וחידושים שאתם לא רוצים לפספס, הקליקו כאן ונדאג לעדכן!<br>
            בנוסף, פרטיותכם חשובה לנו, לעיון במדיניות פרטיות האתר."</strong></p>
            <?php wp_editor(
                html_entity_decode(esc_html($gdpr_message_guest)),
                "woosponder_gdpr_message_guest_editor",
                [
                    "textarea_name" => "woosponder_gdpr_message_guest",
                    "wpautop" => true,
                    "media_buttons" => false,
                ]
            ); ?>

            <h2>הודעה שתוצג למשתמשים רשומים לבקשת אישור מעקב אחר טופס ביצוע ההזמנה:</h2>
            <p><strong>בהתאם לתקנות ה-GDPR, יש לצרף הודעה בטופס ההזמנה כדי ליידע את המשתמשים הרשומים אודות השימוש במידע שלהם.<br>
            לדוגמה: "ברוכים השבים, לעדכונים על מבצעים חמים, הטבות בלעדיות, וחידושים שאתם לא רוצים לפספס, הקליקו כאן ונדאג לעדכן!<br>
            בנוסף, פרטיותכם חשובה לנו, לעיון במדיניות פרטיות האתר."</strong></p>
            <?php wp_editor(
                html_entity_decode(esc_html($gdpr_message_registered)),
                "woosponder_gdpr_message_registered_editor",
                [
                    "textarea_name" => "woosponder_gdpr_message_registered",
                    "wpautop" => true,
                    "media_buttons" => false,
                ]
            ); ?>

            <h2>הודעה שתוצג לצד תיבת הסימון להסכמת האורחים/ המשתמשים הרשומים לאישור מעקב אחר טופס ביצוע ההזמנה:</h2>
            <p><strong>הודעת אישור האורחים/ המשתמשים הרשומים. לדוגמא: "כן, אני מעוניין.ת"</strong></p>
            <?php wp_editor(
                html_entity_decode(esc_html($gdpr_opt_out_message)),
                "woosponder_gdpr_opt_out_message_editor",
                [
                    "textarea_name" => "woosponder_gdpr_opt_out_message",
                    "wpautop" => true,
                    "media_buttons" => false,
                ]
            ); ?>

            <h2>הודעה שתוצג לאורחים/ משתמשים רשומים לאחר אישורם:</h2>
            <p><strong>הודעת אישור האורחים/ המשתמשים הרשומים לאחר אישורם. לדוגמא: "תודה! בקשתך נקלטה."</strong></p>
            <?php wp_editor(
                html_entity_decode(esc_html($gdpr_cart_opt_out_message)),
                "woosponder_gdpr_cart_opt_out_message_editor",
                [
                    "textarea_name" => "woosponder_gdpr_cart_opt_out_message",
                    "wpautop" => true,
                    "media_buttons" => false,
                ]
            ); ?>
<h2>סטטוס הפעלה:</h2>
<p><strong>לאחר בחירת הפעלה ושמירה, ההודעה תוצג לאורחים/ משתמשים רשומים בעמוד ההזמנה באתרכם. ניתן לערוך שינויים בהודעות בכל עת ולעדכנם ע״י לחיצה על כפתור השמירה. כמו כן, ניתן להסיר את ההודעה מהאתר בכל עת ע״י הסרת הבחירה בתיבת הפעילות ולחיצה על שמירה.</strong></p>
<input type="checkbox" id="woosponder_gdpr_activation" name="woosponder_gdpr_activation" value="activate" <?php echo esc_attr($activation_status) == "activate" ? "checked" : ""; ?>>
<label for="woosponder_gdpr_activation">פעיל</label>
            </select>
            <?php submit_button("שמירה", "primary", "submit_gdpr_settings"); ?>
        </form>
    </div>
</div>