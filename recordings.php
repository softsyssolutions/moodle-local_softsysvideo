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

$isconnected = !empty(get_config('local_softsysvideo', 'softsysvideo_plugin_key'));
$apiurl = get_config('local_softsysvideo', 'softsysvideo_api_url') ?: 'https://api.softsysvideo.com';
$pluginkey = get_config('local_softsysvideo', 'softsysvideo_plugin_key') ?: '';

if ($isconnected) {
    $PAGE->requires->js_call_amd('local_softsysvideo/recordings', 'init', [$apiurl, $pluginkey]);
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
    ['class' => 'btn btn-sm btn-secondary']
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
echo html_writer::tag('h2', get_string('recordings', 'local_softsysvideo'));

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
        ['id' => 'ssv-recordings-error']
    );

    $searchinput = html_writer::empty_tag('input', [
        'type' => 'text',
        'id' => 'ssv-recordings-search',
        'class' => 'form-control w-auto',
        'placeholder' => get_string('search') . '...',
        'style' => 'max-width:250px',
    ]);
    $pagination = html_writer::div('', 'd-flex gap-2 align-items-center', ['id' => 'ssv-recordings-pagination']);
    echo html_writer::div(
        $searchinput . $pagination,
        'd-flex justify-content-between align-items-center mb-2'
    );

    $spinner = html_writer::div(
        html_writer::div(
            html_writer::tag('span', get_string('loading', 'local_softsysvideo'), ['class' => 'visually-hidden']),
            'spinner-border text-primary',
            ['role' => 'status']
        ),
        'text-center py-3',
        ['id' => 'ssv-recordings-spinner']
    );
    echo $spinner;

    $th = html_writer::tag('th', get_string('recording_name', 'local_softsysvideo'));
    $th .= html_writer::tag('th', get_string('meeting', 'local_softsysvideo'));
    $th .= html_writer::tag('th', get_string('date', 'local_softsysvideo'));
    $th .= html_writer::tag('th', get_string('duration', 'local_softsysvideo'));
    $th .= html_writer::tag('th', get_string('size', 'local_softsysvideo'));
    $th .= html_writer::tag('th', get_string('play', 'local_softsysvideo'));
    $thead = html_writer::tag('thead', html_writer::tag('tr', $th), ['class' => 'table-dark']);
    $tbody = html_writer::tag('tbody', '', ['id' => 'ssv-recordings-tbody']);
    $table = html_writer::tag('table', $thead . $tbody, ['class' => 'table table-striped table-hover']);
    $tablehtml = html_writer::div($table, 'table-responsive');
    $counthtml = html_writer::tag('p', '', ['class' => 'text-muted small', 'id' => 'ssv-recordings-count']);
    echo html_writer::div($tablehtml . $counthtml, 'd-none', ['id' => 'ssv-recordings-container']);
}

echo html_writer::end_div();
echo $OUTPUT->footer();
