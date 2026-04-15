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
$PAGE->navbar->add(
    get_string('pluginname', 'local_softsysvideo'),
    new moodle_url('/local/softsysvideo/dashboard.php')
);
$PAGE->navbar->add(get_string('meetings', 'local_softsysvideo'));

$isconnected = !empty(get_config('local_softsysvideo', 'softsysvideo_plugin_key'));

if (!$isconnected) {
    redirect(new moodle_url('/local/softsysvideo/connect.php'));
}

if ($isconnected) {
    $filterstrs = [
        'no_meetings' => get_string('no_meetings', 'local_softsysvideo'),
        'total_meetings' => get_string('meetings', 'local_softsysvideo'),
        'previous' => get_string('previous', 'local_softsysvideo'),
        'next' => get_string('next', 'local_softsysvideo'),
        'page_x_of_y' => get_string('page_x_of_y', 'local_softsysvideo', (object)['current' => '{current}', 'total' => '{total}']),
    ];
    $PAGE->requires->js_call_amd('local_softsysvideo/meetings', 'init', [$filterstrs]);
}

echo $OUTPUT->header();

echo html_writer::start_div('container-fluid py-3');
echo local_softsysvideo_render_navigation('meetings');
echo html_writer::tag('h2', get_string('meetings', 'local_softsysvideo'));

