<?php
require_once('../../config.php');
require_once($CFG->libdir . '/adminlib.php');

require_login();
require_capability('local/softsysvideo:manage', context_system::instance());

$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/local/softsysvideo/meetings.php'));
$PAGE->set_title(get_string('meetings', 'local_softsysvideo'));
$PAGE->set_heading(get_string('pluginname', 'local_softsysvideo'));
$PAGE->set_pagelayout('admin');

$isConnected = !empty(get_config('local_softsysvideo', 'softsysvideo_plugin_key'));
$apiUrl      = get_config('local_softsysvideo', 'softsysvideo_api_url') ?: 'https://api.softsysvideo.com';
$pluginKey   = get_config('local_softsysvideo', 'softsysvideo_plugin_key') ?: '';

echo $OUTPUT->header();
?>

<div class="container-fluid py-3">

  <!-- Plugin nav -->
  <div class="d-flex gap-2 mb-3 flex-wrap">
    <a href="<?php echo $CFG->wwwroot; ?>/local/softsysvideo/dashboard.php" class="btn btn-sm btn-outline-primary">📊 Dashboard</a>
    <a href="<?php echo $CFG->wwwroot; ?>/local/softsysvideo/recordings.php" class="btn btn-sm btn-outline-secondary">🎬 Grabaciones</a>
    <a href="<?php echo $CFG->wwwroot; ?>/local/softsysvideo/meetings.php" class="btn btn-sm btn-secondary">📅 Reuniones</a>
    <a href="<?php echo $CFG->wwwroot; ?>/local/softsysvideo/connect.php" class="btn btn-sm btn-outline-secondary">🔌 Conexión</a>
    <a href="<?php echo $CFG->wwwroot; ?>/local/softsysvideo/support.php" class="btn btn-sm btn-outline-danger">🆘 Soporte</a>
  </div>

  <h2>📅 Reuniones recientes</h2>

  <?php if (!$isConnected): ?>
    <div class="alert alert-warning">
      ⚠️ No conectado. <a href="<?php echo $CFG->wwwroot; ?>/local/softsysvideo/connect.php" class="alert-link">Conectar con SoftSys Video →</a>
    </div>
  <?php else: ?>

    <div id="ssv-meetings-loading" class="text-muted">Cargando reuniones...</div>
    <div id="ssv-meetings-error" class="alert alert-danger d-none">Error cargando reuniones.</div>

    <div id="ssv-meetings-container" class="d-none">
      <div class="table-responsive">
        <table class="table table-striped table-hover">
          <thead class="table-dark">
            <tr>
              <th>Nombre</th>
              <th>Fecha inicio</th>
              <th>Duración</th>
              <th>Participantes</th>
            </tr>
          </thead>
          <tbody id="ssv-meetings-tbody">
          </tbody>
        </table>
      </div>
      <p class="text-muted small" id="ssv-meetings-count"></p>
    </div>

  <?php endif; ?>
</div>

<script>
(function() {
    var API_URL    = <?php echo json_encode($apiUrl); ?>;
    var PLUGIN_KEY = <?php echo json_encode($pluginKey); ?>;
    if (!PLUGIN_KEY) return;

    fetch(API_URL + '/api/moodle/meetings', {
        headers: { 'Authorization': 'Bearer ' + PLUGIN_KEY }
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        document.getElementById('ssv-meetings-loading').classList.add('d-none');
        var meetings = data.meetings || data || [];
        if (!Array.isArray(meetings)) meetings = [];

        var tbody = document.getElementById('ssv-meetings-tbody');
        if (meetings.length === 0) {
            tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted">No hay reuniones disponibles.</td></tr>';
        } else {
            meetings.forEach(function(m) {
                var row = '<tr>'
                    + '<td>' + (m.name || '—') + '</td>'
                    + '<td>' + (m.start_date || m.started_at || m.created_at || '—') + '</td>'
                    + '<td>' + (m.duration || '—') + '</td>'
                    + '<td>' + (m.participants !== undefined ? m.participants : '—') + '</td>'
                    + '</tr>';
                tbody.innerHTML += row;
            });
        }
        document.getElementById('ssv-meetings-count').textContent = meetings.length + ' reunión(es) encontrada(s).';
        document.getElementById('ssv-meetings-container').classList.remove('d-none');
    })
    .catch(function() {
        document.getElementById('ssv-meetings-loading').classList.add('d-none');
        document.getElementById('ssv-meetings-error').classList.remove('d-none');
    });
})();
</script>

<?php
echo $OUTPUT->footer();
