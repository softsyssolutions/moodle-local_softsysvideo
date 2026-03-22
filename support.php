<?php
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