if (!$isconnected) {
    echo $OUTPUT->notification(
        get_string('not_connected', 'local_softsysvideo') . ' ' .
        html_writer::link(
            new moodle_url('/local/softsysvideo/connect.php'),
            get_string('connect_account', 'local_softsysvideo')
        ),
        \core\output\notification::NOTIFY_WARNING
    );
} else {
    echo html_writer::div(
        get_string('request_failed', 'local_softsysvideo'),
        'alert alert-danger d-none',
        ['id' => 'ssv-meetings-error']
    );

    // Row 1: Search + pagination.
    $searchcol = html_writer::div(
        html_writer::tag(
            'label',
            get_string('search'),
            ['for' => 'ssv-meetings-search', 'class' => 'small text-muted d-block']
        ) .
        html_writer::empty_tag('input', [
            'type' => 'text',
            'id' => 'ssv-meetings-search',
            'class' => 'form-control form-control-sm',
            'placeholder' => get_string('search') . '...',
        ]),
        'col'
    );
    $pagination = html_writer::div(
        '',
        'd-flex align-items-end ssv-flex-gap',
        ['id' => 'ssv-meetings-pagination']
    );
    echo html_writer::div(
        $searchcol . html_writer::div($pagination, 'col-auto'),
        'row g-2 align-items-end mb-2'
    );

    // Row 2: Filters — uniform labels above each field.
    $statusopts = [
        '' => get_string('all_statuses', 'local_softsysvideo'),
        'active' => get_string('active_meetings', 'local_softsysvideo'),
        'ended' => get_string('ended_meetings', 'local_softsysvideo'),
    ];
    $recopts = [
        '' => get_string('all_statuses', 'local_softsysvideo'),
        'true' => get_string('with_recording', 'local_softsysvideo'),
        'false' => get_string('without_recording', 'local_softsysvideo'),
    ];
    $sortbyopts = [
        'started_at' => get_string('sort_date', 'local_softsysvideo'),
        'duration_seconds' => get_string('sort_duration', 'local_softsysvideo'),
        'participant_count' => get_string('sort_participants', 'local_softsysvideo'),
        'name' => get_string('sort_name', 'local_softsysvideo'),
    ];
    $sortorderopts = [
        'desc' => get_string('descending', 'local_softsysvideo'),
        'asc' => get_string('ascending', 'local_softsysvideo'),
    ];

    $lbl = 'small text-muted d-block';
    $ctl = 'form-control form-control-sm';

    $fc1 = html_writer::div(
        html_writer::tag('label', get_string('status', 'local_softsysvideo'), [
            'for' => 'ssv-filter-status', 'class' => $lbl,
        ]) .
        html_writer::select(
            $statusopts,
            'ssv-filter-status',
            '',
            false,
            ['id' => 'ssv-filter-status', 'class' => $ctl]
        ),
        'col'
    );
    $fc2 = html_writer::div(
        html_writer::tag('label', get_string('recordings', 'local_softsysvideo'), [
            'for' => 'ssv-filter-recording', 'class' => $lbl,
        ]) .
        html_writer::select(
            $recopts,
            'ssv-filter-recording',
            '',
            false,
            ['id' => 'ssv-filter-recording', 'class' => $ctl]
        ),
        'col'
    );
    $fc3 = html_writer::div(
        html_writer::tag('label', get_string('date_from', 'local_softsysvideo'), [
            'for' => 'ssv-filter-date-from', 'class' => $lbl,
        ]) .
        html_writer::empty_tag('input', [
            'type' => 'date', 'id' => 'ssv-filter-date-from', 'class' => $ctl,
        ]),
        'col'
    );
    $fc4 = html_writer::div(
        html_writer::tag('label', get_string('date_to', 'local_softsysvideo'), [
            'for' => 'ssv-filter-date-to', 'class' => $lbl,
        ]) .
        html_writer::empty_tag('input', [
            'type' => 'date', 'id' => 'ssv-filter-date-to', 'class' => $ctl,
        ]),
        'col'
    );
    $fc5 = html_writer::div(
        html_writer::tag('label', get_string('sort_by', 'local_softsysvideo'), [
            'for' => 'ssv-filter-sort-by', 'class' => $lbl,
        ]) .
        html_writer::select(
            $sortbyopts,
            'ssv-filter-sort-by',
            'started_at',
            false,
            ['id' => 'ssv-filter-sort-by', 'class' => $ctl]
        ),
        'col'
    );
    $fc6 = html_writer::div(
        html_writer::tag('label', get_string('sort_order', 'local_softsysvideo'), [
            'for' => 'ssv-filter-sort-order', 'class' => $lbl,
        ]) .
        html_writer::select(
            $sortorderopts,
            'ssv-filter-sort-order',
            'desc',
            false,
            ['id' => 'ssv-filter-sort-order', 'class' => $ctl]
        ),
        'col-auto'
    );
    $fc7 = html_writer::div(
        html_writer::tag('label', '&nbsp;', ['class' => $lbl]) .
        html_writer::tag('button', get_string('apply_filters', 'local_softsysvideo'), [
            'class' => 'btn btn-sm btn-primary',
            'id' => 'ssv-filter-apply',
            'type' => 'button',
        ]),
        'col-auto'
    );
    echo html_writer::div(
        $fc1 . $fc2 . $fc3 . $fc4 . $fc5 . $fc6 . $fc7,
        'row g-2 align-items-end mb-3'
    );

    echo html_writer::div(
        $OUTPUT->pix_icon('i/loading', '', 'moodle', ['class' => 'icon-lg']),
        'text-center py-3',
        ['id' => 'ssv-meetings-spinner']
    );

    $th = html_writer::tag('th', get_string('meeting_name', 'local_softsysvideo'));
    $th .= html_writer::tag('th', get_string('start_date', 'local_softsysvideo'));
    $th .= html_writer::tag('th', get_string('duration', 'local_softsysvideo'));
    $th .= html_writer::tag('th', get_string('participants', 'local_softsysvideo'));
    $th .= html_writer::tag('th', '');
    $thead = html_writer::tag('thead', html_writer::tag('tr', $th));
    $tbody = html_writer::tag('tbody', '', ['id' => 'ssv-meetings-tbody']);
    $table = html_writer::tag('table', $thead . $tbody, [
        'class' => 'generaltable table table-striped local-softsysvideo-table',
    ]);
    $tablehtml = html_writer::div($table, 'table-responsive');
    $counthtml = html_writer::tag('p', '', ['class' => 'text-muted small', 'id' => 'ssv-meetings-count']);
    echo html_writer::div($tablehtml . $counthtml, 'd-none', ['id' => 'ssv-meetings-container']);
    // Participants modal.
    $modalspinner = html_writer::div(
        $OUTPUT->pix_icon('i/loading', '', 'moodle', ['class' => 'icon-lg']),
        'text-center py-3',
        ['id' => 'ssv-participants-spinner']
    );
    $modalerror = html_writer::div(
        get_string('request_failed', 'local_softsysvideo'),
        'alert alert-danger d-none',
        ['id' => 'ssv-participants-error']
    );
    $modalth = html_writer::tag('th', get_string('full_name', 'local_softsysvideo'));
    $modalth .= html_writer::tag('th', get_string('role', 'local_softsysvideo'));
    $modalth .= html_writer::tag('th', get_string('joined', 'local_softsysvideo'));
    $modalth .= html_writer::tag('th', get_string('duration', 'local_softsysvideo'));
    $modalth .= html_writer::tag('th', get_string('video', 'local_softsysvideo'));
    $modalth .= html_writer::tag('th', get_string('audio', 'local_softsysvideo'));
    $modalthead = html_writer::tag('thead', html_writer::tag('tr', $modalth));
    $modaltbody = html_writer::tag('tbody', '', ['id' => 'ssv-participants-tbody']);
    $modaltable = html_writer::tag('table', $modalthead . $modaltbody, [
        'class' => 'table table-sm table-striped local-softsysvideo-table d-none',
        'id'    => 'ssv-participants-table',
    ]);
    $noresults = html_writer::div(
        get_string('no_participants', 'local_softsysvideo'),
        'text-muted text-center py-3 d-none',
        ['id' => 'ssv-participants-empty']
    );
    $modalbody = html_writer::div(
        $modalspinner . $modalerror . $modaltable . $noresults,
        'modal-body'
    );
    $modalheader = html_writer::div(
        html_writer::tag('h5', get_string('participant_details', 'local_softsysvideo'), [
            'class' => 'modal-title', 'id' => 'ssv-participants-modal-label',
        ]) .
        html_writer::tag('button', '', [
            'type' => 'button', 'class' => 'btn-close',
            'data-bs-dismiss' => 'modal', 'aria-label' => 'Close',
        ]),
        'modal-header'
    );
    $modalcontent = html_writer::div(
        $modalheader . $modalbody,
        'modal-content'
    );
    $modaldialog = html_writer::div($modalcontent, 'modal-dialog modal-lg modal-dialog-scrollable');
    echo html_writer::div($modaldialog, 'modal fade', [
        'id'              => 'ssv-participants-modal',
        'tabindex'        => '-1',
        'aria-labelledby' => 'ssv-participants-modal-label',
        'aria-hidden'     => 'true',
    ]);
}

echo html_writer::end_div();
echo $OUTPUT->footer();
