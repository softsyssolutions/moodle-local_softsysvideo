<?php
defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_softsysvideo', get_string('pluginname', 'local_softsysvideo'));

    if ($ADMIN->fulltree) {

        // ── SECCIÓN 1: Estado de conexión ─────────────────────────────────────
        $apiurl    = get_config('local_softsysvideo', 'softsysvideo_api_url');
        $pluginkey = get_config('local_softsysvideo', 'softsysvideo_plugin_key');
        $isconnected = !empty($apiurl) && !empty($pluginkey);

        if ($isconnected) {
            // Intentar obtener créditos y uso del API
            $creditshtml = '';
            $usagehtml   = '';
            $bbbstatus   = '';

            try {
                require_once($CFG->dirroot . '/local/softsysvideo/classes/api_client.php');
                $client  = new \local_softsysvideo\api_client($apiurl, $pluginkey);
                $credits = $client->get_credits();
                $usage   = $client->get_usage();

                if (!empty($credits)) {
                    $balance  = number_format(floatval($credits['balance'] ?? 0), 2);
                    $tname    = htmlspecialchars($credits['tenant_name'] ?? '');
                    $creditshtml = html_writer::div(
                        html_writer::tag('strong', get_string('credits_balance', 'local_softsysvideo') . ': ') .
                        html_writer::tag('span', '$' . $balance . ' USD',
                            ['class' => floatval($credits['balance'] ?? 0) < 5 ? 'text-danger fw-bold' : 'text-success fw-bold']) .
                        ($tname ? html_writer::tag('span', ' · ' . $tname, ['class' => 'text-muted ms-2']) : ''),
                        'alert alert-info py-2 px-3 mb-1'
                    );
                }

                if (!empty($usage)) {
                    $meetings  = $usage['meetings']['total'] ?? 0;
                    $active    = $usage['meetings']['active_now'] ?? 0;
                    $minutes   = $usage['meetings']['total_minutes'] ?? 0;
                    $cost      = number_format(floatval($usage['cost_usd']['total'] ?? 0), 2);
                    $period    = $usage['period_days'] ?? 30;
                    $usagehtml = html_writer::div(
                        html_writer::tag('strong', get_string('monthly_usage', 'local_softsysvideo') . ' (' . $period . ' días): ') .
                        "$meetings reuniones · {$minutes} min · $active activas · \${$cost} USD consumidos",
                        'alert alert-secondary py-2 px-3 mb-1'
                    );
                }
            } catch (\Exception $e) {
                $creditshtml = html_writer::div(
                    get_string('connection_failed', 'local_softsysvideo') . ': ' . htmlspecialchars($e->getMessage()),
                    'alert alert-warning py-2 px-3 mb-1'
                );
            }

            // Estado de configuración de BBB
            require_once($CFG->dirroot . '/local/softsysvideo/classes/bbb_configurator.php');
            $configurator = new \local_softsysvideo\bbb_configurator();
            if ($configurator->is_bbb_installed()) {
                $bbbcfg = $configurator->get_current_bbb_config();
                $pointingToUs = $configurator->is_configured_for_softsysvideo($apiurl);
                $bbbstatus = html_writer::div(
                    html_writer::tag('strong', 'BigBlueButton: ') .
                    ($pointingToUs
                        ? html_writer::tag('span', '✅ Configurado para SoftSys Video', ['class' => 'text-success'])
                        : html_writer::tag('span', '⚠️ Apuntando a otro servidor: ' . htmlspecialchars($bbbcfg['server_url']), ['class' => 'text-warning'])),
                    'alert alert-light border py-2 px-3 mb-1'
                );
            } else {
                $bbbstatus = html_writer::div(
                    '⚠️ ' . get_string('bbb_not_installed', 'local_softsysvideo'),
                    'alert alert-warning py-2 px-3 mb-1'
                );
            }

            $statusblock = html_writer::div(
                html_writer::tag('h5', '🟢 ' . get_string('connected', 'local_softsysvideo') .
                    html_writer::tag('small', ' · ' . htmlspecialchars($apiurl), ['class' => 'text-muted fs-6 ms-2']), ['class' => 'mb-2']) .
                $creditshtml .
                $usagehtml .
                $bbbstatus,
                'p-3 mb-3 bg-light rounded border'
            );
        } else {
            $statusblock = html_writer::div(
                '🔴 ' . get_string('not_connected', 'local_softsysvideo') .
                ' — ' . html_writer::link(
                    new moodle_url('/local/softsysvideo/setup.php'),
                    get_string('setup_wizard', 'local_softsysvideo'),
                    ['class' => 'btn btn-sm btn-primary ms-2']
                ),
                'alert alert-secondary'
            );
        }

        $settings->add(new admin_setting_heading(
            'local_softsysvideo/connectionstatus',
            get_string('connected', 'local_softsysvideo'),
            $statusblock
        ));

        // ── SECCIÓN 2: Credenciales ───────────────────────────────────────────
        $settings->add(new admin_setting_heading(
            'local_softsysvideo/credentialshdr',
            'Credenciales de API',
            get_string('api_url_help', 'local_softsysvideo')
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
            'Secreto compartido BBB. Se usa para configurar mod_bigbluebutton.',
            ''
        ));

        // ── SECCIÓN 3: Setup Wizard ───────────────────────────────────────────
        $setupbtn = html_writer::div(
            html_writer::link(
                new moodle_url('/local/softsysvideo/setup.php'),
                '⚙️ ' . get_string('setup_wizard', 'local_softsysvideo'),
                ['class' => 'btn btn-primary me-2']
            ) .
            html_writer::link(
                new moodle_url('/local/softsysvideo/setup.php', ['action' => 'configure']),
                '🔗 ' . get_string('configure_bbb', 'local_softsysvideo'),
                ['class' => 'btn btn-outline-secondary']
            ),
            'mt-2'
        );

        $settings->add(new admin_setting_heading(
            'local_softsysvideo/setupwizardhdr',
            get_string('setup_wizard', 'local_softsysvideo'),
            get_string('setup_wizard', 'local_softsysvideo') . ': conecta y configura BigBlueButton automáticamente.' .
            $setupbtn
        ));

        // ── SECCIÓN 4: Soporte integrado ──────────────────────────────────────
        $supportbtn = html_writer::div(
            html_writer::link(
                new moodle_url('/local/softsysvideo/support.php'),
                '🆘 Reportar problema a SoftSys',
                ['class' => 'btn btn-outline-danger btn-sm']
            ),
            'mt-1'
        );

        $settings->add(new admin_setting_heading(
            'local_softsysvideo/supporthdr',
            'Soporte',
            'Reporta problemas directamente al equipo de SoftSys Video.' . $supportbtn
        ));
    }

    $ADMIN->add('localplugins', $settings);
}
