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
 * External function: get dashboard stats via server-side proxy.
 *
 * @package    local_softsysvideo
 * @copyright  2026 SoftSys Solutions {@link https://softsyssolutions.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_softsysvideo\external;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

use external_api;
use external_function_parameters;
use external_single_structure;
use external_value;

/**
 * Proxy for /api/moodle/stats.
 */
class get_stats extends external_api {
    /**
     * Describe parameters.
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([]);
    }

    /**
     * Execute the external function.
     * @return array
     */
    public static function execute(): array {
        $context = \context_system::instance();
        self::validate_context($context);
        require_capability('local/softsysvideo:manage', $context);

        $client = \local_softsysvideo\api_client::from_config();
        $data = $client->get('/api/moodle/stats');

        $month = $data['this_month'] ?? [];
        return [
            'meetings'           => (int)($month['meetings'] ?? 0),
            'session_minutes'    => (int)($month['session_minutes'] ?? 0),
            'total_hours'        => (string)($month['total_hours'] ?? '0m'),
            'participants'       => (int)($month['participants'] ?? 0),
            'recordings'         => (int)($month['recordings'] ?? 0),
            'recording_minutes'  => (int)($month['recording_minutes'] ?? 0),
            'tenant_name'        => (string)($data['tenant_name'] ?? ''),
        ];
    }

    /**
     * Describe return value.
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'meetings'            => new external_value(PARAM_INT, 'Meetings this month'),
            'session_minutes'     => new external_value(PARAM_INT, 'Session minutes this month'),
            'total_hours'         => new external_value(PARAM_TEXT, 'Video hours this month'),
            'participants'        => new external_value(PARAM_INT, 'Total participants this month'),
            'recordings'          => new external_value(PARAM_INT, 'Recordings this month'),
            'recording_minutes'   => new external_value(PARAM_INT, 'Recording minutes this month'),
            'tenant_name'         => new external_value(PARAM_TEXT, 'Tenant name'),
        ]);
    }
}
