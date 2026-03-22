<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Library functions for the SoftSys Video plugin.
 *
 * @package    local_softsysvideo
 * @copyright  2026 SoftSys Solutions {@link https://softsyssolutions.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

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
        get_string('dashboard', 'local_softsysvideo'),
        new moodle_url('/local/softsysvideo/dashboard.php'),
        navigation_node::TYPE_SETTING,
        null,
        'local_softsysvideo_dashboard'
    );
}

/**
 * Extend the settings navigation with a dashboard shortcut.
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
        get_string('dashboard', 'local_softsysvideo'),
        new moodle_url('/local/softsysvideo/dashboard.php'),
        navigation_node::TYPE_SETTING,
        null,
        'local_softsysvideo_dashboard'
    );
}
