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
 * Connect page for the SoftSys Video companion plugin.
 *
 * The admin pastes a Dashboard API key (created at app.softsysvideo.com)
 * to link this Moodle installation with their SoftSys Video account.
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
$PAGE->set_url(new moodle_url('/local/softsysvideo/connect.php'));
$PAGE->set_title(get_string('connection', 'local_softsysvideo'));
$PAGE->set_heading(get_string('pluginname', 'local_softsysvideo'));
$PAGE->set_pagelayout('admin');
$PAGE->navbar->add(
    get_string('pluginname', 'local_softsysvideo'),
    new moodle_url('/local/softsysvideo/dashboard.php')
);
$PAGE->navbar->add(get_string('connection', 'local_softsysvideo'));

$action = optional_param('action', '', PARAM_ALPHA);
$isconnected = !empty(get_config('local_softsysvideo', 'softsysvideo_plugin_key'));
$tenantname = get_config('local_softsysvideo', 'softsysvideo_tenant_name') ?: '';
$apiurl = get_config('local_softsysvideo', 'softsysvideo_api_url') ?: '';

$message = '';
$messagetype = 'info';

require_once($CFG->libdir . '/filelib.php');

// Handle form submission.
if (data_submitted() && confirm_sesskey()) {
    if ($action === 'disconnect') {
        $connectionid = get_config('local_softsysvideo', 'softsysvideo_connection_id');
        $pluginkey = get_config('local_softsysvideo', 'softsysvideo_plugin_key');
        $storedapiurl = get_config('local_softsysvideo', 'softsysvideo_api_url');
        if (!empty($connectionid) && !empty($pluginkey) && !empty($storedapiurl)) {
            $curl = new \curl();
            $curl->setopt([
                'CURLOPT_CONNECTTIMEOUT' => 10,
                'CURLOPT_TIMEOUT' => 15,
            ]);
            $curl->setHeader([
                'Content-Type: application/json',
                'Authorization: Bearer ' . $pluginkey,
            ]);
            $disconnecturl = rtrim($storedapiurl, '/') . '/api/moodle/disconnect';
            $curl->post($disconnecturl, json_encode(['connection_id' => $connectionid]));
        }

        $configkeys = [
            'softsysvideo_api_url',
            'softsysvideo_plugin_key',
            'softsysvideo_shared_secret',
            'softsysvideo_tenant_name',
            'cache_credit_balance',
            'cache_updated_at',
            'softsysvideo_connection_id',
        ];
        foreach ($configkeys as $key) {
            unset_config($key, 'local_softsysvideo');
        }
        redirect(
            new moodle_url('/admin/settings.php', ['section' => 'local_softsysvideo']),
            get_string('disconnected', 'local_softsysvideo'),
            null,
            \core\output\notification::NOTIFY_SUCCESS
        );
    }

    if ($action === 'connect') {
        $apikey = required_param('api_key', PARAM_RAW_TRIMMED);

        if (empty($apikey)) {
            $message = get_string('api_key_required', 'local_softsysvideo');
            $messagetype = 'warning';
        } else {
            $moodleurl = $CFG->wwwroot;
            $connectendpoint = 'https://api.softsysvideo.com/api/moodle/connect';

            $payload = json_encode([
                'api_key' => $apikey,
                'moodle_url' => $moodleurl,
                'label' => 'Moodle: ' . $moodleurl,
            ]);

            $curl = new \curl();
            $curl->setopt([
                'CURLOPT_CONNECTTIMEOUT' => 10,
                'CURLOPT_TIMEOUT' => 15,
            ]);
            $curl->setHeader([
                'Content-Type: application/json',
                'Accept: application/json',
            ]);
            $response = $curl->post($connectendpoint, $payload);
            $info = $curl->get_info();
            $httpcode = (int)($info['http_code'] ?? 0);
            $curlerror = $curl->error;

            if ($httpcode === 0 || !empty($curlerror)) {
                $message = get_string('connection_server_error', 'local_softsysvideo')
                    . ' ' . s($curlerror ?: '');
                $messagetype = 'danger';
            } else {
                $data = json_decode($response, true);

                if (($httpcode !== 200 && $httpcode !== 201) || empty($data['api_url'])) {
                    $message = $data['error'] ?? get_string('connection_error_http', 'local_softsysvideo', $httpcode);
                    $messagetype = 'danger';
                } else {
                    set_config('softsysvideo_api_url', $data['api_url'], 'local_softsysvideo');
                    set_config('softsysvideo_plugin_key', $apikey, 'local_softsysvideo');
                    set_config('softsysvideo_shared_secret', $data['shared_secret'] ?? '', 'local_softsysvideo');
                    set_config('softsysvideo_tenant_name', $data['tenant_name'] ?? '', 'local_softsysvideo');
                    set_config('cache_credit_balance', $data['credit_balance'] ?? null, 'local_softsysvideo');
                    set_config('cache_updated_at', time(), 'local_softsysvideo');
                    set_config('softsysvideo_connection_id', $data['connection_id'] ?? '', 'local_softsysvideo');

                    redirect(
                        new moodle_url('/local/softsysvideo/dashboard.php'),
                        get_string(
                            'connection_success_detail',
                            'local_softsysvideo',
                            s($data['tenant_name'] ?? '')
                        ),
                        null,
                        \core\output\notification::NOTIFY_SUCCESS
                    );
                }
            }
        }
    }
}

