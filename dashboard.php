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
 * Dashboard page for the SoftSys Video companion plugin.
 *
 * @package    local_softsysvideo
 * @copyright  2026 SoftSys Solutions {@link https://softsyssolutions.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->libdir . '/adminlib.php');

require_login();
require_capability('local/softsysvideo:manage', context_system::instance());

$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/local/softsysvideo/dashboard.php'));
$PAGE->set_title(get_string('dashboard', 'local_softsysvideo'));
$PAGE->set_heading(get_string('pluginname', 'local_softsysvideo'));
$PAGE->set_pagelayout('admin');

$isconnected = !empty(get_config('local_softsysvideo', 'softsysvideo_plugin_key'));
$apiurl = get_config('local_softsysvideo', 'softsysvideo_api_url') ?: 'https://api.softsysvideo.com';
$pluginkey = get_config('local_softsysvideo', 'softsysvideo_plugin_key') ?: '';
$tenantname = get_config('local_softsysvideo', 'softsysvideo_tenant_name') ?: 'SoftSys Video';

if ($isconnected) {
    $PAGE->requires->js_call_amd('local_softsysvideo/dashboard', 'init', [$apiurl, $pluginkey]);
    $PAGE->requires->js_call_amd('local_softsysvideo/analytics', 'init', [$apiurl, $pluginkey]);
}

// Build plugin navigation.
$navlinks = [];
$navlinks[] = html_writer::link(
    new moodle_url('/local/softsysvideo/dashboard.php'),
    get_string('dashboard', 'local_softsysvideo'),
    ['class' => 'btn btn-sm btn-primary']
);
$navlinks[] = html_writer::link(
    new moodle_url('/local/softsysvideo/recordings.php'),
    get_string('recordings', 'local_softsysvideo'),
    ['class' => 'btn btn-sm btn-outline-secondary']
);
$navlinks[] = html_writer::link(
    new moodle_url('/local/softsysvideo/meetings.php'),
    get_string('meetings', 'local_softsysvideo'),
    ['class' => 'btn btn-sm btn-outline-secondary']
);
$navlinks[] = html_writer::link(
    new moodle_url('/local/softsysvideo/connect.php'),
    get_string('connection', 'local_softsysvideo'),
    ['class' => 'btn btn-sm btn-outline-secondary']
);
$navlinks[] = html_writer::link(
    new moodle_url('/local/softsysvideo/support.php'),
    get_string('support', 'local_softsysvideo'),
    ['class' => 'btn btn-sm btn-outline-danger']
);
$navhtml = html_writer::div(implode('', $navlinks), 'd-flex gap-2 mb-3 flex-wrap');

echo $OUTPUT->header();
echo html_writer::start_div('container-fluid py-3');
echo $navhtml;

if (!$isconnected) {
    echo $OUTPUT->notification(
        get_string('not_connected', 'local_softsysvideo') . ' ' .
        html_writer::link(
            new moodle_url('/local/softsysvideo/connect.php'),
            get_string('connect_account', 'local_softsysvideo')
        ),
        \core\output\notification::NOTIFY_WARNING
    );
} else {
    // Header section.
    $badgetext = get_string('connected', 'local_softsysvideo') . ' &mdash; ' . htmlspecialchars($tenantname);
    $badge = html_writer::tag('span', $badgetext, ['class' => 'badge bg-success', 'id' => 'ssv-tenant-name']);
    $headercontent = html_writer::tag('h2', get_string('dashboard', 'local_softsysvideo'), ['class' => 'mb-0']);
    $headercontent .= $badge;
    echo html_writer::div(
        html_writer::div($headercontent, ''),
        'd-flex align-items-center gap-3 mb-4'
    );

    // Stats cards.
    $statcard = function ($id, $colorclass, $labelkey) {
        $inner = html_writer::div('&mdash;', 'display-6 fw-bold ' . $colorclass, ['id' => $id]);
        $inner .= html_writer::div(get_string($labelkey, 'local_softsysvideo'), 'text-muted small mt-1');
        return html_writer::div(
            html_writer::div($inner, 'card-body'),
            'card text-center h-100'
        );
    };
    $statsrow = html_writer::div(
        html_writer::div($statcard('ssv-stat-meetings', 'text-primary', 'this_month_meetings'), 'col-6 col-md-3') .
        html_writer::div($statcard('ssv-stat-hours', 'text-info', 'video_hours'), 'col-6 col-md-3') .
        html_writer::div($statcard('ssv-stat-participants', 'text-warning', 'total_participants'), 'col-6 col-md-3') .
        html_writer::div($statcard('ssv-stat-recordings', 'text-success', 'total_recordings'), 'col-6 col-md-3'),
        'row g-3 mb-4'
    );
    echo $statsrow;

    echo html_writer::div(
        get_string('request_failed', 'local_softsysvideo'),
        'alert alert-danger d-none',
        ['id' => 'ssv-stats-error']
    );

    // Analytics charts.
    echo html_writer::tag('h4', get_string('usage_over_time', 'local_softsysvideo'), ['class' => 'mt-4 mb-3']);
    echo html_writer::div(
        html_writer::div('', 'spinner-border text-primary', ['role' => 'status']),
        'text-center py-3',
        ['id' => 'ssv-analytics-spinner']
    );
    echo html_writer::div(
        get_string('analytics_unavailable', 'local_softsysvideo'),
        'alert alert-warning d-none',
        ['id' => 'ssv-analytics-error']
    );

    $chart1 = html_writer::div(
        html_writer::div(
            html_writer::tag('h6', get_string('sessions_over_time', 'local_softsysvideo'), ['class' => 'card-title']) .
            html_writer::tag('canvas', '', ['id' => 'ssv-chart-meetings', 'height' => '200']),
            'card-body'
        ),
        'card'
    );
    $chart2 = html_writer::div(
        html_writer::div(
            html_writer::tag('h6', get_string('minutes_consumed', 'local_softsysvideo'), ['class' => 'card-title']) .
            html_writer::tag('canvas', '', ['id' => 'ssv-chart-minutes', 'height' => '200']),
            'card-body'
        ),
        'card'
    );
    echo html_writer::div(
        html_writer::div($chart1, 'col-md-6') . html_writer::div($chart2, 'col-md-6'),
        'row g-3 mb-4',
        ['id' => 'ssv-charts-row']
    );

    // Quick links.
    $reccard = html_writer::div(
        html_writer::tag('h5', get_string('recordings', 'local_softsysvideo'), ['class' => 'card-title']) .
        html_writer::tag('p', get_string('recordings_desc', 'local_softsysvideo'), ['class' => 'card-text text-muted']) .
        $OUTPUT->single_button(
            new moodle_url('/local/softsysvideo/recordings.php'),
            get_string('recordings', 'local_softsysvideo'),
            'get',
            ['class' => 'btn-outline-primary']
        ),
        'card-body'
    );
    $meetcard = html_writer::div(
        html_writer::tag('h5', get_string('meetings', 'local_softsysvideo'), ['class' => 'card-title']) .
        html_writer::tag('p', get_string('meetings_desc', 'local_softsysvideo'), ['class' => 'card-text text-muted']) .
        $OUTPUT->single_button(
            new moodle_url('/local/softsysvideo/meetings.php'),
            get_string('meetings', 'local_softsysvideo'),
            'get',
            ['class' => 'btn-outline-primary']
        ),
        'card-body'
    );
    echo html_writer::div(
        html_writer::div(html_writer::div($reccard, 'card'), 'col-md-6') .
        html_writer::div(html_writer::div($meetcard, 'card'), 'col-md-6'),
        'row g-3'
    );
}

echo html_writer::end_div();
echo $OUTPUT->footer();
