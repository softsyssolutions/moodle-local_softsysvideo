<?php
/**
 * Moodle Connect — local_softsysvideo
 *
 * Standard Moodle form-based connect wizard.
 * Avoids AJAX POST because Moodle 4.5 Slim router blocks POST to local plugin PHP files.
 *
 * On submit:
 *  - Calls POST /api/moodle/connect on the SoftSys Video API
 *  - Saves Plugin API Key, API URL, Shared Secret to Moodle config (get_config / set_config)
 *  - Optionally configures mod_bigbluebutton automatically
 */

require_once('../../config.php');
require_once($CFG->libdir . '/adminlib.php');

require_login();
require_capability('local/softsysvideo:manage', context_system::instance());

$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/local/softsysvideo/connect.php'));
$PAGE->set_title(get_string('setup_wizard', 'local_softsysvideo'));
$PAGE->set_heading(get_string('setup_wizard', 'local_softsysvideo'));
$PAGE->set_pagelayout('admin');

$action       = optional_param('action', '', PARAM_ALPHA);
$isConnected  = !empty(get_config('local_softsysvideo', 'softsysvideo_plugin_key'));
$tenantName   = get_config('local_softsysvideo', 'softsysvideo_tenant_name') ?: '';
$creditBalance = get_config('local_softsysvideo', 'cache_credit_balance');
$apiUrl       = get_config('local_softsysvideo', 'softsysvideo_api_url') ?: '';

$message = '';
$messageType = 'info';

// ── Handle form submission ──────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && confirm_sesskey()) {

    if ($action === 'disconnect') {
        foreach ([
            'softsysvideo_api_url', 'softsysvideo_plugin_key', 'softsysvideo_shared_secret',
            'softsysvideo_tenant_name', 'cache_credit_balance', 'cache_updated_at',
            'softsysvideo_connection_id',
        ] as $key) {
            unset_config($key, 'local_softsysvideo');
        }
        redirect(new moodle_url('/admin/settings.php', ['section' => 'local_softsysvideo']),
            get_string('disconnected', 'local_softsysvideo'), null, \core\output\notification::NOTIFY_SUCCESS);
    }

    if ($action === 'connect') {
        $email        = required_param('email', PARAM_EMAIL);
        $password     = required_param('password', PARAM_RAW_TRIMMED);
        $configurebbb = optional_param('configure_bbb', 0, PARAM_BOOL);

        if (empty($email) || empty($password)) {
            $message = 'Por favor ingresa tu email y contraseña.';
            $messageType = 'warning';
        } else {
            // Build Moodle URL (origin only)
            $moodleUrl = (new moodle_url('/'))->get_scheme() . '://' . $_SERVER['HTTP_HOST'];

            // Determine API connect endpoint
            $overrideEndpoint = get_config('local_softsysvideo', 'softsysvideo_connect_endpoint');
            $connectEndpoint  = !empty($overrideEndpoint)
                ? $overrideEndpoint
                : 'https://api.softsysvideo.com/api/moodle/connect';

            // Call POST /api/moodle/connect using PHP curl directly
            $payload = json_encode([
                'email'      => $email,
                'password'   => $password,
                'moodle_url' => $moodleUrl,
                'label'      => 'Moodle: ' . $moodleUrl,
            ]);

            $ch = curl_init($connectEndpoint);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $payload,
                CURLOPT_HTTPHEADER     => [
                    'Content-Type: application/json',
                    'Accept: application/json',
                    'Content-Length: ' . strlen($payload),
                ],
                CURLOPT_TIMEOUT        => 15,
                CURLOPT_FOLLOWLOCATION => true,
            ]);
            $response = curl_exec($ch);
            $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($httpCode === 0 || !empty($curlError)) {
                $message = 'No se pudo conectar con el servidor de SoftSys Video. ' . htmlspecialchars($curlError ?: 'Verifica tu conexión a internet.');
                $messageType = 'danger';
            } else {
                $data = json_decode($response, true);

                if (($httpCode !== 200 && $httpCode !== 201) || empty($data['plugin_key'])) {
                    $message = $data['error'] ?? 'Error de conexión (HTTP ' . $httpCode . ')';
                    $messageType = 'danger';
                } else {
                    // Save to Moodle config (standard plugin config storage)
                    set_config('softsysvideo_api_url',       $data['api_url'],        'local_softsysvideo');
                    set_config('softsysvideo_plugin_key',    $data['plugin_key'],     'local_softsysvideo');
                    set_config('softsysvideo_shared_secret', $data['shared_secret'],  'local_softsysvideo');
                    set_config('softsysvideo_tenant_name',   $data['tenant_name'],    'local_softsysvideo');
                    set_config('cache_credit_balance', $data['credit_balance'] ?? null, 'local_softsysvideo');
                    set_config('cache_updated_at',           time(),                  'local_softsysvideo');
                    set_config('softsysvideo_connection_id', $data['connection_id'] ?? '', 'local_softsysvideo');

                    // Auto-configure BigBlueButton if requested
                    $bbbMsg = '';
                    if ($configurebbb) {
                        require_once($CFG->dirroot . '/local/softsysvideo/classes/bbb_configurator.php');
                        $bbb = new \local_softsysvideo\bbb_configurator();
                        if ($bbb->is_bbb_installed()) {
                            $bbb->configure_bbb($data['api_url'], $data['shared_secret']);
                            $bbbMsg = ' BigBlueButton configurado.';
                        }
                    }

                    redirect(
                        new moodle_url('/admin/settings.php', ['section' => 'local_softsysvideo']),
                        '✅ Conectado correctamente a ' . htmlspecialchars($data['tenant_name'] ?: 'SoftSys Video') . '.' . $bbbMsg,
                        null,
                        \core\output\notification::NOTIFY_SUCCESS
                    );
                }
            }
        }
    }
}

// ── Check if BBB is installed ─────────────────────────────────────────────────
$bbbInstalled = (
    file_exists($CFG->dirroot . '/local/softsysvideo/classes/bbb_configurator.php') &&
    class_exists('core_plugin_manager') &&
    \core_plugin_manager::instance()->get_plugin_info('mod_bigbluebutton') !== null
);

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_softsysvideo/connect_wizard', [
    'sesskey'        => sesskey(),
    'wwwroot'        => $CFG->wwwroot,
    'is_connected'   => $isConnected,
    'tenant_name'    => htmlspecialchars($tenantName),
    'credit_balance' => $creditBalance !== false ? number_format(floatval($creditBalance), 2) : null,
    'api_url'        => htmlspecialchars($apiUrl),
    'bbb_installed'  => $bbbInstalled,
    'message'        => $message,
    'message_type'   => $messageType,
]);
echo $OUTPUT->footer();
