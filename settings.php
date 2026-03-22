<?php
defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_softsysvideo', get_string('pluginname', 'local_softsysvideo'));

    if ($ADMIN->fulltree) {
        $settings->add(new admin_setting_configtext(
            'local_softsysvideo/softsysvideo_api_url',
            get_string('api_url', 'local_softsysvideo'),
            get_string('api_url_help', 'local_softsysvideo'),
            '',
            PARAM_URL
        ));

        $settings->add(new admin_setting_configpasswordunmask(
            'local_softsysvideo/softsysvideo_plugin_key',
            get_string('plugin_key', 'local_softsysvideo'),
            get_string('plugin_key_help', 'local_softsysvideo'),
            ''
        ));

        $settings->add(new admin_setting_configpasswordunmask(
            'local_softsysvideo/softsysvideo_shared_secret',
            get_string('shared_secret', 'local_softsysvideo'),
            '',
            ''
        ));

        $button = html_writer::link(
            new moodle_url('/local/softsysvideo/setup.php'),
            get_string('setup_wizard', 'local_softsysvideo'),
            ['class' => 'btn btn-primary']
        );

        $settings->add(new admin_setting_heading(
            'local_softsysvideo/setupwizard',
            get_string('setup_wizard', 'local_softsysvideo'),
            $button
        ));
    }

    $ADMIN->add('localplugins', $settings);
}
