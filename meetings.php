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
 * Meetings page for the SoftSys Video companion plugin.
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
$PAGE->set_url(new moodle_url('/local/softsysvideo/meetings.php'));
$PAGE->set_title(get_string('meetings', 'local_softsysvideo'));
$PAGE->set_heading(get_string('pluginname', 'local_softsysvideo'));
$PAGE->set_pagelayout('admin');

$isconnected = !empty(get_config('local_softsysvideo', 'softsysvideo_plugin_key'));
$apiurl      = get_config('local_softsysvideo', 'softsysvideo_api_url') ?: 'https://api.softsysvideo.com';
$pluginkey   = get_config('local_softsysvideo', 'softsysvideo_plugin_key') ?: '';

if ($isconnected) {
    $PAGE->requires->js_call_amd('local_softsysvideo/meetings', 'init', [$apiurl, $pluginkey]);
}

echo $OUTPUT->header();
?>

<div class="container-fluid py-3">

  <!-- Plugin nav -->
  <div class="d-flex gap-2 mb-3 flex-wrap">
    <a href="<?php echo $CFG->wwwroot; ?>/local/softsysvideo/dashboard.php" class="btn btn-sm btn-outline-primary"><?php echo get_string('dashboard', 'local_softsysvideo'); ?></a>
    <a href="<?php echo $CFG->wwwroot; ?>/local/softsysvideo/recordings.php"
       class="btn btn-sm btn-outline-secondary"><?php echo get_string('recordings', 'local_softsysvideo'); ?></a>
    <a href="<?php echo $CFG->wwwroot; ?>/local/softsysvideo/meetings.php" class="btn btn-sm btn-secondary"><?php echo get_string('meetings', 'local_softsysvideo'); ?></a>
    <a href="<?php echo $CFG->wwwroot; ?>/local/softsysvideo/connect.php" class="btn btn-sm btn-outline-secondary"><?php echo get_string('connection', 'local_softsysvideo'); ?></a>
    <a href="<?php echo $CFG->wwwroot; ?>/local/softsysvideo/support.php" class="btn btn-sm btn-outline-danger"><?php echo get_string('support', 'local_softsysvideo'); ?></a>
  </div>

  <h2><?php echo get_string('meetings', 'local_softsysvideo'); ?></h2>

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

    <div id="ssv-meetings-error" class="alert alert-danger d-none"><?php echo get_string('request_failed', 'local_softsysvideo'); ?></div>

    <div class="d-flex justify-content-between align-items-center mb-2">
      <input type="text" id="ssv-meetings-search" class="form-control w-auto"
             placeholder="<?php echo get_string('search'); ?>..." style="max-width:250px">
      <div id="ssv-meetings-pagination" class="d-flex gap-2 align-items-center"></div>
    </div>
    <div id="ssv-meetings-spinner" class="text-center py-3">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden"><?php echo get_string('loading', 'local_softsysvideo'); ?></span>
      </div>
    </div>

    <div id="ssv-meetings-container" class="d-none">
      <div class="table-responsive">
        <table class="table table-striped table-hover">
          <thead class="table-dark">
            <tr>
              <th><?php echo get_string('meeting_name', 'local_softsysvideo'); ?></th>
              <th><?php echo get_string('start_date', 'local_softsysvideo'); ?></th>
              <th><?php echo get_string('duration', 'local_softsysvideo'); ?></th>
              <th><?php echo get_string('participants', 'local_softsysvideo'); ?></th>
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

<?php
echo $OUTPUT->footer();
