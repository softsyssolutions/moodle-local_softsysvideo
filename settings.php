<?php
defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_softsysvideo', get_string('pluginname', 'local_softsysvideo'));

    if ($ADMIN->fulltree) {

        // ── SECCIÓN 1: Estado de conexión (sin llamadas HTTP — solo config local) ──
        $apiurl      = get_config('local_softsysvideo', 'softsysvideo_api_url');
        $pluginkey   = get_config('local_softsysvideo', 'softsysvideo_plugin_key');
        $isconnected = !empty($apiurl) && !empty($pluginkey);

        // Leer caché de créditos/uso si existen
        $cachedcredits = get_config('local_softsysvideo', 'cache_credit_balance');
        $cachedtenant  = get_config('local_softsysvideo', 'cache_tenant_name');
        $cachedusage   = get_config('local_softsysvideo', 'cache_usage_summary');
        $cachedat      = get_config('local_softsysvideo', 'cache_updated_at');

        $wwwroot = $CFG->wwwroot;

        if ($isconnected) {
            $connhtml = '<div class="p-3 mb-3 bg-light rounded border">';
            $connhtml .= '<h5 class="mb-2">🟢 ' . get_string('connected', 'local_softsysvideo')
                . ' <small class="text-muted fs-6 ms-2">' . htmlspecialchars($apiurl) . '</small></h5>';

            // Mostrar datos cacheados si existen
            if ($cachedcredits !== false) {
                $balance = number_format(floatval($cachedcredits), 2);
                $alertcl = floatval($cachedcredits) < 5 ? 'alert-danger' : 'alert-success';
                $connhtml .= '<div class="alert ' . $alertcl . ' py-2 px-3 mb-1">'
                    . '<strong>💰 ' . get_string('credits_balance', 'local_softsysvideo') . ':</strong> $'
                    . $balance . ' USD'
                    . ($cachedtenant ? ' <span class="text-muted">· ' . htmlspecialchars($cachedtenant) . '</span>' : '')
                    . '</div>';
            }
            if ($cachedusage) {
                $connhtml .= '<div class="alert alert-secondary py-2 px-3 mb-1">'
                    . '<strong>📊 ' . get_string('monthly_usage', 'local_softsysvideo') . ':</strong> '
                    . htmlspecialchars($cachedusage) . '</div>';
            }

            // Estado BBB desde config local (sin HTTP)
            $bbburl = get_config('bigbluebutton', 'server_url');
            if ($bbburl) {
                $pointing = !empty($apiurl) && (strpos($bbburl, parse_url($apiurl, PHP_URL_HOST)) !== false);
                $connhtml .= '<div class="alert alert-light border py-2 px-3 mb-1">'
                    . '🔗 <strong>BigBlueButton:</strong> '
                    . ($pointing
                        ? '<span class="text-success">✅ Configurado para SoftSys Video</span>'
                        : '⚠️ Apuntando a: ' . htmlspecialchars($bbburl)
                        . ' — <a href="' . $wwwroot . '/local/softsysvideo/setup.php">Configurar ahora</a>')
                    . '</div>';
            }

            $connhtml .= '<div class="mt-2">'
                . '<a href="' . $wwwroot . '/local/softsysvideo/setup.php" class="btn btn-sm btn-outline-primary">'
                . '🔄 Actualizar datos</a>'
                . ($cachedat ? '<small class="text-muted ms-2">Última sync: ' . userdate($cachedat, '%d/%m %H:%M') . '</small>' : '')
                . '</div>';

            $connhtml .= '</div>';
        } else {
            $connhtml = '<div class="alert alert-secondary">'
                . '🔴 ' . get_string('not_connected', 'local_softsysvideo')
                . ' — <a href="' . $wwwroot . '/local/softsysvideo/setup.php" class="btn btn-sm btn-primary ms-2">'
                . '⚙️ ' . get_string('setup_wizard', 'local_softsysvideo') . '</a>'
                . '</div>';
        }

        $settings->add(new admin_setting_heading(
            'local_softsysvideo/connectionstatus',
            'Estado de conexión',
            $connhtml
        ));

        // ── SECCIÓN 2: Credenciales ───────────────────────────────────────────
        $settings->add(new admin_setting_heading(
            'local_softsysvideo/credentialshdr',
            'Credenciales de API',
            'Genera el Plugin API Key en <a href="https://app.softsysvideo.com" target="_blank">app.softsysvideo.com</a>'
            . ' → Configuración → Integración Moodle.'
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
            'Secreto compartido BBB-compatible para configurar mod_bigbluebutton.',
            ''
        ));

        // ── SECCIÓN 3: Setup Wizard & Acciones ───────────────────────────────
        $actionbtns = '<div class="d-flex flex-wrap gap-2 mt-2">'
            . '<a href="' . $wwwroot . '/local/softsysvideo/setup.php" class="btn btn-primary">'
            . '⚙️ ' . get_string('setup_wizard', 'local_softsysvideo') . '</a>'
            . '<a href="' . $wwwroot . '/local/softsysvideo/setup.php?action=configure&sesskey=' . sesskey() . '" class="btn btn-outline-secondary">'
            . '🔗 ' . get_string('configure_bbb', 'local_softsysvideo') . '</a>'
            . '<a href="' . $wwwroot . '/local/softsysvideo/support.php" class="btn btn-outline-danger">'
            . '🆘 Reportar problema</a>'
            . '</div>';

        $settings->add(new admin_setting_heading(
            'local_softsysvideo/actionshdr',
            'Acciones',
            'Configura la integración, actualiza credenciales y reporta problemas.' . $actionbtns
        ));
    }

    $ADMIN->add('localplugins', $settings);
}
