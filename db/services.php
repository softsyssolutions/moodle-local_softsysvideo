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
 * External service definitions for local_softsysvideo.
 *
 * @package    local_softsysvideo
 * @copyright  2026 SoftSys Solutions {@link https://softsyssolutions.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'local_softsysvideo_get_stats' => [
        'classname'   => \local_softsysvideo\external\get_stats::class,
        'methodname'  => 'execute',
        'description' => 'Get dashboard statistics from the SoftSys Video API.',
        'type'        => 'read',
        'ajax'        => true,
        'capabilities' => 'local/softsysvideo:manage',
    ],
    'local_softsysvideo_get_analytics' => [
        'classname'   => \local_softsysvideo\external\get_analytics::class,
        'methodname'  => 'execute',
        'description' => 'Get analytics chart data from the SoftSys Video API.',
        'type'        => 'read',
        'ajax'        => true,
        'capabilities' => 'local/softsysvideo:manage',
    ],
    'local_softsysvideo_get_recordings' => [
        'classname'   => \local_softsysvideo\external\get_recordings::class,
        'methodname'  => 'execute',
        'description' => 'Get recordings list from the SoftSys Video API.',
        'type'        => 'read',
        'ajax'        => true,
        'capabilities' => 'local/softsysvideo:manage',
    ],
    'local_softsysvideo_get_meetings' => [
        'classname'   => \local_softsysvideo\external\get_meetings::class,
        'methodname'  => 'execute',
        'description' => 'Get meetings list from the SoftSys Video API.',
        'type'        => 'read',
        'ajax'        => true,
        'capabilities' => 'local/softsysvideo:manage',
    ],
    'local_softsysvideo_get_tickets' => [
        'classname'   => \local_softsysvideo\external\get_tickets::class,
        'methodname'  => 'execute',
        'description' => 'Get support tickets from the SoftSys Video API.',
        'type'        => 'read',
        'ajax'        => true,
        'capabilities' => 'local/softsysvideo:manage',
    ],
    'local_softsysvideo_get_ticket_detail' => [
        'classname'   => \local_softsysvideo\external\get_ticket_detail::class,
        'methodname'  => 'execute',
        'description' => 'Get a single support ticket with messages.',
        'type'        => 'read',
        'ajax'        => true,
        'capabilities' => 'local/softsysvideo:manage',
    ],
    'local_softsysvideo_get_meeting_participants' => [
        'classname'   => \local_softsysvideo\external\get_meeting_participants::class,
        'methodname'  => 'execute',
        'description' => 'Get participants for a specific meeting.',
        'type'        => 'read',
        'ajax'        => true,
        'capabilities' => 'local/softsysvideo:manage',
    ],
    'local_softsysvideo_create_ticket' => [
        'classname'   => \local_softsysvideo\external\create_ticket::class,
        'methodname'  => 'execute',
        'description' => 'Create a support ticket via the SoftSys Video API.',
        'type'        => 'write',
        'ajax'        => true,
        'capabilities' => 'local/softsysvideo:manage',
    ],
];
