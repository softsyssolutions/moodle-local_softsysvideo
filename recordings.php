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
 * Recordings page for the SoftSys Video companion plugin.
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
$PAGE->set_url(new moodle_url('/local/softsysvideo/recordings.php'));
$PAGE->set_title(get_string('recordings', 'local_softsysvideo'));
$PAGE->set_heading(get_string('pluginname', 'local_softsysvideo'));
$PAGE->set_pagelayout('admin');

$isconnected = !empty(get_config('local_softsysvideo', 'softsysvideo_plugin_key'));
$apiurl      = get_config('local_softsysvideo', 'softsysvideo_api_url') ?: 'https://api.softsysvideo.com';
$pluginkey   = get_config('local_softsysvideo', 'softsysvideo_plugin_key') ?: '';

if ($isconnected) {
    $PAGE->requires->js_call_amd('local_softsysvideo/recordings', 'init', [$apiurl, $pluginkey]);
}

// Build plugin navigation.
$navlinks = [];
$navlinks[] = html_writer::link(
    new moodle_url('/local/softsysvideo/dashboard.php'),
    get_string('dashboard', 'local_softsysvideo'),
    ['class' => 'btn btn-sm btn-outline-primary']
);
$navlinks[] = html_writer::link(
    new moodle_url('/local/softsysvideo/recordings.php'),
    get_string('recordings', 'local_softsysvideo'),
    ['class' => 'btn btn-sm btn-secondary']
);
$navlinks[] = html_writer::link(
    new moodle_url('/local/softsysvideo/meetings.php'),
    get_string('meetings', 'local_softsysvideo'),
    ['class' => 'btn btn-sm btn-outline-secondary']
);
$navlinks[] = html_writer::link(
    new moodle_url('/local/softsysvideo/connect.php'),
    get_string('connection', 'local_softsysvideo'),
    ['class' => 'btn btn-sm btn-outline-secondary']
);
$navlinks[] = html_writer::link(
    new moodle_url('/local/softsysvideo/support.php'),
    get_string('support', 'local_softsysvideo'),
    ['class' => 'btn btn-sm btn-outline-danger']
);
$navhtml = html_writer::div(
    implode('', $navlinks),
    'd-flex gap-2 mb-3 flex-wrap'
);

echo $OUTPUT->header();
?>

<div class="container-fluid py-3">

  <!-- Plugin nav -->
  <?php echo $navhtml; ?>

  <h2><?php echo get_string('recordings', 'local_softsysvideo'); ?></h2>

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

    <?php
    echo html_writer::div(
        get_string('request_failed', 'local_softsysvideo'),
        'alert alert-danger d-none',
        ['id' => 'ssv-recordings-error']
    );
    ?>

    <div class="d-flex justify-content-between align-items-center mb-2">
      <input type="text" id="ssv-recordings-search" class="form-control w-auto"
             placeholder="<?php echo get_string('search'); ?>..." style="max-width:250px">
      <div id="ssv-recordings-pagination" class="d-flex gap-2 align-items-center"></div>
    </div>
    <div id="ssv-recordings-spinner" class="text-center py-3">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden"><?php echo get_string('loading', 'local_softsysvideo'); ?></span>
      </div>
    </div>

    <div id="ssv-recordings-container" class="d-none">
      <div class="table-responsive">
        <table class="table table-striped table-hover">
          <thead class="table-dark">
            <tr>
              <th><?php echo get_string('recording_name', 'local_softsysvideo'); ?></th>
              <th><?php echo get_string('meeting', 'local_softsysvideo'); ?></th>
              <th><?php echo get_string('date', 'local_softsysvideo'); ?></th>
              <th><?php echo get_string('duration', 'local_softsysvideo'); ?></th>
              <th><?php echo get_string('size', 'local_softsysvideo'); ?></th>
              <th><?php echo get_string('play', 'local_softsysvideo'); ?></th>
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

<?php
echo $OUTPUT->footer();
