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
 * Dashboard page for the SoftSys Video companion plugin.
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
$PAGE->set_url(new moodle_url('/local/softsysvideo/dashboard.php'));
$PAGE->set_title(get_string('dashboard', 'local_softsysvideo'));
$PAGE->set_heading(get_string('pluginname', 'local_softsysvideo'));
$PAGE->set_pagelayout('admin');

$isconnected = !empty(get_config('local_softsysvideo', 'softsysvideo_plugin_key'));
$apiurl      = get_config('local_softsysvideo', 'softsysvideo_api_url') ?: 'https://api.softsysvideo.com';
$pluginkey   = get_config('local_softsysvideo', 'softsysvideo_plugin_key') ?: '';
$tenantname  = get_config('local_softsysvideo', 'softsysvideo_tenant_name') ?: 'SoftSys Video';

if ($isconnected) {
    $PAGE->requires->js_call_amd('local_softsysvideo/dashboard', 'init', [$apiurl, $pluginkey]);
}

echo $OUTPUT->header();
?>

<div class="container-fluid py-3">

  <!-- Plugin nav -->
  <div class="d-flex gap-2 mb-3 flex-wrap">
    <a href="<?php echo $CFG->wwwroot; ?>/local/softsysvideo/dashboard.php" class="btn btn-sm btn-primary">Dashboard</a>
    <a href="<?php echo $CFG->wwwroot; ?>/local/softsysvideo/recordings.php" class="btn btn-sm btn-outline-secondary"><?php echo get_string('recordings', 'local_softsysvideo'); ?></a>
    <a href="<?php echo $CFG->wwwroot; ?>/local/softsysvideo/meetings.php" class="btn btn-sm btn-outline-secondary"><?php echo get_string('meetings', 'local_softsysvideo'); ?></a>
    <a href="<?php echo $CFG->wwwroot; ?>/local/softsysvideo/connect.php" class="btn btn-sm btn-outline-secondary"><?php echo get_string('connection', 'local_softsysvideo'); ?></a>
    <a href="<?php echo $CFG->wwwroot; ?>/local/softsysvideo/support.php" class="btn btn-sm btn-outline-danger"><?php echo get_string('support', 'local_softsysvideo'); ?></a>
  </div>

  <?php if (!$isconnected): ?>
    <?php echo $OUTPUT->notification(
        get_string('not_connected', 'local_softsysvideo') . ' ' .
        html_writer::link(
            new moodle_url('/local/softsysvideo/connect.php'),
            get_string('connect_account', 'local_softsysvideo')
        ),
        \core\output\notification::NOTIFY_WARNING
    ); ?>
  <?php else: ?>

    <!-- Header -->
    <div class="d-flex align-items-center gap-3 mb-4">
      <div>
        <h2 class="mb-0"><?php echo get_string('dashboard', 'local_softsysvideo'); ?></h2>
        <span class="badge bg-success" id="ssv-tenant-name"><?php echo get_string('connected', 'local_softsysvideo'); ?> &mdash; <?php echo htmlspecialchars($tenantname); ?></span>
      </div>
    </div>

    <!-- Stats cards -->
    <div class="row g-3 mb-4">
      <div class="col-6 col-md-3">
        <div class="card text-center h-100">
          <div class="card-body">
            <div class="display-6 fw-bold text-primary" id="ssv-stat-meetings">&mdash;</div>
            <div class="text-muted small mt-1"><?php echo get_string('this_month_meetings', 'local_softsysvideo'); ?></div>
          </div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="card text-center h-100">
          <div class="card-body">
            <div class="display-6 fw-bold text-info" id="ssv-stat-hours">&mdash;</div>
            <div class="text-muted small mt-1"><?php echo get_string('video_hours', 'local_softsysvideo'); ?></div>
          </div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="card text-center h-100">
          <div class="card-body">
            <div class="display-6 fw-bold text-warning" id="ssv-stat-participants">&mdash;</div>
            <div class="text-muted small mt-1"><?php echo get_string('total_participants', 'local_softsysvideo'); ?></div>
          </div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="card text-center h-100">
          <div class="card-body">
            <div class="display-6 fw-bold text-success" id="ssv-stat-recordings">&mdash;</div>
            <div class="text-muted small mt-1"><?php echo get_string('total_recordings', 'local_softsysvideo'); ?></div>
          </div>
        </div>
      </div>
    </div>
    <div id="ssv-stats-error" class="alert alert-danger d-none"><?php echo get_string('request_failed', 'local_softsysvideo'); ?></div>

    <!-- Quick links -->
    <div class="row g-3">
      <div class="col-md-6">
        <div class="card">
          <div class="card-body">
            <h5 class="card-title"><?php echo get_string('recordings', 'local_softsysvideo'); ?></h5>
            <p class="card-text text-muted">Accede a todas las grabaciones de tus reuniones.</p>
            <?php echo $OUTPUT->single_button(
                new moodle_url('/local/softsysvideo/recordings.php'),
                get_string('recordings', 'local_softsysvideo'),
                'get',
                ['class' => 'btn-outline-primary']
            ); ?>
          </div>
        </div>
      </div>
      <div class="col-md-6">
        <div class="card">
          <div class="card-body">
            <h5 class="card-title"><?php echo get_string('meetings', 'local_softsysvideo'); ?></h5>
            <p class="card-text text-muted">Revisa el historial de reuniones del tenant.</p>
            <?php echo $OUTPUT->single_button(
                new moodle_url('/local/softsysvideo/meetings.php'),
                get_string('meetings', 'local_softsysvideo'),
                'get',
                ['class' => 'btn-outline-primary']
            ); ?>
          </div>
        </div>
      </div>
    </div>

  <?php endif; ?>
</div>

<?php
echo $OUTPUT->footer();
