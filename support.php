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
 * Support page for the SoftSys Video companion plugin.
 *
 * Sends a support request email to the Moodle site administrator.
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
$PAGE->set_heading(get_string('support', 'local_softsysvideo'));

$action = optional_param('action', '', PARAM_ALPHA);

// AJAX: enviar ticket por email al admin del sitio
if ($action === 'submit') {
    header('Content-Type: application/json');
    require_sesskey();

    $subject     = required_param('subject', PARAM_TEXT);
    $description = required_param('description', PARAM_TEXT);
    $courseId    = optional_param('course_id', '', PARAM_TEXT);

    $admin = get_admin();
    $from  = $USER;

    $messagetext  = strip_tags($description) . "\n\n";
    $messagetext .= "Moodle: {$CFG->wwwroot}\n";
    $messagetext .= "Plugin: local_softsysvideo\n";
    if ($courseId) {
        $messagetext .= "Course ID: {$courseId}\n";
    }

    $messagehtml  = html_writer::tag('p', format_text($description, FORMAT_PLAIN));
    $messagehtml .= html_writer::tag('p', 'Moodle: ' . $CFG->wwwroot);
    $messagehtml .= html_writer::tag('p', 'Plugin: local_softsysvideo');
    if ($courseId) {
        $messagehtml .= html_writer::tag('p', 'Course ID: ' . s($courseId));
    }

    $result = email_to_user(
        $admin,
        $from,
        '[' . get_string('pluginname', 'local_softsysvideo') . ' Support] ' . $subject,
        $messagetext,
        $messagehtml
    );

    if ($result) {
        echo json_encode(['ok' => true]);
    } else {
        echo json_encode(['ok' => false, 'error' => get_string('support_error', 'local_softsysvideo')]);
    }
    exit;
}

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_softsysvideo/support_form', [
    'sesskey' => sesskey(),
    'wwwroot' => $CFG->wwwroot,
]);
echo $OUTPUT->footer();
