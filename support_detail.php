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
 * Support ticket detail page for the SoftSys Video companion plugin.
 *
 * Displays the detail of a single support ticket including its message history.
 *
 * @package    local_softsysvideo
 * @copyright  2026 SoftSys Solutions {@link https://softsyssolutions.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->libdir . '/adminlib.php');

require_login();
require_capability('local/softsysvideo:manage', context_system::instance());

$ticketid = required_param('id', PARAM_INT);

$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/local/softsysvideo/support_detail.php', ['id' => $ticketid]));
$PAGE->set_title(get_string('ticket_detail', 'local_softsysvideo'));
$PAGE->set_heading(get_string('pluginname', 'local_softsysvideo'));
$PAGE->set_pagelayout('admin');

$apiurl = get_config('local_softsysvideo', 'softsysvideo_api_url') ?: 'https://api.softsysvideo.com';
$pluginkey = get_config('local_softsysvideo', 'softsysvideo_plugin_key') ?: '';

$PAGE->requires->js_call_amd('local_softsysvideo/support_detail', 'init', [$apiurl, $pluginkey, $ticketid]);

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
    ['class' => 'btn btn-sm btn-danger']
);
$navhtml = html_writer::div(implode('', $navlinks), 'd-flex gap-2 mb-3 flex-wrap');

echo $OUTPUT->header();

echo html_writer::start_div('container-fluid py-3');
echo $navhtml;

// Back button.
echo html_writer::link(
    new moodle_url('/local/softsysvideo/support.php'),
    html_writer::tag('span', '&laquo; ', []) . get_string('back_to_list', 'local_softsysvideo'),
    ['class' => 'btn btn-sm btn-outline-secondary mb-3']
);

echo html_writer::tag('h2', get_string('ticket_detail', 'local_softsysvideo'));

// Error alert (hidden by default).
echo html_writer::div(
    get_string('request_failed', 'local_softsysvideo'),
    'alert alert-danger d-none',
    ['id' => 'ssv-detail-error']
);

// Spinner.
$spinner = html_writer::div(
    html_writer::div(
        html_writer::tag('span', get_string('loading', 'local_softsysvideo'), ['class' => 'visually-hidden']),
        'spinner-border text-primary',
        ['role' => 'status']
    ),
    'text-center py-3',
    ['id' => 'ssv-detail-spinner']
);
echo $spinner;

// Ticket info card (hidden until data loads).
$subjectrow = html_writer::tag(
    'p',
    html_writer::tag('strong', get_string('ticket_subject', 'local_softsysvideo') . ': ') .
    html_writer::tag('span', '', ['id' => 'ssv-detail-subject'])
);
$statusrow = html_writer::tag(
    'p',
    html_writer::tag('strong', get_string('ticket_status', 'local_softsysvideo') . ': ') .
    html_writer::tag('span', '', ['id' => 'ssv-detail-status'])
);
$priorityrow = html_writer::tag(
    'p',
    html_writer::tag('strong', get_string('ticket_priority', 'local_softsysvideo') . ': ') .
    html_writer::tag('span', '', ['id' => 'ssv-detail-priority'])
);
$daterow = html_writer::tag(
    'p',
    html_writer::tag('strong', get_string('ticket_date', 'local_softsysvideo') . ': ') .
    html_writer::tag('span', '', ['id' => 'ssv-detail-date'])
);

echo html_writer::div(
    html_writer::div($subjectrow . $statusrow . $priorityrow . $daterow, 'card-body'),
    'card mb-4 d-none',
    ['id' => 'ssv-detail-card']
);

// Messages section (hidden until data loads).
$messagesheading = html_writer::tag('h4', get_string('messages_title', 'local_softsysvideo'), ['class' => 'mb-3']);
$timeline = html_writer::div('', '', ['id' => 'ssv-detail-timeline']);
echo html_writer::div(
    $messagesheading . $timeline,
    'd-none',
    ['id' => 'ssv-detail-messages']
);

echo html_writer::end_div();
echo $OUTPUT->footer();