echo $OUTPUT->header();
echo html_writer::start_div('container-fluid py-3');
echo local_softsysvideo_render_navigation('connection');
echo html_writer::tag('h2', get_string('connection', 'local_softsysvideo'));
echo html_writer::start_div('', ['style' => 'max-width:700px']);

if ($message) {
    echo html_writer::div(s($message), 'alert alert-' . $messagetype);
}

if ($isconnected) {
    // Connected state card.
    $orglabel = get_string('organization_label', 'local_softsysvideo');
    $orgstrong = html_writer::tag('strong', $orglabel . ':');
    $orgrow = html_writer::tag('p', $orgstrong . ' ' . s($tenantname ?: '—'));
    $apicodehtml = html_writer::tag('code', s($apiurl));
    $apilabel = get_string('api_url_label', 'local_softsysvideo');
    $apistrong = html_writer::tag('strong', $apilabel . ':');
    $apirow = html_writer::tag('p', $apistrong . ' ' . $apicodehtml);
    $confirmstr = get_string('confirm_disconnect', 'local_softsysvideo');
    $disconnectbtn = html_writer::tag(
        'button',
        get_string('disconnect', 'local_softsysvideo'),
        ['type' => 'submit', 'class' => 'btn btn-outline-danger', 'onclick' => "return confirm('" . $confirmstr . "')"]
    );
    $disconnectform = html_writer::tag(
        'form',
        html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]) .
        html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'action', 'value' => 'disconnect']) .
        $disconnectbtn,
        ['method' => 'post', 'action' => '']
    );
    $cardbody = html_writer::div($orgrow . $apirow . $disconnectform, 'card-body');
    $cardheader = html_writer::div(
        get_string('connected', 'local_softsysvideo'),
        'card-header bg-success text-white font-weight-bold'
    );
    echo html_writer::div($cardheader . $cardbody, 'card border-success mb-4');
    echo html_writer::tag('h3', get_string('reconnect', 'local_softsysvideo'));
}

// Connect form — single API key field.
$keyinput = html_writer::tag('label', get_string('api_key_label', 'local_softsysvideo'), [
    'for' => 'ssv-api-key',
    'class' => 'form-label',
]);
$keyinput .= html_writer::empty_tag('input', [
    'type' => 'text',
    'id' => 'ssv-api-key',
    'name' => 'api_key',
    'class' => 'form-control ssv-monospace',
    'required' => 'required',
    'placeholder' => 'sss_live_...',
    'autocomplete' => 'off',
]);
$keyhelp = html_writer::tag('div', get_string('api_key_help', 'local_softsysvideo'), [
    'class' => 'form-text text-muted',
]);
$submitbtn = html_writer::tag('button', get_string('connect', 'local_softsysvideo'), [
    'type' => 'submit',
    'class' => 'btn btn-primary',
]);
$instrp = html_writer::tag('p', get_string('connect_instructions', 'local_softsysvideo'), ['class' => 'text-muted']);
$forminner = $instrp .
    html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]) .
    html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'action', 'value' => 'connect']) .
    html_writer::div($keyinput . $keyhelp, 'mb-3') .
    $submitbtn;
$form = html_writer::tag('form', $forminner, ['method' => 'post', 'action' => '']);
echo html_writer::div(html_writer::div($form, 'card-body'), 'card');

echo html_writer::end_div(); // Close max-width wrapper.
echo html_writer::end_div(); // Close container-fluid.
echo $OUTPUT->footer();
