<?php
require_once('../../config.php');
require_once($CFG->libdir . '/adminlib.php');

require_login();
require_capability('local/softsysvideo:manage', context_system::instance());

$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/local/softsysvideo/dashboard.php'));
$PAGE->set_title(get_string('dashboard', 'local_softsysvideo'));
$PAGE->set_heading(get_string('pluginname', 'local_softsysvideo'));
$PAGE->set_pagelayout('admin');

$isConnected = !empty(get_config('local_softsysvideo', 'softsysvideo_plugin_key'));
$apiUrl      = get_config('local_softsysvideo', 'softsysvideo_api_url') ?: 'https://api.softsysvideo.com';
$pluginKey   = get_config('local_softsysvideo', 'softsysvideo_plugin_key') ?: '';
$tenantName  = get_config('local_softsysvideo', 'softsysvideo_tenant_name') ?: 'SoftSys Video';

echo $OUTPUT->header();
?>

<div class="container-fluid py-3">

  <!-- Plugin nav -->
  <div class="d-flex gap-2 mb-3 flex-wrap">
    <a href="<?php echo $CFG->wwwroot; ?>/local/softsysvideo/dashboard.php" class="btn btn-sm btn-primary">📊 Dashboard</a>
    <a href="<?php echo $CFG->wwwroot; ?>/local/softsysvideo/recordings.php" class="btn btn-sm btn-outline-secondary">🎬 Grabaciones</a>
    <a href="<?php echo $CFG->wwwroot; ?>/local/softsysvideo/meetings.php" class="btn btn-sm btn-outline-secondary">📅 Reuniones</a>
    <a href="<?php echo $CFG->wwwroot; ?>/local/softsysvideo/connect.php" class="btn btn-sm btn-outline-secondary">🔌 Conexión</a>
    <a href="<?php echo $CFG->wwwroot; ?>/local/softsysvideo/support.php" class="btn btn-sm btn-outline-danger">🆘 Soporte</a>
  </div>

  <?php if (!$isConnected): ?>
    <div class="alert alert-warning">
      ⚠️ No conectado. <a href="<?php echo $CFG->wwwroot; ?>/local/softsysvideo/connect.php" class="alert-link">Conectar con SoftSys Video →</a>
    </div>
  <?php else: ?>

    <!-- Header -->
    <div class="d-flex align-items-center gap-3 mb-4">
      <div>
        <h2 class="mb-0">📊 Dashboard</h2>
        <span class="badge bg-success">🟢 Conectado — <?php echo htmlspecialchars($tenantName); ?></span>
      </div>
    </div>

    <!-- Stats cards -->
    <div class="row g-3 mb-4" id="ssv-stats-row">
      <div class="col-6 col-md-3">
        <div class="card text-center h-100">
          <div class="card-body">
            <div class="display-6 fw-bold text-primary" id="stat-meetings">—</div>
            <div class="text-muted small mt-1">Reuniones este mes</div>
          </div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="card text-center h-100">
          <div class="card-body">
            <div class="display-6 fw-bold text-info" id="stat-hours">—</div>
            <div class="text-muted small mt-1">Horas de video</div>
          </div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="card text-center h-100">
          <div class="card-body">
            <div class="display-6 fw-bold text-warning" id="stat-participants">—</div>
            <div class="text-muted small mt-1">Participantes</div>
          </div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="card text-center h-100">
          <div class="card-body">
            <div class="display-6 fw-bold text-success" id="stat-recordings">—</div>
            <div class="text-muted small mt-1">Grabaciones</div>
          </div>
        </div>
      </div>
    </div>
    <div id="ssv-stats-error" class="alert alert-danger d-none">Error cargando estadísticas.</div>

    <!-- Quick links -->
    <div class="row g-3">
      <div class="col-md-6">
        <div class="card">
          <div class="card-body">
            <h5 class="card-title">🎬 Grabaciones recientes</h5>
            <p class="card-text text-muted">Accede a todas las grabaciones de tus reuniones.</p>
            <a href="<?php echo $CFG->wwwroot; ?>/local/softsysvideo/recordings.php" class="btn btn-outline-primary">Ver grabaciones →</a>
          </div>
        </div>
      </div>
      <div class="col-md-6">
        <div class="card">
          <div class="card-body">
            <h5 class="card-title">📅 Reuniones recientes</h5>
            <p class="card-text text-muted">Revisa el historial de reuniones del tenant.</p>
            <a href="<?php echo $CFG->wwwroot; ?>/local/softsysvideo/meetings.php" class="btn btn-outline-primary">Ver reuniones →</a>
          </div>
        </div>
      </div>
    </div>

  <?php endif; ?>
</div>

<script>
(function() {
    var API_URL   = <?php echo json_encode($apiUrl); ?>;
    var PLUGIN_KEY = <?php echo json_encode($pluginKey); ?>;
    if (!PLUGIN_KEY) return;

    fetch(API_URL + '/api/moodle/stats', {
        headers: { 'Authorization': 'Bearer ' + PLUGIN_KEY }
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        document.getElementById('stat-meetings').textContent    = data.this_month ? data.this_month.meetings    : '—';
        document.getElementById('stat-hours').textContent       = data.this_month ? data.this_month.hours       : '—';
        document.getElementById('stat-participants').textContent = data.this_month ? data.this_month.participants : '—';
        document.getElementById('stat-recordings').textContent  = data.total_recordings !== undefined ? data.total_recordings : '—';
    })
    .catch(function() {
        document.getElementById('ssv-stats-error').classList.remove('d-none');
    });
})();
</script>

<?php
echo $OUTPUT->footer();
