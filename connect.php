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
 * Form-based connect wizard. Avoids AJAX POST because Moodle 4.5 Slim router
 * blocks POST to local plugin PHP files.
 *
 * On submit:
 *  - Calls POST /api/moodle/connect on the SoftSys Video API
 *  - Saves Plugin API Key, API URL and tenant info to Moodle config
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

$action      = optional_param('action', '', PARAM_ALPHA);
$isConnected = !empty(get_config('local_softsysvideo', 'softsysvideo_plugin_key'));
$tenantName  = get_config('local_softsysvideo', 'softsysvideo_tenant_name') ?: '';
$apiUrl      = get_config('local_softsysvideo', 'softsysvideo_api_url') ?: '';

$message     = '';
$messageType = 'info';

// Handle form submission.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && confirm_sesskey()) {

    if ($action === 'disconnect') {
        foreach ([
            'softsysvideo_api_url', 'softsysvideo_plugin_key', 'softsysvideo_shared_secret',
            'softsysvideo_tenant_name', 'cache_credit_balance', 'cache_updated_at',
            'softsysvideo_connection_id',
        ] as $key) {
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
        $email    = required_param('email', PARAM_EMAIL);
        $password = required_param('password', PARAM_RAW_TRIMMED);

        if (empty($email) || empty($password)) {
            $message     = get_string('email_password_required', 'local_softsysvideo');
            $messageType = 'warning';
        } else {
            $moodleUrl = (new moodle_url('/'))->get_scheme() . '://' . $_SERVER['HTTP_HOST'];

            $overrideEndpoint = get_config('local_softsysvideo', 'softsysvideo_connect_endpoint');
            $connectEndpoint  = !empty($overrideEndpoint)
                ? $overrideEndpoint
                : 'https://api.softsysvideo.com/api/moodle/connect';

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
            $response  = curl_exec($ch);
            $httpCode  = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($httpCode === 0 || !empty($curlError)) {
                $message     = get_string('connection_server_error', 'local_softsysvideo') . ' ' . htmlspecialchars($curlError ?: '');
                $messageType = 'danger';
            } else {
                $data = json_decode($response, true);

                if (($httpCode !== 200 && $httpCode !== 201) || empty($data['plugin_key'])) {
                    $message     = $data['error'] ?? 'Error de conexión (HTTP ' . $httpCode . ')';
                    $messageType = 'danger';
                } else {
                    set_config('softsysvideo_api_url',       $data['api_url'],                  'local_softsysvideo');
                    set_config('softsysvideo_plugin_key',    $data['plugin_key'],               'local_softsysvideo');
                    set_config('softsysvideo_shared_secret', $data['shared_secret'] ?? '',      'local_softsysvideo');
                    set_config('softsysvideo_tenant_name',   $data['tenant_name'] ?? '',        'local_softsysvideo');
                    set_config('cache_credit_balance',       $data['credit_balance'] ?? null,   'local_softsysvideo');
                    set_config('cache_updated_at',           time(),                            'local_softsysvideo');
                    set_config('softsysvideo_connection_id', $data['connection_id'] ?? '',      'local_softsysvideo');

                    redirect(
                        new moodle_url('/local/softsysvideo/dashboard.php'),
                        get_string('connection_success_detail', 'local_softsysvideo', htmlspecialchars($data['tenant_name'] ?? '')),
                        null,
                        \core\output\notification::NOTIFY_SUCCESS
                    );
                }
            }
        }
    }
}

echo $OUTPUT->header();
?>

<div class="container-fluid py-3" style="max-width:700px">

  <!-- Plugin nav -->
  <div class="d-flex gap-2 mb-3 flex-wrap">
    <a href="<?php echo $CFG->wwwroot; ?>/local/softsysvideo/dashboard.php" class="btn btn-sm btn-outline-primary">📊 Dashboard</a>
    <a href="<?php echo $CFG->wwwroot; ?>/local/softsysvideo/recordings.php" class="btn btn-sm btn-outline-secondary">🎬 Grabaciones</a>
    <a href="<?php echo $CFG->wwwroot; ?>/local/softsysvideo/meetings.php" class="btn btn-sm btn-outline-secondary">📅 Reuniones</a>
    <a href="<?php echo $CFG->wwwroot; ?>/local/softsysvideo/connect.php" class="btn btn-sm btn-secondary">🔌 Conexión</a>
    <a href="<?php echo $CFG->wwwroot; ?>/local/softsysvideo/support.php" class="btn btn-sm btn-outline-danger">🆘 Soporte</a>
  </div>

  <h2>🔌 <?php echo get_string('connection', 'local_softsysvideo'); ?></h2>

  <?php if ($message): ?>
    <div class="alert alert-<?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></div>
  <?php endif; ?>

  <?php if ($isConnected): ?>
    <!-- Connected state -->
    <div class="card border-success mb-4">
      <div class="card-header bg-success text-white fw-bold">🟢 Conectado</div>
      <div class="card-body">
        <p><strong>Organización:</strong> <?php echo htmlspecialchars($tenantName ?: '—'); ?></p>
        <p><strong>API URL:</strong> <code><?php echo htmlspecialchars($apiUrl); ?></code></p>
        <form method="post" action="">
          <input type="hidden" name="sesskey" value="<?php echo sesskey(); ?>">
          <input type="hidden" name="action" value="disconnect">
          <button type="submit" class="btn btn-outline-danger"
            onclick="return confirm('<?php echo get_string('confirm_disconnect', 'local_softsysvideo'); ?>')">
            🔌 Desconectar
          </button>
        </form>
      </div>
    </div>
    <h3>Reconectar con otra cuenta</h3>
  <?php endif; ?>

  <!-- Connect form -->
  <div class="card">
    <div class="card-body">
      <p class="text-muted"><?php echo get_string('connect_instructions', 'local_softsysvideo'); ?></p>
      <form method="post" action="">
        <input type="hidden" name="sesskey" value="<?php echo sesskey(); ?>">
        <input type="hidden" name="action" value="connect">
        <div class="mb-3">
          <label for="ssv-email" class="form-label">Email</label>
          <input type="email" id="ssv-email" name="email" class="form-control" required placeholder="tu@empresa.com">
        </div>
        <div class="mb-3">
          <label for="ssv-password" class="form-label">Contraseña</label>
          <input type="password" id="ssv-password" name="password" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-primary">🔌 Conectar</button>
      </form>
    </div>
  </div>

</div>

<?php
echo $OUTPUT->footer();
