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
 * Meetings page for the SoftSys Video companion plugin.
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
$PAGE->set_url(new moodle_url('/local/softsysvideo/meetings.php'));
$PAGE->set_title(get_string('meetings', 'local_softsysvideo'));
$PAGE->set_heading(get_string('pluginname', 'local_softsysvideo'));
$PAGE->set_pagelayout('admin');

$isconnected = !empty(get_config('local_softsysvideo', 'softsysvideo_plugin_key'));
$apiurl = get_config('local_softsysvideo', 'softsysvideo_api_url') ?: 'https://api.softsysvideo.com';
$pluginkey = get_config('local_softsysvideo', 'softsysvideo_plugin_key') ?: '';

if ($isconnected) {
    $filterstrs = [
        'filters' => get_string('filters', 'local_softsysvideo'),
        'show_filters' => get_string('show_filters', 'local_softsysvideo'),
        'hide_filters' => get_string('hide_filters', 'local_softsysvideo'),
        'all_statuses' => get_string('all_statuses', 'local_softsysvideo'),
        'active_meetings' => get_string('active_meetings', 'local_softsysvideo'),
        'ended_meetings' => get_string('ended_meetings', 'local_softsysvideo'),
        'date_from' => get_string('date_from', 'local_softsysvideo'),
        'date_to' => get_string('date_to', 'local_softsysvideo'),
        'with_recording' => get_string('with_recording', 'local_softsysvideo'),
        'without_recording' => get_string('without_recording', 'local_softsysvideo'),
        'sort_by' => get_string('sort_by', 'local_softsysvideo'),
        'sort_order' => get_string('sort_order', 'local_softsysvideo'),
        'ascending' => get_string('ascending', 'local_softsysvideo'),
        'descending' => get_string('descending', 'local_softsysvideo'),
        'sort_date' => get_string('sort_date', 'local_softsysvideo'),
        'sort_duration' => get_string('sort_duration', 'local_softsysvideo'),
        'sort_participants' => get_string('sort_participants', 'local_softsysvideo'),
        'sort_name' => get_string('sort_name', 'local_softsysvideo'),
        'apply_filters' => get_string('apply_filters', 'local_softsysvideo'),
    ];
    $PAGE->requires->js_call_amd('local_softsysvideo/meetings', 'init', [$apiurl, $pluginkey, $filterstrs]);
}

