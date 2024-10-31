<?php
defined("ABSPATH") || exit();
function woosponder_register_gdpr_settings()
{
    // register settings for "woosponder" page
    register_setting("woosponder_gdpr_settings", "woosponder_gdpr_message");
    register_setting(
        "woosponder_gdpr_settings",
        "woosponder_gdpr_activation_status"
    ); // Activation status setting

    // register a new section in the "woosponder" page
    add_settings_section(
        "woosponder_gdpr_section",
        "GDPR Compliance Settings",
        "woosponder_gdpr_section_callback",
        "woosponder_gdpr_settings"
    );

    // register fields in the "woosponder_gdpr_section" section, inside the "woosponder" page
    add_settings_field(
        "woosponder_gdpr_message",
        "GDPR Message",
        "woosponder_gdpr_message_callback",
        "woosponder_gdpr_settings",
        "woosponder_gdpr_section"
    );

    add_settings_field(
        "woosponder_gdpr_activation_status",
        "Activation Status",
        "woosponder_gdpr_activation_status_callback",
        "woosponder_gdpr_settings",
        "woosponder_gdpr_section"
    );
}

add_action("admin_init", "woosponder_register_gdpr_settings");

function woosponder_gdpr_section_callback()
{
    echo "<p>GDPR related settings for Woosponder.</p>";
}

function woosponder_gdpr_message_callback()
{
    $setting = get_option("woosponder_gdpr_message"); ?>
    <input type="text" name="woosponder_gdpr_message" value="<?php echo esc_attr(
        $setting
    ); ?>">
    <?php
}

function woosponder_gdpr_activation_status_callback()
{
    $setting = get_option("woosponder_gdpr_activation_status", "deactivate"); ?>
    <select name="woosponder_gdpr_activation_status">
        <option value="activate" <?php selected(
            $setting,
            "activate"
        ); ?>>Activate</option>
        <option value="deactivate" <?php selected(
            $setting,
            "deactivate"
        ); ?>>Deactivate</option>
    </select>
    <?php
}