<?php
defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_softsysvideo', get_string('pluginname', 'local_softsysvideo'));

    if ($ADMIN->fulltree) {

        // ── ESTADO DE CONEXIÓN (lee config local — sin llamadas HTTP) ─────────
        $apiurl      = get_config('local_softsysvideo', 'softsysvideo_api_url');
        $pluginkey   = get_config('local_softsysvideo', 'softsysvideo_plugin_key');
        $tenantname  = get_config('local_softsysvideo', 'softsysvideo_tenant_name');
        $balance     = get_config('local_softsysvideo', 'cache_credit_balance');
        $updatedat   = get_config('local_softsysvideo', 'cache_updated_at');
        $isconnected = !empty($apiurl) && !empty($pluginkey);

        $wwwroot     = $CFG->wwwroot;
        $connecturl  = $wwwroot . '/local/softsysvideo/connect.php';
        $supporturl  = $wwwroot . '/local/softsysvideo/support.php';

        if ($isconnected) {
            // BBB status (from local config, no HTTP)
            $bbburl    = get_config('bigbluebutton', 'server_url') ?: '';
            $bbbparsed = $bbburl ? parse_url($bbburl, PHP_URL_HOST) : '';
            $apiparsed = $apiurl ? parse_url($apiurl, PHP_URL_HOST) : '';
            $bbbpoints = !empty($bbbparsed) && !empty($apiparsed) && $bbbparsed === $apiparsed;

            $balstr  = $balance !== false
                ? '$' . number_format(floatval($balance), 2) . ' USD'
                : '—';
            $baclass = ($balance !== false && floatval($balance) < 5) ? 'text-danger fw-bold' : 'text-success fw-bold';

            $synctime = $updatedat ? userdate((int)$updatedat, '%d/%m %H:%M') : 'Nunca';

            $bbbrow = '';
            if (!empty($bbburl)) {
                $bbbstatus = $bbbpoints
                    ? '<span class="text-success">✅ Configurado para SoftSys Video</span>'
                    : '⚠️ Apuntando a <code>' . htmlspecialchars($bbburl) . '</code>'
                    . ' — <a href="' . $connecturl . '">Reconfigurar</a>';
                $bbbrow = '<tr><th scope="row">BigBlueButton</th><td>' . $bbbstatus . '</td></tr>';
            }

            $connhtml = '
<div class="card border-success mb-3">
  <div class="card-header bg-success text-white fw-bold">🟢 Conectado a SoftSys Video</div>
  <div class="card-body p-3">
    <table class="table table-sm table-borderless mb-2">
      <tbody>
        <tr><th scope="row" width="160">Organización</th><td><strong>' . htmlspecialchars($tenantname ?: '—') . '</strong></td></tr>
        <tr><th scope="row">Saldo actual</th><td><span class="' . $baclass . '">' . $balstr . '</span></td></tr>
        <tr><th scope="row">API URL</th><td><code class="small">' . htmlspecialchars($apiurl) . '</code></td></tr>
        ' . $bbbrow . '
        <tr><th scope="row">Última sync</th><td class="text-muted small">' . $synctime . '</td></tr>
      </tbody>
    </table>
    <div class="d-flex flex-wrap gap-2">
      <a href="' . $connecturl . '" class="btn btn-sm btn-outline-primary">🔄 Actualizar / Reconectar</a>
      <a href="' . $wwwroot . '/local/softsysvideo/setup.php" class="btn btn-sm btn-outline-secondary">⚙️ Setup Wizard</a>
      <a href="' . $supporturl . '" class="btn btn-sm btn-outline-danger">🆘 Soporte</a>
    </div>
  </div>
</div>';
        } else {
            $connhtml = '
<div class="card border-secondary mb-3">
  <div class="card-body d-flex align-items-center gap-3 p-3">
    <span class="fs-4">🔴</span>
    <div>
      <strong>' . get_string('not_connected', 'local_softsysvideo') . '</strong>
      <p class="mb-2 text-muted small">Conecta este Moodle con tu cuenta de SoftSys Video para habilitar videoconferencias.</p>
      <a href="' . $connecturl . '" class="btn btn-primary">🔌 Conectar con SoftSys Video</a>
    </div>
  </div>
</div>';
        }

        $settings->add(new admin_setting_heading(
            'local_softsysvideo/connectionstatus',
            'Estado de conexión',
            $connhtml
        ));

        // ── CONFIGURACIÓN AVANZADA (solo si ya conectado) ─────────────────────
        if ($isconnected) {
            $settings->add(new admin_setting_heading(
                'local_softsysvideo/advancedhdr',
                'Configuración avanzada',
                'Estos valores se configuran automáticamente al conectar. Edítalos solo si es necesario.'
            ));

            $settings->add(new admin_setting_configtext(
                'local_softsysvideo/softsysvideo_api_url',
                get_string('api_url', 'local_softsysvideo'),
                get_string('api_url_help', 'local_softsysvideo'),
                '',
                PARAM_URL
            ));

            $settings->add(new admin_setting_configpasswordunmask(
                'local_softsysvideo/softsysvideo_plugin_key',
                get_string('plugin_key', 'local_softsysvideo'),
                get_string('plugin_key_help', 'local_softsysvideo'),
                ''
            ));

            $settings->add(new admin_setting_configpasswordunmask(
                'local_softsysvideo/softsysvideo_shared_secret',
                get_string('shared_secret', 'local_softsysvideo'),
                'Secreto compartido BBB-compatible.',
                ''
            ));
        } else {
            // If not connected, show the fields for manual entry as fallback
            $settings->add(new admin_setting_heading(
                'local_softsysvideo/manualfallbackhdr',
                'Configuración manual (opcional)',
                'Si tienes las credenciales, puedes ingresarlas directamente. '
                . 'O usa el botón "Conectar" de arriba para el flujo automático.'
            ));

            $settings->add(new admin_setting_configtext(
                'local_softsysvideo/softsysvideo_api_url',
                get_string('api_url', 'local_softsysvideo'),
                get_string('api_url_help', 'local_softsysvideo'),
                '',
                PARAM_URL
            ));

            $settings->add(new admin_setting_configpasswordunmask(
                'local_softsysvideo/softsysvideo_plugin_key',
                get_string('plugin_key', 'local_softsysvideo'),
                get_string('plugin_key_help', 'local_softsysvideo'),
                ''
            ));

            $settings->add(new admin_setting_configpasswordunmask(
                'local_softsysvideo/softsysvideo_shared_secret',
                get_string('shared_secret', 'local_softsysvideo'),
                '',
                ''
            ));
        }

        // ── ENDPOINT DE CONEXIÓN (configurable para dev/staging) ──────────────
        $settings->add(new admin_setting_configtext(
            'local_softsysvideo/softsysvideo_connect_endpoint',
            'Endpoint de conexión (avanzado)',
            'URL base del API de SoftSys Video. Dejar vacío para usar producción (https://api.softsysvideo.com).',
            '',
            PARAM_URL
        ));
    }

    $ADMIN->add('localplugins', $settings);
}