// Build plugin navigation.
$navlinks = [];
$navlinks[] = html_writer::link(
    new moodle_url('/local/softsysvideo/dashboard.php'),
    get_string('dashboard', 'local_softsysvideo'),
    ['class' => 'btn btn-sm btn-outline-primary']
);
$navlinks[] = html_writer::link(
    new moodle_url('/local/softsysvideo/recordings.php'),
    get_string('recordings', 'local_softsysvideo'),
    ['class' => 'btn btn-sm btn-outline-secondary']
);
$navlinks[] = html_writer::link(
    new moodle_url('/local/softsysvideo/meetings.php'),
    get_string('meetings', 'local_softsysvideo'),
    ['class' => 'btn btn-sm btn-secondary']
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
echo html_writer::tag('h2', get_string('meetings', 'local_softsysvideo'));

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
    echo html_writer::div(
        get_string('request_failed', 'local_softsysvideo'),
        'alert alert-danger d-none',
        ['id' => 'ssv-meetings-error']
    );

    // Row 1: Search + pagination.
    $searchcol = html_writer::div(
        html_writer::tag(
            'label',
            get_string('search'),
            ['for' => 'ssv-meetings-search', 'class' => 'small text-muted d-block']
        ) .
        html_writer::empty_tag('input', [
            'type' => 'text',
            'id' => 'ssv-meetings-search',
            'class' => 'form-control form-control-sm',
            'placeholder' => get_string('search') . '...',
        ]),
        'col'
    );
    $pagination = html_writer::div(
        '',
        'd-flex gap-2 align-items-end',
        ['id' => 'ssv-meetings-pagination']
    );
    echo html_writer::div(
        $searchcol . html_writer::div($pagination, 'col-auto'),
        'row g-2 align-items-end mb-2'
    );

    // Row 2: Filters — uniform labels above each field.
    $statusopts = [
        '' => get_string('all_statuses', 'local_softsysvideo'),
        'active' => get_string('active_meetings', 'local_softsysvideo'),
        'ended' => get_string('ended_meetings', 'local_softsysvideo'),
    ];
    $recopts = [
        '' => get_string('all_statuses', 'local_softsysvideo'),
        'true' => get_string('with_recording', 'local_softsysvideo'),
        'false' => get_string('without_recording', 'local_softsysvideo'),
    ];
    $sortbyopts = [
        'started_at' => get_string('sort_date', 'local_softsysvideo'),
        'duration_seconds' => get_string('sort_duration', 'local_softsysvideo'),
        'participant_count' => get_string('sort_participants', 'local_softsysvideo'),
        'name' => get_string('sort_name', 'local_softsysvideo'),
    ];
    $sortorderopts = [
        'desc' => get_string('descending', 'local_softsysvideo'),
        'asc' => get_string('ascending', 'local_softsysvideo'),
    ];

    $lbl = 'small text-muted d-block';
    $ctl = 'form-control form-control-sm';

    $fc1 = html_writer::div(
        html_writer::tag('label', get_string('status', 'local_softsysvideo'), [
            'for' => 'ssv-filter-status', 'class' => $lbl,
        ]) .
        html_writer::select(
            $statusopts,
            'ssv-filter-status',
            '',
            false,
            ['id' => 'ssv-filter-status', 'class' => $ctl]
        ),
        'col'
    );
    $fc2 = html_writer::div(
        html_writer::tag('label', get_string('recordings', 'local_softsysvideo'), [
            'for' => 'ssv-filter-recording', 'class' => $lbl,
        ]) .
        html_writer::select(
            $recopts,
            'ssv-filter-recording',
            '',
            false,
            ['id' => 'ssv-filter-recording', 'class' => $ctl]
        ),
        'col'
    );
    $fc3 = html_writer::div(
        html_writer::tag('label', get_string('date_from', 'local_softsysvideo'), [
            'for' => 'ssv-filter-date-from', 'class' => $lbl,
        ]) .
        html_writer::empty_tag('input', [
            'type' => 'date', 'id' => 'ssv-filter-date-from', 'class' => $ctl,
        ]),
        'col'
    );
    $fc4 = html_writer::div(
        html_writer::tag('label', get_string('date_to', 'local_softsysvideo'), [
            'for' => 'ssv-filter-date-to', 'class' => $lbl,
        ]) .
        html_writer::empty_tag('input', [
            'type' => 'date', 'id' => 'ssv-filter-date-to', 'class' => $ctl,
        ]),
        'col'
    );
    $fc5 = html_writer::div(
        html_writer::tag('label', get_string('sort_by', 'local_softsysvideo'), [
            'for' => 'ssv-filter-sort-by', 'class' => $lbl,
        ]) .
        html_writer::select(
            $sortbyopts,
            'ssv-filter-sort-by',
            'started_at',
            false,
            ['id' => 'ssv-filter-sort-by', 'class' => $ctl]
        ),
        'col'
    );
    $fc6 = html_writer::div(
        html_writer::tag('label', get_string('sort_order', 'local_softsysvideo'), [
            'for' => 'ssv-filter-sort-order', 'class' => $lbl,
        ]) .
        html_writer::select(
            $sortorderopts,
            'ssv-filter-sort-order',
            'desc',
            false,
            ['id' => 'ssv-filter-sort-order', 'class' => $ctl]
        ),
        'col-auto'
    );
    $fc7 = html_writer::div(
        html_writer::tag('label', '&nbsp;', ['class' => $lbl]) .
        html_writer::tag('button', get_string('apply_filters', 'local_softsysvideo'), [
            'class' => 'btn btn-sm btn-primary',
            'id' => 'ssv-filter-apply',
            'type' => 'button',
        ]),
        'col-auto'
    );
    echo html_writer::div(
        $fc1 . $fc2 . $fc3 . $fc4 . $fc5 . $fc6 . $fc7,
        'row g-2 align-items-end mb-3'
    );

    echo html_writer::div(
        $OUTPUT->pix_icon('i/loading', '', 'moodle', ['class' => 'icon-lg']),
        'text-center py-3',
        ['id' => 'ssv-meetings-spinner']
    );

    $th = html_writer::tag('th', get_string('meeting_name', 'local_softsysvideo'));
    $th .= html_writer::tag('th', get_string('start_date', 'local_softsysvideo'));
    $th .= html_writer::tag('th', get_string('duration', 'local_softsysvideo'));
    $th .= html_writer::tag('th', get_string('participants', 'local_softsysvideo'));
    $thead = html_writer::tag('thead', html_writer::tag('tr', $th), ['class' => 'table-dark']);
    $tbody = html_writer::tag('tbody', '', ['id' => 'ssv-meetings-tbody']);
    $table = html_writer::tag('table', $thead . $tbody, ['class' => 'table table-striped table-hover']);
    $tablehtml = html_writer::div($table, 'table-responsive');
    $counthtml = html_writer::tag('p', '', ['class' => 'text-muted small', 'id' => 'ssv-meetings-count']);
    echo html_writer::div($tablehtml . $counthtml, 'd-none', ['id' => 'ssv-meetings-container']);
}

echo html_writer::end_div();
echo $OUTPUT->footer();
