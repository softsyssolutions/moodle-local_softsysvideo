<?php
/**
 * Moodle Connect — local_softsysvideo
 *
 * Allows Moodle admins to connect their Moodle to SoftSys Video
 * by entering their SoftSys Video account credentials.
 *
 * On success:
 *  - Saves Plugin API Key, API URL, Shared Secret to Moodle config
 *  - Optionally configures mod_bigbluebutton automatically
 *  - Caches tenant name and credit balance for display in settings
 */

require_once('../../config.php');
require_once($CFG->libdir . '/adminlib.php');

require_login();
require_capability('local/softsysvideo:manage', context_system::instance());

$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/local/softsysvideo/connect.php'));
$PAGE->set_title(get_string('setup_wizard', 'local_softsysvideo'));
$PAGE->set_heading(get_string('setup_wizard', 'local_softsysvideo'));

$action = optional_param('action', '', PARAM_ALPHA);

// ── AJAX: connect ─────────────────────────────────────────────────────────────
if ($action === 'connect') {
    header('Content-Type: application/json');
    require_sesskey();

    $email     = required_param('email', PARAM_EMAIL);
    $password  = required_param('password', PARAM_RAW);
    $configurebbb = optional_param('configure_bbb', 0, PARAM_BOOL);

    // Build Moodle URL (origin only, no path)
    $moodleUrl = (new moodle_url('/'))->get_scheme() . '://' . $_SERVER['HTTP_HOST'];

    // Determine API endpoint — use configured URL or default to production
    $apiUrl = get_config('local_softsysvideo', 'softsysvideo_api_url');
    $connectEndpoint = 'https://api.softsysvideo.com/api/moodle/connect';
    // If already configured, derive the connect endpoint from the known base domain
    if (!empty($apiUrl)) {
        $parsed = parse_url($apiUrl);
        $host = $parsed['host'] ?? '';
        // Extract base domain: api-TENANT.DOMAIN -> DOMAIN
        if (preg_match('/^api-[^.]+\.(.+)$/', $host, $m)) {
            $connectEndpoint = $parsed['scheme'] . '://api.' . $m[1] . '/api/moodle/connect';
        }
    }

    // For the Moodle test instance, use a configurable endpoint
    $connectEndpoint = defined('SOFTSYSVIDEO_API_ENDPOINT')
        ? SOFTSYSVIDEO_API_ENDPOINT . '/api/moodle/connect'
        : $connectEndpoint;

    // Actually, use the already-configured API URL base if available,
    // otherwise default to the production one
    // For the test Moodle, we'll pass a custom endpoint via a hidden config
    $overrideEndpoint = get_config('local_softsysvideo', 'softsysvideo_connect_endpoint');
    if (!empty($overrideEndpoint)) {
        $connectEndpoint = $overrideEndpoint;
    }

    // Call POST /api/moodle/connect
    $curl = new curl();
    $curl->setHeader(['Content-Type: application/json', 'Accept: application/json']);
    $response = $curl->post($connectEndpoint, json_encode([
        'email'      => $email,
        'password'   => $password,
        'moodle_url' => $moodleUrl,
        'label'      => 'Moodle: ' . $moodleUrl,
    ]));

    $info = $curl->get_info();
    $httpCode = $info['http_code'] ?? 0;

    if ($httpCode === 0) {
        echo json_encode(['ok' => false, 'error' => 'No se pudo conectar con el servidor de SoftSys Video. Verifica tu conexión a internet.']);
        exit;
    }

    $data = json_decode($response, true);

    if ($httpCode !== 201 || empty($data['plugin_key'])) {
        $errMsg = $data['error'] ?? 'Error de conexión (HTTP ' . $httpCode . ')';
        echo json_encode(['ok' => false, 'error' => $errMsg]);
        exit;
    }

    // ── Save everything to Moodle config ─────────────────────────────────────
    set_config('softsysvideo_api_url',      $data['api_url'],       'local_softsysvideo');
    set_config('softsysvideo_plugin_key',   $data['plugin_key'],    'local_softsysvideo');
    set_config('softsysvideo_shared_secret',$data['shared_secret'], 'local_softsysvideo');
    set_config('softsysvideo_tenant_name',  $data['tenant_name'],   'local_softsysvideo');
    set_config('cache_credit_balance',      $data['credit_balance'],'local_softsysvideo');
    set_config('cache_updated_at',          time(),                  'local_softsysvideo');
    set_config('softsysvideo_connection_id',$data['connection_id'] ?? '', 'local_softsysvideo');

    // ── Auto-configure BigBlueButton if requested ─────────────────────────────
    $bbbConfigured = false;
    if ($configurebbb) {
        require_once($CFG->dirroot . '/local/softsysvideo/classes/bbb_configurator.php');
        $bbb = new \local_softsysvideo\bbb_configurator();
        if ($bbb->is_bbb_installed()) {
            $bbb->configure_bbb($data['api_url'], $data['shared_secret']);
            $bbbConfigured = true;
        }
    }

    echo json_encode([
        'ok'            => true,
        'tenant_name'   => $data['tenant_name'],
        'credit_balance'=> $data['credit_balance'],
        'api_url'       => $data['api_url'],
        'bbb_configured'=> $bbbConfigured,
    ]);
    exit;
}

// ── AJAX: disconnect ──────────────────────────────────────────────────────────
if ($action === 'disconnect') {
    header('Content-Type: application/json');
    require_sesskey();

    // Clear all stored config
    foreach (['softsysvideo_api_url','softsysvideo_plugin_key','softsysvideo_shared_secret',
              'softsysvideo_tenant_name','cache_credit_balance','cache_updated_at',
              'softsysvideo_connection_id'] as $key) {
        unset_config($key, 'local_softsysvideo');
    }

    echo json_encode(['ok' => true]);
    exit;
}

// ── Page render ───────────────────────────────────────────────────────────────
$isConnected   = !empty(get_config('local_softsysvideo', 'softsysvideo_plugin_key'));
$tenantName    = get_config('local_softsysvideo', 'softsysvideo_tenant_name') ?: '';
$creditBalance = get_config('local_softsysvideo', 'cache_credit_balance');
$apiUrl        = get_config('local_softsysvideo', 'softsysvideo_api_url') ?: '';

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_softsysvideo/connect_wizard', [
    'sesskey'        => sesskey(),
    'wwwroot'        => $CFG->wwwroot,
    'is_connected'   => $isConnected,
    'tenant_name'    => htmlspecialchars($tenantName),
    'credit_balance' => $creditBalance !== false ? number_format(floatval($creditBalance), 2) : null,
    'api_url'        => htmlspecialchars($apiUrl),
    'bbb_installed'  => (
        file_exists($CFG->dirroot . '/local/softsysvideo/classes/bbb_configurator.php') &&
        class_exists('core_plugin_manager') &&
        \core_plugin_manager::instance()->get_plugin_info('mod_bigbluebutton') !== null
    ),
]);
echo $OUTPUT->footer();
