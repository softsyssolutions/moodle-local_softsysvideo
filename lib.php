<?php
defined('MOODLE_INTERNAL') || die();

/**
 * Extend the main navigation for SoftSys Video administrators.
 *
 * @param global_navigation $navigation
 * @return void
 */
function local_softsysvideo_extend_navigation(global_navigation $navigation): void {
    if (!isloggedin() || isguestuser()) {
        return;
    }

    $systemcontext = context_system::instance();
    if (!has_capability('local/softsysvideo:manage', $systemcontext)) {
        return;
    }

    $node = $navigation->add(
        get_string('pluginname', 'local_softsysvideo'),
        null,
        navigation_node::TYPE_CUSTOM,
        null,
        'local_softsysvideo'
    );

    $node->add(
        get_string('setup_wizard', 'local_softsysvideo'),
        new moodle_url('/local/softsysvideo/setup.php'),
        navigation_node::TYPE_SETTING,
        null,
        'local_softsysvideo_setup'
    );
}

/**
 * Extend the settings navigation with a setup shortcut.
 *
 * @param settings_navigation $settingsnav
 * @param context $context
 * @return void
 */
function local_softsysvideo_extend_settings_navigation(settings_navigation $settingsnav, context $context): void {
    if ($context->contextlevel !== CONTEXT_SYSTEM) {
        return;
    }

    if (!has_capability('local/softsysvideo:manage', $context)) {
        return;
    }

    $settingsnav->add(
        get_string('setup_wizard', 'local_softsysvideo'),
        new moodle_url('/local/softsysvideo/setup.php'),
        navigation_node::TYPE_SETTING,
        null,
        'local_softsysvideo_setup'
    );
}
