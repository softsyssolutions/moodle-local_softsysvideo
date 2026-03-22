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
$PAGE->set_title('Soporte SoftSys Video');
$PAGE->set_heading('Soporte SoftSys Video');

$action = optional_param('action', '', PARAM_ALPHA);

// AJAX: enviar ticket
if ($action === 'submit') {
    header('Content-Type: application/json');
    require_sesskey();

    $subject     = required_param('subject', PARAM_TEXT);
    $description = required_param('description', PARAM_TEXT);
    $courseId    = optional_param('course_id', '', PARAM_TEXT);

    $apiUrl    = get_config('local_softsysvideo', 'softsysvideo_api_url');
    $pluginKey = get_config('local_softsysvideo', 'softsysvideo_plugin_key');

    if (empty($apiUrl) || empty($pluginKey)) {
        echo json_encode(['ok' => false, 'error' => 'Plugin not configured']);
        exit;
    }

    try {
        require_once($CFG->dirroot . '/local/softsysvideo/classes/api_client.php');
        $client = new \local_softsysvideo\api_client($apiUrl, $pluginKey);
        $result = $client->create_support_ticket([
            'subject'          => $subject,
            'description'      => $description,
            'moodle_site_url'  => $CFG->wwwroot,
            'moodle_course_id' => $courseId,
            'fullName'         => fullname($USER) . ' (' . $USER->email . ')',
        ]);
        echo json_encode(['ok' => true, 'result' => $result]);
    } catch (\Exception $e) {
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_softsysvideo/support_form', [
    'sesskey'   => sesskey(),
    'wwwroot'   => $CFG->wwwroot,
]);
echo $OUTPUT->footer();
