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
 * Support tickets page for the SoftSys Video companion plugin.
 *
 * Lists existing support tickets and allows creating new ones via the external API.
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
$PAGE->set_url(new moodle_url('/local/softsysvideo/support.php'));
$PAGE->set_title(get_string('support', 'local_softsysvideo'));
$PAGE->set_heading(get_string('pluginname', 'local_softsysvideo'));
$PAGE->set_pagelayout('admin');
$PAGE->navbar->add(
    get_string('pluginname', 'local_softsysvideo'),
    new moodle_url('/local/softsysvideo/dashboard.php')
);
$PAGE->navbar->add(get_string('support', 'local_softsysvideo'));

$isconnected = !empty(get_config('local_softsysvideo', 'softsysvideo_plugin_key'));

if (!$isconnected) {
    redirect(new moodle_url('/local/softsysvideo/connect.php'));
}

$jsstrings = [
    'no_tickets' => get_string('no_tickets', 'local_softsysvideo'),
    'submit_ticket' => get_string('submit_ticket', 'local_softsysvideo'),
    'submitting' => get_string('loading', 'local_softsysvideo'),
    'ticket_created' => get_string('ticket_created', 'local_softsysvideo'),
    'previous' => get_string('previous', 'local_softsysvideo'),
    'next' => get_string('next', 'local_softsysvideo'),
];
$PAGE->requires->js_call_amd('local_softsysvideo/support_list', 'init', [$CFG->wwwroot, $jsstrings]);

echo $OUTPUT->header();

echo html_writer::start_div('container-fluid py-3');
echo local_softsysvideo_render_navigation('support');

// Page header.
echo html_writer::div(
    html_writer::tag('h2', get_string('support_tickets', 'local_softsysvideo'), ['class' => 'mb-0']) .
    html_writer::tag('button', get_string('create_ticket', 'local_softsysvideo'), [
        'id'    => 'ssv-support-create-btn',
        'class' => 'btn btn-primary btn-sm',
        'type'  => 'button',
    ]),
    'ssv-page-header'
);

// Error alert (hidden by default).
echo html_writer::div(
    get_string('request_failed', 'local_softsysvideo'),
    'alert alert-danger d-none',
    ['id' => 'ssv-support-error']
);

// Success notification (hidden by default).
echo html_writer::div(
    get_string('ticket_created', 'local_softsysvideo'),
    'alert alert-success d-none',
    ['id' => 'ssv-support-success']
);

// Create ticket form (hidden by default).
$subjectlabel = html_writer::tag(
    'label',
    get_string('ticket_subject', 'local_softsysvideo'),
    ['for' => 'ssv-ticket-subject', 'class' => 'form-label']
);
$subjectinput = html_writer::empty_tag('input', [
    'type'      => 'text',
    'id'        => 'ssv-ticket-subject',
    'name'      => 'subject',
    'class'     => 'form-control mb-3',
    'required'  => 'required',
    'maxlength' => '255',
]);
$desclabel = html_writer::tag(
    'label',
    get_string('ticket_description', 'local_softsysvideo'),
    ['for' => 'ssv-ticket-description', 'class' => 'form-label']
);
$desctextarea = html_writer::tag('textarea', '', [
    'id'       => 'ssv-ticket-description',
    'name'     => 'description',
    'class'    => 'form-control mb-3',
    'rows'     => '5',
    'required' => 'required',
]);
$courseidlabel = html_writer::tag(
    'label',
    get_string('ticket_course_id', 'local_softsysvideo'),
    ['for' => 'ssv-ticket-course-id', 'class' => 'form-label']
);
$courseidinput = html_writer::empty_tag('input', [
    'type'  => 'text',
    'id'    => 'ssv-ticket-course-id',
    'name'  => 'course_id',
    'class' => 'form-control mb-4',
]);
$submitbtn = html_writer::tag(
    'button',
    get_string('submit_ticket', 'local_softsysvideo'),
    ['type' => 'button', 'id' => 'ssv-ticket-submit', 'class' => 'btn btn-primary me-2']
);
$cancelbtn = html_writer::tag(
    'button',
    get_string('cancel', 'core'),
    ['type' => 'button', 'id' => 'ssv-ticket-cancel', 'class' => 'btn btn-outline-secondary']
);
$forminner = $subjectlabel . $subjectinput .
    $desclabel . $desctextarea .
    $courseidlabel . $courseidinput .
    $submitbtn . $cancelbtn;
echo html_writer::div(
    html_writer::div($forminner, 'card-body'),
    'ssv-connect-card card mb-4 d-none',
    ['id' => 'ssv-support-form']
);

// Spinner.
echo html_writer::div(
    $OUTPUT->pix_icon('i/loading', '', 'moodle', ['class' => 'icon-lg']),
    'text-center py-3',
    ['id' => 'ssv-support-spinner']
);

// Tickets table (hidden until data loads).
$th  = html_writer::tag('th', get_string('ticket_subject', 'local_softsysvideo'));
$th .= html_writer::tag('th', get_string('ticket_status', 'local_softsysvideo'));
$th .= html_writer::tag('th', get_string('ticket_priority', 'local_softsysvideo'));
$th .= html_writer::tag('th', get_string('ticket_date', 'local_softsysvideo'));
$thead = html_writer::tag('thead', html_writer::tag('tr', $th));
$tbody = html_writer::tag('tbody', '', ['id' => 'ssv-support-tbody']);
$table = html_writer::tag('table', $thead . $tbody, [
    'class' => 'generaltable table local-softsysvideo-table',
]);
$tablehtml = html_writer::div($table, 'ssv-table-wrapper');
$counthtml = html_writer::tag('p', '', ['class' => 'text-muted small mt-2', 'id' => 'ssv-support-count']);
$paginationhtml = html_writer::div('', 'd-flex align-items-center mt-2 ssv-flex-gap', ['id' => 'ssv-support-pagination']);
echo html_writer::div(
    $tablehtml . $counthtml . $paginationhtml,
    'd-none',
    ['id' => 'ssv-support-container']
);

echo html_writer::end_div();
echo $OUTPUT->footer();
