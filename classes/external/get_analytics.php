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
 * External function: get analytics chart data via server-side proxy.
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
use external_multiple_structure;
use external_value;

/**
 * Proxy for /api/moodle/analytics.
 */
class get_analytics extends external_api {
    /**
     * Describe parameters.
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'range' => new external_value(PARAM_ALPHANUMEXT, 'Time range (e.g. 30d)', VALUE_DEFAULT, '30d'),
        ]);
    }

    /**
     * Execute the external function.
     * @param string $range Time range filter.
     * @return array
     */
    public static function execute(string $range = '30d'): array {
        $params = self::validate_parameters(self::execute_parameters(), ['range' => $range]);
        $context = \context_system::instance();
        self::validate_context($context);
        require_capability('local/softsysvideo:manage', $context);

        $client = \local_softsysvideo\api_client::from_config();
        $data = $client->get('/api/moodle/analytics', ['range' => $params['range']]);

        $chartdata = [];
        if (!empty($data['chart_data']) && is_array($data['chart_data'])) {
            foreach ($data['chart_data'] as $point) {
                $chartdata[] = [
                    'date'     => $point['date'] ?? '',
                    'sessions' => (int)($point['sessions'] ?? 0),
                    'minutes'  => (int)($point['minutes'] ?? 0),
                ];
            }
        }

        return ['chart_data' => $chartdata];
    }

    /**
     * Describe return value.
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'chart_data' => new external_multiple_structure(
                new external_single_structure([
                    'date'     => new external_value(PARAM_TEXT, 'Date label'),
                    'sessions' => new external_value(PARAM_INT, 'Number of sessions'),
                    'minutes'  => new external_value(PARAM_INT, 'Minutes consumed'),
                ])
            ),
        ]);
    }
}
