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
 * External function: get support tickets via server-side proxy.
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
 * Proxy for /api/moodle/support/tickets.
 */
class get_tickets extends external_api {
    /**
     * Describe parameters.
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'limit'  => new external_value(PARAM_INT, 'Max items', VALUE_DEFAULT, 20),
            'offset' => new external_value(PARAM_INT, 'Offset', VALUE_DEFAULT, 0),
        ]);
    }

    /**
     * Execute the external function.
     * @param int $limit Max items.
     * @param int $offset Offset.
     * @return array
     */
    public static function execute(int $limit = 20, int $offset = 0): array {
        $params = self::validate_parameters(self::execute_parameters(), [
            'limit' => $limit, 'offset' => $offset,
        ]);

        $context = \context_system::instance();
        self::validate_context($context);
        require_capability('local/softsysvideo:manage', $context);

        $client = \local_softsysvideo\api_client::from_config();
        $data = $client->get('/api/moodle/support/tickets', [
            'limit' => $params['limit'],
            'offset' => $params['offset'],
        ]);

        $tickets = [];
        if (!empty($data['tickets']) && is_array($data['tickets'])) {
            foreach ($data['tickets'] as $t) {
                $tickets[] = [
                    'id'         => (int)($t['id'] ?? 0),
                    'subject'    => $t['subject'] ?? '',
                    'status'     => $t['status'] ?? '',
                    'priority'   => $t['priority'] ?? '',
                    'created_at' => $t['createdAt'] ?? $t['created_at'] ?? '',
                ];
            }
        }

        return [
            'tickets' => $tickets,
            'total'   => (int)($data['total'] ?? 0),
        ];
    }

    /**
     * Describe return value.
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'tickets' => new external_multiple_structure(
                new external_single_structure([
                    'id'         => new external_value(PARAM_INT, 'Ticket ID'),
                    'subject'    => new external_value(PARAM_TEXT, 'Subject'),
                    'status'     => new external_value(PARAM_TEXT, 'Status'),
                    'priority'   => new external_value(PARAM_TEXT, 'Priority'),
                    'created_at' => new external_value(PARAM_TEXT, 'Created date'),
                ])
            ),
            'total' => new external_value(PARAM_INT, 'Total tickets'),
        ]);
    }
}
