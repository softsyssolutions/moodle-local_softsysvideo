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
 * Admin settings for the SoftSys Video companion plugin.
 *
 * Redirects to the plugin dashboard when connected, or shows the
 * connect prompt when not yet linked.
 *
 * @package    local_softsysvideo
 * @copyright  2026 SoftSys Solutions {@link https://softsyssolutions.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_softsysvideo', get_string('pluginname', 'local_softsysvideo'));

    if ($ADMIN->fulltree) {
        $isconnected = !empty(get_config('local_softsysvideo', 'softsysvideo_plugin_key'));
        $wwwroot = $CFG->wwwroot;

        if ($isconnected) {
            $dashboardurl = new \moodle_url('/local/softsysvideo/dashboard.php');
            $connhtml = '
<div class="card border-success mb-3">
  <div class="card-body p-3 text-center">
    <p class="mb-2">' . get_string('connected', 'local_softsysvideo') . '</p>
    <a href="' . $dashboardurl->out() . '" class="btn btn-primary">' .
            get_string('view_dashboard', 'local_softsysvideo') . '</a>
  </div>
</div>';
        } else {
            $connecturl = $wwwroot . '/local/softsysvideo/connect.php';
            $connhtml = '
<div class="card border-secondary mb-3">
  <div class="card-body d-flex align-items-center p-3 ssv-flex-gap">
    <div>
      <strong>' . get_string('not_connected', 'local_softsysvideo') . '</strong>
      <p class="mb-2 text-muted small">' . get_string('connect_instructions', 'local_softsysvideo') . '</p>
      <a href="' . $connecturl . '" class="btn btn-primary">' . get_string('connect_account', 'local_softsysvideo') . '</a>
    </div>
  </div>
</div>';
        }

        $settings->add(new admin_setting_heading(
            'local_softsysvideo/connectionstatus',
            get_string('connection', 'local_softsysvideo'),
            $connhtml
        ));
    }

    $ADMIN->add('localplugins', $settings);
}
