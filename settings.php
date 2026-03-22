<?php
defined("MOODLE_INTERNAL") || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage(
        "local_softsysvideo",
        get_string("pluginname", "local_softsysvideo")
    );
    $ADMIN->add("localplugins", $settings);

    $settings->add(new admin_setting_configtext(
        "local_softsysvideo/api_url",
        get_string("api_url", "local_softsysvideo"),
        get_string("api_url_help", "local_softsysvideo"),
        "",
        PARAM_URL
    ));

    $settings->add(new admin_setting_configpasswordunmask(
        "local_softsysvideo/plugin_key",
        get_string("plugin_key", "local_softsysvideo"),
        get_string("plugin_key_help", "local_softsysvideo"),
        ""
    ));

    $settings->add(new admin_setting_configpasswordunmask(
        "local_softsysvideo/shared_secret",
        get_string("shared_secret", "local_softsysvideo"),
        "",
        ""
    ));

    // Link to setup wizard
    $setupurl = new moodle_url("/local/softsysvideo/setup.php");
    $settings->add(new admin_setting_description(
        "local_softsysvideo/setup_link",
        "",
        html_writer::link($setupurl, get_string("setup_wizard", "local_softsysvideo"), ["class" => "btn btn-primary"])
    ));
}
