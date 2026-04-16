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
 * Recordings page for the SoftSys Video companion plugin.
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
$PAGE->set_url(new moodle_url('/local/softsysvideo/recordings.php'));
$PAGE->set_title(get_string('recordings', 'local_softsysvideo'));
$PAGE->set_heading(get_string('pluginname', 'local_softsysvideo'));
$PAGE->set_pagelayout('admin');
$PAGE->navbar->add(
    get_string('pluginname', 'local_softsysvideo'),
    new moodle_url('/local/softsysvideo/dashboard.php')
);
$PAGE->navbar->add(get_string('recordings', 'local_softsysvideo'));

$isconnected = !empty(get_config('local_softsysvideo', 'softsysvideo_plugin_key'));

if (!$isconnected) {
    redirect(new moodle_url('/local/softsysvideo/connect.php'));
}

$filterstrs = [
    'no_recordings' => get_string('no_recordings', 'local_softsysvideo'),
    'total_recordings' => get_string('total_recordings', 'local_softsysvideo'),
    'previous' => get_string('previous', 'local_softsysvideo'),
    'next' => get_string('next', 'local_softsysvideo'),
    'page_x_of_y' => get_string('page_x_of_y', 'local_softsysvideo', (object)['current' => '{current}', 'total' => '{total}']),
    'play' => get_string('play', 'local_softsysvideo'),
];
$PAGE->requires->js_call_amd('local_softsysvideo/recordings', 'init', [$filterstrs]);

echo $OUTPUT->header();

echo html_writer::start_div('container-fluid py-3');
echo local_softsysvideo_render_navigation('recordings');

// Page header.
echo html_writer::div(
    html_writer::tag('h2', get_string('recordings', 'local_softsysvideo'), ['class' => 'mb-0']) .
    html_writer::tag('span', get_string('recordings_desc', 'local_softsysvideo'), [
        'class' => 'text-muted',
        'style' => 'font-size:0.85rem;',
    ]),
    'ssv-page-header'
);

echo html_writer::div(
    get_string('request_failed', 'local_softsysvideo'),
    'alert alert-danger d-none',
    ['id' => 'ssv-recordings-error']
);

// Search + pagination row.
$searchcol = html_writer::div(
    html_writer::tag(
        'label',
        get_string('search'),
        ['for' => 'ssv-recordings-search', 'class' => 'small text-muted d-block']
    ) .
    html_writer::empty_tag('input', [
        'type'        => 'text',
        'id'          => 'ssv-recordings-search',
        'class'       => 'form-control form-control-sm',
        'placeholder' => get_string('search') . '...',
    ]),
    'col'
);
$pagination = html_writer::div(
    '',
    'd-flex align-items-end ssv-flex-gap',
    ['id' => 'ssv-recordings-pagination']
);
echo html_writer::div(
    $searchcol . html_writer::div($pagination, 'col-auto'),
    'row g-2 align-items-end mb-2'
);

// Filter row.
$stateopts = [
    ''           => get_string('state_all', 'local_softsysvideo'),
    'ready'      => get_string('state_ready', 'local_softsysvideo'),
    'processing' => get_string('state_processing', 'local_softsysvideo'),
    'failed'     => get_string('state_failed', 'local_softsysvideo'),
];
$sortbyopts = [
    'created_at'       => get_string('sort_date', 'local_softsysvideo'),
    'duration_seconds' => get_string('sort_duration', 'local_softsysvideo'),
    'size_bytes'       => get_string('sort_size', 'local_softsysvideo'),
    'name'             => get_string('sort_name', 'local_softsysvideo'),
];
$sortorderopts = [
    'desc' => get_string('descending', 'local_softsysvideo'),
    'asc'  => get_string('ascending', 'local_softsysvideo'),
];

$ctl = 'form-control form-control-sm';

$fc1 = html_writer::div(
    html_writer::tag('label', get_string('status', 'local_softsysvideo'),
        ['for' => 'ssv-filter-state']) .
    html_writer::select($stateopts, 'ssv-filter-state', '', false,
        ['id' => 'ssv-filter-state', 'class' => $ctl]),
    'col'
);
$fc2 = html_writer::div(
    html_writer::tag('label', get_string('date_from', 'local_softsysvideo'),
        ['for' => 'ssv-filter-date-from']) .
    html_writer::empty_tag('input', ['type' => 'date', 'id' => 'ssv-filter-date-from', 'class' => $ctl]),
    'col'
);
$fc3 = html_writer::div(
    html_writer::tag('label', get_string('date_to', 'local_softsysvideo'),
        ['for' => 'ssv-filter-date-to']) .
    html_writer::empty_tag('input', ['type' => 'date', 'id' => 'ssv-filter-date-to', 'class' => $ctl]),
    'col'
);
$fc4 = html_writer::div(
    html_writer::tag('label', get_string('sort_by', 'local_softsysvideo'),
        ['for' => 'ssv-filter-sort-by']) .
    html_writer::select($sortbyopts, 'ssv-filter-sort-by', 'created_at', false,
        ['id' => 'ssv-filter-sort-by', 'class' => $ctl]),
    'col'
);
$fc5 = html_writer::div(
    html_writer::tag('label', get_string('sort_order', 'local_softsysvideo'),
        ['for' => 'ssv-filter-sort-order']) .
    html_writer::select($sortorderopts, 'ssv-filter-sort-order', 'desc', false,
        ['id' => 'ssv-filter-sort-order', 'class' => $ctl]),
    'col-auto'
);
$fc6 = html_writer::div(
    html_writer::tag('label', '&nbsp;') .
    html_writer::tag('button', get_string('apply_filters', 'local_softsysvideo'), [
        'class' => 'btn btn-sm btn-primary d-block',
        'id'    => 'ssv-filter-apply',
        'type'  => 'button',
    ]),
    'col-auto'
);
echo html_writer::div(
    $fc1 . $fc2 . $fc3 . $fc4 . $fc5 . $fc6,
    'ssv-filter-row row g-2 align-items-end mb-3'
);

echo html_writer::div(
    $OUTPUT->pix_icon('i/loading', '', 'moodle', ['class' => 'icon-lg']),
    'text-center py-3',
    ['id' => 'ssv-recordings-spinner']
);

$th  = html_writer::tag('th', get_string('recording_name', 'local_softsysvideo'));
$th .= html_writer::tag('th', get_string('meeting', 'local_softsysvideo'));
$th .= html_writer::tag('th', get_string('date', 'local_softsysvideo'));
$th .= html_writer::tag('th', get_string('duration', 'local_softsysvideo'));
$th .= html_writer::tag('th', get_string('size', 'local_softsysvideo'));
$th .= html_writer::tag('th', get_string('play', 'local_softsysvideo'));
$thead = html_writer::tag('thead', html_writer::tag('tr', $th));
$tbody = html_writer::tag('tbody', '', ['id' => 'ssv-recordings-tbody']);
$table = html_writer::tag('table', $thead . $tbody, [
    'class' => 'generaltable table local-softsysvideo-table',
]);
$tablehtml = html_writer::div($table, 'ssv-table-wrapper');
$counthtml = html_writer::tag('p', '', ['class' => 'text-muted small mt-2', 'id' => 'ssv-recordings-count']);
echo html_writer::div($tablehtml . $counthtml, 'd-none', ['id' => 'ssv-recordings-container']);

echo html_writer::end_div();
echo $OUTPUT->footer();
