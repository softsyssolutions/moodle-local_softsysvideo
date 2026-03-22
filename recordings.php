<?php
require_once('../../config.php');
require_once($CFG->libdir . '/adminlib.php');

require_login();
require_capability('local/softsysvideo:manage', context_system::instance());

$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/local/softsysvideo/recordings.php'));
$PAGE->set_title(get_string('recordings', 'local_softsysvideo'));
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
    <a href="<?php echo $CFG->wwwroot; ?>/local/softsysvideo/recordings.php" class="btn btn-sm btn-secondary">🎬 Grabaciones</a>
    <a href="<?php echo $CFG->wwwroot; ?>/local/softsysvideo/meetings.php" class="btn btn-sm btn-outline-secondary">📅 Reuniones</a>
    <a href="<?php echo $CFG->wwwroot; ?>/local/softsysvideo/connect.php" class="btn btn-sm btn-outline-secondary">🔌 Conexión</a>
    <a href="<?php echo $CFG->wwwroot; ?>/local/softsysvideo/support.php" class="btn btn-sm btn-outline-danger">🆘 Soporte</a>
  </div>

  <h2>🎬 Grabaciones</h2>

  <?php if (!$isConnected): ?>
    <div class="alert alert-warning">
      ⚠️ No conectado. <a href="<?php echo $CFG->wwwroot; ?>/local/softsysvideo/connect.php" class="alert-link">Conectar con SoftSys Video →</a>
    </div>
  <?php else: ?>

    <div id="ssv-recordings-loading" class="text-muted">Cargando grabaciones...</div>
    <div id="ssv-recordings-error" class="alert alert-danger d-none">Error cargando grabaciones.</div>

    <div id="ssv-recordings-container" class="d-none">
      <div class="table-responsive">
        <table class="table table-striped table-hover">
          <thead class="table-dark">
            <tr>
              <th>Nombre</th>
              <th>Reunión</th>
              <th>Fecha</th>
              <th>Duración</th>
              <th>Tamaño</th>
              <th>Acción</th>
            </tr>
          </thead>
          <tbody id="ssv-recordings-tbody">
          </tbody>
        </table>
      </div>
      <p class="text-muted small" id="ssv-recordings-count"></p>
    </div>

  <?php endif; ?>
</div>

<script>
(function() {
    var API_URL    = <?php echo json_encode($apiUrl); ?>;
    var PLUGIN_KEY = <?php echo json_encode($pluginKey); ?>;
    if (!PLUGIN_KEY) return;

    fetch(API_URL + '/api/moodle/recordings', {
        headers: { 'Authorization': 'Bearer ' + PLUGIN_KEY }
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        document.getElementById('ssv-recordings-loading').classList.add('d-none');
        var recordings = data.recordings || data || [];
        if (!Array.isArray(recordings)) recordings = [];

        var tbody = document.getElementById('ssv-recordings-tbody');
        if (recordings.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">No hay grabaciones disponibles.</td></tr>';
        } else {
            recordings.forEach(function(rec) {
                var playBtn = rec.url
                    ? '<a href="' + rec.url + '" target="_blank" class="btn btn-sm btn-success">▶ Reproducir</a>'
                    : '<span class="text-muted">—</span>';
                var row = '<tr>'
                    + '<td>' + (rec.name || '—') + '</td>'
                    + '<td>' + (rec.meeting_name || rec.meeting || '—') + '</td>'
                    + '<td>' + (rec.date || rec.created_at || '—') + '</td>'
                    + '<td>' + (rec.duration || '—') + '</td>'
                    + '<td>' + (rec.size || '—') + '</td>'
                    + '<td>' + playBtn + '</td>'
                    + '</tr>';
                tbody.innerHTML += row;
            });
        }
        document.getElementById('ssv-recordings-count').textContent = recordings.length + ' grabación(es) encontrada(s).';
        document.getElementById('ssv-recordings-container').classList.remove('d-none');
    })
    .catch(function() {
        document.getElementById('ssv-recordings-loading').classList.add('d-none');
        document.getElementById('ssv-recordings-error').classList.remove('d-none');
    });
})();
</script>

<?php
echo $OUTPUT->footer();
