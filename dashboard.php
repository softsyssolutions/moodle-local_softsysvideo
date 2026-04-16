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
$PAGE->navbar->add(
    get_string('pluginname', 'local_softsysvideo'),
    new moodle_url('/local/softsysvideo/dashboard.php')
);
$PAGE->navbar->add(get_string('dashboard', 'local_softsysvideo'));

$isconnected = !empty(get_config('local_softsysvideo', 'softsysvideo_plugin_key'));

if (!$isconnected) {
    redirect(new moodle_url('/local/softsysvideo/connect.php'));
}

$tenantname = get_config('local_softsysvideo', 'softsysvideo_tenant_name') ?: 'SoftSys Video';

$PAGE->requires->js_call_amd('local_softsysvideo/dashboard', 'init');
$PAGE->requires->js_call_amd('local_softsysvideo/analytics', 'init');

echo $OUTPUT->header();
echo html_writer::start_div('container-fluid py-3');
echo local_softsysvideo_render_navigation('dashboard');

// Connected: display dashboard content.
// Page header with connected badge.
$badgetext = get_string('connected', 'local_softsysvideo') . ' — ' . s($tenantname);
$badge = html_writer::tag('span', $badgetext, ['class' => 'badge', 'id' => 'ssv-tenant-name']);
$headertitle = html_writer::tag('h2', get_string('dashboard', 'local_softsysvideo'), ['class' => 'mb-0']);
echo html_writer::div(
    $headertitle . $badge,
    'ssv-page-header'
);

echo html_writer::div(
    get_string('request_failed', 'local_softsysvideo'),
    'alert alert-danger d-none',
    ['id' => 'ssv-stats-error']
);

// Stats cards — 6 KPIs in 2 rows of 3.
$statcard = function ($id, $colorclass, $labelkey) {
    $inner = html_writer::div('—', 'ssv-stat-value ' . $colorclass, ['id' => $id]);
    $inner .= html_writer::div(get_string($labelkey, 'local_softsysvideo'), 'ssv-stat-label');
    return html_writer::div(
        html_writer::div($inner, 'card-body'),
        'card ssv-stat-card text-center h-100'
    );
};

echo html_writer::div(
    html_writer::div($statcard('ssv-stat-meetings', 'text-primary', 'this_month_meetings'), 'col-6 col-md-4') .
    html_writer::div($statcard('ssv-stat-hours', 'text-info', 'video_hours'), 'col-6 col-md-4') .
    html_writer::div($statcard('ssv-stat-session-minutes', 'text-primary', 'sessionminutes'), 'col-6 col-md-4') .
    html_writer::div($statcard('ssv-stat-participants', 'text-warning', 'total_participants'), 'col-6 col-md-4') .
    html_writer::div($statcard('ssv-stat-recordings', 'text-success', 'total_recordings'), 'col-6 col-md-4') .
    html_writer::div($statcard('ssv-stat-recording-minutes', 'text-success', 'recordingminutes'), 'col-6 col-md-4'),
    'row g-3 mb-4'
);

// Analytics section header with range selector.
$analyticsheader = html_writer::tag(
    'h5',
    get_string('usage_over_time', 'local_softsysvideo'),
    ['class' => 'mb-0 fw-bold', 'style' => 'color: var(--ssv-text); font-size: 1rem;']
);
$rangebtn = function ($range, $label, $active = false) {
    $cls = 'btn btn-sm ssv-range-btn ' . ($active ? 'btn-primary' : 'btn-outline-secondary');
    return html_writer::tag('button', $label, [
        'class'      => $cls,
        'type'       => 'button',
        'data-range' => $range,
    ]);
};
$rangebtns = html_writer::div(
    $rangebtn('7d', get_string('range_7d', 'local_softsysvideo')) .
    $rangebtn('30d', get_string('range_30d', 'local_softsysvideo'), true) .
    $rangebtn('90d', get_string('range_90d', 'local_softsysvideo')),
    'btn-group ssv-flex-gap'
);
echo html_writer::div(
    html_writer::div($analyticsheader, '') .
    html_writer::div($rangebtns, ''),
    'd-flex justify-content-between align-items-center mt-2 mb-3'
);

// Analytics KPI cards row.
$akpi = function ($id, $colorclass, $labelkey) use ($statcard) {
    $inner = html_writer::div('—', 'ssv-stat-value ' . $colorclass, ['id' => $id]);
    $inner .= html_writer::div(get_string($labelkey, 'local_softsysvideo'), 'ssv-stat-label');
    return html_writer::div(
        html_writer::div($inner, 'card-body'),
        'card ssv-stat-card text-center h-100'
    );
};
echo html_writer::div(
    html_writer::div($akpi('ssv-analytics-total-sessions', 'text-primary', 'total_sessions'), 'col-6 col-md-3') .
    html_writer::div($akpi('ssv-analytics-total-minutes', 'text-info', 'total_minutes'), 'col-6 col-md-3') .
    html_writer::div($akpi('ssv-analytics-total-rec-minutes', 'text-success', 'recordingminutes'), 'col-6 col-md-3') .
    html_writer::div($akpi('ssv-analytics-recordings-count', 'text-warning', 'total_recordings'), 'col-6 col-md-3'),
    'row g-3 mb-3 ssv-kpi-row'
);

echo html_writer::div(
    $OUTPUT->pix_icon('i/loading', '', 'moodle', ['class' => 'icon-lg']),
    'text-center py-3',
    ['id' => 'ssv-analytics-spinner']
);
echo html_writer::div(
    get_string('analytics_unavailable', 'local_softsysvideo'),
    'alert alert-warning d-none',
    ['id' => 'ssv-analytics-error']
);

// Charts row.
$chart1 = html_writer::div(
    html_writer::div(
        html_writer::tag('h6', get_string('sessions_over_time', 'local_softsysvideo'), ['class' => 'card-title']) .
        html_writer::tag('canvas', '', ['id' => 'ssv-chart-meetings', 'height' => '200']),
        'card-body'
    ),
    'card ssv-chart-card'
);
$chart2 = html_writer::div(
    html_writer::div(
        html_writer::tag('h6', get_string('minutes_consumed', 'local_softsysvideo'), ['class' => 'card-title']) .
        html_writer::tag('canvas', '', ['id' => 'ssv-chart-minutes', 'height' => '200']),
        'card-body'
    ),
    'card ssv-chart-card'
);
echo html_writer::div(
    html_writer::div($chart1, 'col-md-6') . html_writer::div($chart2, 'col-md-6'),
    'row g-3 mb-4',
    ['id' => 'ssv-charts-row']
);

echo html_writer::end_div();
echo $OUTPUT->footer();
