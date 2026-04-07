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
 * External function: get recordings list via server-side proxy.
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
 * Proxy for /api/moodle/recordings.
 */
class get_recordings extends external_api {
    /**
     * Describe parameters.
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'page'       => new external_value(PARAM_INT, 'Page number', VALUE_DEFAULT, 1),
            'per_page'   => new external_value(PARAM_INT, 'Items per page', VALUE_DEFAULT, 20),
            'search'     => new external_value(PARAM_TEXT, 'Search term', VALUE_DEFAULT, ''),
            'state'      => new external_value(PARAM_ALPHA, 'State filter', VALUE_DEFAULT, ''),
            'date_from'  => new external_value(PARAM_INT, 'Date from (unix)', VALUE_DEFAULT, 0),
            'date_to'    => new external_value(PARAM_INT, 'Date to (unix)', VALUE_DEFAULT, 0),
            'sort_by'    => new external_value(PARAM_ALPHANUMEXT, 'Sort field', VALUE_DEFAULT, 'created_at'),
            'sort_order' => new external_value(PARAM_ALPHA, 'Sort order', VALUE_DEFAULT, 'desc'),
        ]);
    }

    /**
     * Execute the external function.
     * @param int    $page Page number.
     * @param int    $perpage Items per page.
     * @param string $search Search term.
     * @param string $state State filter.
     * @param int    $datefrom Date from unix.
     * @param int    $dateto Date to unix.
     * @param string $sortby Sort field.
     * @param string $sortorder Sort order.
     * @return array
     */
    public static function execute(
        int $page = 1,
        int $perpage = 20,
        string $search = '',
        string $state = '',
        int $datefrom = 0,
        int $dateto = 0,
        string $sortby = 'created_at',
        string $sortorder = 'desc'
    ): array {
        $params = self::validate_parameters(self::execute_parameters(), [
            'page' => $page, 'per_page' => $perpage, 'search' => $search,
            'state' => $state, 'date_from' => $datefrom, 'date_to' => $dateto,
            'sort_by' => $sortby, 'sort_order' => $sortorder,
        ]);

        $context = \context_system::instance();
        self::validate_context($context);
        require_capability('local/softsysvideo:manage', $context);

        $query = ['page' => $params['page'], 'per_page' => $params['per_page']];
        if (!empty($params['search'])) {
            $query['search'] = $params['search'];
        }
        if (!empty($params['state'])) {
            $query['state'] = $params['state'];
        }
        if (!empty($params['date_from'])) {
            $query['date_from'] = $params['date_from'];
        }
        if (!empty($params['date_to'])) {
            $query['date_to'] = $params['date_to'];
        }
        if (!empty($params['sort_by'])) {
            $query['sort_by'] = $params['sort_by'];
        }
        if (!empty($params['sort_order'])) {
            $query['sort_order'] = $params['sort_order'];
        }

        $client = \local_softsysvideo\api_client::from_config();
        $data = $client->get('/api/moodle/recordings', $query);

        $recordings = [];
        if (!empty($data['recordings']) && is_array($data['recordings'])) {
            foreach ($data['recordings'] as $rec) {
                $recordings[] = [
                    'name'             => $rec['name'] ?? '',
                    'meeting_name'     => $rec['meeting_name'] ?? $rec['meeting'] ?? '',
                    'created_at'       => (int)($rec['created_at'] ?? 0),
                    'duration_seconds' => (int)($rec['duration_seconds'] ?? 0),
                    'size_bytes'       => (int)($rec['size_bytes'] ?? 0),
                    'playback_url'     => $rec['url'] ?? $rec['playback_url'] ?? '',
                ];
            }
        }

        return [
            'recordings'  => $recordings,
            'total'       => (int)($data['total'] ?? 0),
            'total_pages' => (int)($data['total_pages'] ?? 1),
        ];
    }

    /**
     * Describe return value.
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'recordings' => new external_multiple_structure(
                new external_single_structure([
                    'name'             => new external_value(PARAM_TEXT, 'Recording name'),
                    'meeting_name'     => new external_value(PARAM_TEXT, 'Meeting name'),
                    'created_at'       => new external_value(PARAM_INT, 'Created timestamp'),
                    'duration_seconds' => new external_value(PARAM_INT, 'Duration in seconds'),
                    'size_bytes'       => new external_value(PARAM_INT, 'Size in bytes'),
                    'playback_url'     => new external_value(PARAM_URL, 'Playback URL'),
                ])
            ),
            'total'       => new external_value(PARAM_INT, 'Total recordings'),
            'total_pages' => new external_value(PARAM_INT, 'Total pages'),
        ]);
    }
}
