<?php
require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

use local_softsysvideo\api_client;
use local_softsysvideo\bbb_configurator;

$systemcontext = context_system::instance();

require_login();
require_capability('local/softsysvideo:manage', $systemcontext);

$pageurl = new moodle_url('/local/softsysvideo/setup.php');
$PAGE->set_context($systemcontext);
$PAGE->set_url($pageurl);
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('setup_wizard', 'local_softsysvideo'));
$PAGE->set_heading(get_string('pluginname', 'local_softsysvideo'));

$action = optional_param('action', '', PARAM_ALPHA);

if ($action !== '') {
    $apiurl = trim((string)optional_param('api_url', '', PARAM_URL));
    $pluginkey = trim((string)optional_param('plugin_key', '', PARAM_RAW_TRIMMED));
    $sharedsecret = trim((string)get_config('local_softsysvideo', 'softsysvideo_shared_secret'));
    $response = ['ok' => false];

    try {
        if (!confirm_sesskey()) {
            throw new moodle_exception('invalidsesskey');
        }

        if ($apiurl === '' || $pluginkey === '') {
            throw new moodle_exception('missing_connection_fields', 'local_softsysvideo');
        }

        $client = new api_client($apiurl, $pluginkey);

        if ($action === 'test') {
            $ok = $client->test_connection();
            $health = $client->get_last_health();
            $credits = $ok ? $client->get_credits() : [];

            $response = [
                'ok' => $ok,
                'balance' => $credits['balance'] ?? $credits['current_balance'] ?? 0,
                'tenant_name' => $health['tenant_name'] ?? $health['tenant'] ?? '',
                'message' => $ok ? get_string('connection_success', 'local_softsysvideo') : get_string('connection_failed', 'local_softsysvideo'),
            ];
        } else if ($action === 'configure') {
            if ($sharedsecret === '') {
                throw new moodle_exception('missing_shared_secret', 'local_softsysvideo');
            }

            $configurator = new bbb_configurator();
            if (!$configurator->is_bbb_installed()) {
                throw new moodle_exception('bbb_not_installed', 'local_softsysvideo');
            }

            if (!$client->test_connection()) {
                throw new moodle_exception('connection_failed', 'local_softsysvideo');
            }

            $configured = $configurator->configure_bbb($apiurl, $sharedsecret);
            if ($configured) {
                set_config('softsysvideo_api_url', $apiurl, 'local_softsysvideo');
                set_config('softsysvideo_plugin_key', $pluginkey, 'local_softsysvideo');
            }

            $response = [
                'ok' => $configured,
                'message' => $configured ? get_string('bbb_configured', 'local_softsysvideo') : get_string('connection_failed', 'local_softsysvideo'),
            ];
        } else {
            throw new moodle_exception('invalidparameter');
        }
    } catch (Throwable $e) {
        $response['message'] = $e->getMessage();
    }

    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

$bbbconfigurator = new bbb_configurator();
$storedapiurl = (string)get_config('local_softsysvideo', 'softsysvideo_api_url');
$storedpluginkey = (string)get_config('local_softsysvideo', 'softsysvideo_plugin_key');
$bbbinstalled = $bbbconfigurator->is_bbb_installed();
$connected = $bbbinstalled && $storedapiurl !== '' && $bbbconfigurator->is_configured_for_softsysvideo($storedapiurl);
$currentbbb = $bbbconfigurator->get_current_bbb_config();

$templatecontext = [
    'sesskey' => sesskey(),
    'apiurl' => $storedapiurl,
    'pluginkey' => $storedpluginkey,
    'connected' => $connected,
    'statusclass' => $connected ? 'alert-success' : 'alert-danger',
    'statuslabel' => $connected ? get_string('connected', 'local_softsysvideo') : get_string('not_connected', 'local_softsysvideo'),
    'statusmessage' => $bbbinstalled
        ? get_string('current_bbb_server', 'local_softsysvideo') . ': ' . ($currentbbb['server_url'] ?: get_string('not_connected', 'local_softsysvideo'))
        : get_string('bbb_not_installed', 'local_softsysvideo'),
    'testinglabel' => get_string('testing', 'local_softsysvideo'),
    'requestfailedlabel' => get_string('request_failed', 'local_softsysvideo'),
];

$PAGE->requires->js_call_amd('local_softsysvideo/setup', 'init', [$pageurl->out(false)]);

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_softsysvideo/setup_wizard', $templatecontext);
echo $OUTPUT->footer();
