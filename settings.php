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
 * @package    local_softsysvideo
 * @copyright  2026 SoftSys Solutions {@link https://softsyssolutions.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_softsysvideo', get_string('pluginname', 'local_softsysvideo'));

    if ($ADMIN->fulltree) {
        $apiurl = get_config('local_softsysvideo', 'softsysvideo_api_url');
        $pluginkey = get_config('local_softsysvideo', 'softsysvideo_plugin_key');
        $tenantname = get_config('local_softsysvideo', 'softsysvideo_tenant_name');
        $isconnected = !empty($apiurl) && !empty($pluginkey);

        $wwwroot = $CFG->wwwroot;
        $connecturl = $wwwroot . '/local/softsysvideo/connect.php';
        $dashboardurl = $wwwroot . '/local/softsysvideo/dashboard.php';
        $supporturl = $wwwroot . '/local/softsysvideo/support.php';

        // Translated strings for inline JS.
        $strmeetings = get_string('this_month_meetings', 'local_softsysvideo');
        $strhours = get_string('video_hours', 'local_softsysvideo');
        $strparticipants = get_string('participants', 'local_softsysvideo');
        $strrecordings = get_string('recordings', 'local_softsysvideo');
        $strloadingstats = get_string('loading_stats', 'local_softsysvideo');
        $strstatsloaderror = get_string('stats_load_error', 'local_softsysvideo');
        $strviewdashboard = get_string('view_dashboard', 'local_softsysvideo');
        $strreconnect = get_string('reconnect', 'local_softsysvideo');
        $strsupport = get_string('support', 'local_softsysvideo');
        $strorg = get_string('organization_label', 'local_softsysvideo');

        if ($isconnected) {
            $connhtml = '
<div class="card border-success mb-3">
  <div class="card-header bg-success text-white fw-bold">' .
            get_string('connected', 'local_softsysvideo') . ': ' .
            htmlspecialchars($tenantname ?: '—') . '</div>
  <div class="card-body p-3">
    <p class="mb-2"><strong>' . $strorg . ':</strong> ' . htmlspecialchars($tenantname ?: '—') . '</p>
    <div id="ssv-stats-container">
      <p class="text-muted small">' . $strloadingstats . '</p>
    </div>
    <div class="d-flex flex-wrap gap-2 mt-3">
      <a href="' . $dashboardurl . '" class="btn btn-primary">' . $strviewdashboard . '</a>
      <a href="' . $connecturl . '" class="btn btn-sm btn-outline-secondary">' . $strreconnect . '</a>
      <a href="' . $supporturl . '" class="btn btn-sm btn-outline-danger">' . $strsupport . '</a>
    </div>
  </div>
</div>
<script>
(function() {
    var LABELS = {
        meetings: ' . json_encode($strmeetings) . ',
        hours: ' . json_encode($strhours) . ',
        participants: ' . json_encode($strparticipants) . ',
        recordings: ' . json_encode($strrecordings) . ',
        error: ' . json_encode($strstatsloaderror) . '
    };
    var API_URL = ' . json_encode($apiurl) . ';
    var PLUGIN_KEY = ' . json_encode($pluginkey) . ';
    fetch(API_URL + \'/api/moodle/stats\', {
        headers: { \'Authorization\': \'Bearer \' + PLUGIN_KEY }
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        var html = \'<div class="row g-2">\';
        var m = data.this_month;
        var card = \'<div class="col-6 col-md-3"><div class="card text-center p-2">\';
        var cend = \'</div></div></div>\';
        html += card + \'<div class="fw-bold fs-5">\' + (m ? m.meetings : \'—\') +
            \'</div><div class="small text-muted">\' + LABELS.meetings + \'</div>\' + cend;
        html += card + \'<div class="fw-bold fs-5">\' + (m ? m.hours : \'—\') +
            \'</div><div class="small text-muted">\' + LABELS.hours + \'</div>\' + cend;
        html += card + \'<div class="fw-bold fs-5">\' + (m ? m.participants : \'—\') +
            \'</div><div class="small text-muted">\' + LABELS.participants + \'</div>\' + cend;
        var recs = data.total_recordings !== undefined ? data.total_recordings : \'—\';
        html += card + \'<div class="fw-bold fs-5">\' + recs +
            \'</div><div class="small text-muted">\' + LABELS.recordings + \'</div>\' + cend;
        html += \'</div>\';
        document.getElementById(\'ssv-stats-container\').innerHTML = html;
    })
    .catch(function() {
        document.getElementById(\'ssv-stats-container\').innerHTML =
            \'<p class="text-muted small">\' + LABELS.error + \'</p>\';
    });
})();
</script>';
        } else {
            $connhtml = '
<div class="card border-secondary mb-3">
  <div class="card-body d-flex align-items-center gap-3 p-3">
    <span class="fs-4">🔴</span>
    <div>
      <strong>' . get_string('not_connected', 'local_softsysvideo') . '</strong>
      <p class="mb-2 text-muted small">' . get_string('connect_instructions', 'local_softsysvideo') . '</p>
      <a href="' . $connecturl . '" class="btn btn-primary">🔌 ' . get_string('connect_account', 'local_softsysvideo') . '</a>
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
