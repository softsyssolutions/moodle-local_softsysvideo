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
 * External function: create a support ticket via server-side proxy.
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
 * Proxy for POST /api/moodle/support/tickets.
 */
class create_ticket extends external_api {
    /**
     * Describe parameters.
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'subject'     => new external_value(PARAM_TEXT, 'Ticket subject'),
            'description' => new external_value(PARAM_RAW, 'Ticket description'),
            'course_id'   => new external_value(PARAM_TEXT, 'Course ID (optional)', VALUE_DEFAULT, ''),
        ]);
    }

    /**
     * Execute the external function.
     * @param string $subject Ticket subject.
     * @param string $description Ticket description.
     * @param string $courseid Course ID.
     * @return array
     */
    public static function execute(string $subject, string $description, string $courseid = ''): array {
        global $CFG;

        $params = self::validate_parameters(self::execute_parameters(), [
            'subject' => $subject, 'description' => $description, 'course_id' => $courseid,
        ]);

        $context = \context_system::instance();
        self::validate_context($context);
        require_capability('local/softsysvideo:manage', $context);

        $body = [
            'subject'         => $params['subject'],
            'description'     => $params['description'],
            'moodle_site_url' => $CFG->wwwroot,
        ];
        if (!empty($params['course_id'])) {
            $body['moodle_course_id'] = $params['course_id'];
        }

        $client = \local_softsysvideo\api_client::from_config();
        $data = $client->post('/api/moodle/support/tickets', $body);

        $ticketid = 0;
        if (!empty($data['id'])) {
            $ticketid = (int)$data['id'];
        } else if (!empty($data['ticketRef'])) {
            // Backend returns "SSS-TKT-<id>"; extract the numeric portion.
            $ticketid = (int)preg_replace('/\D/', '', $data['ticketRef']);
        }

        return [
            'success' => !empty($data['success']),
            'id'      => $ticketid,
        ];
    }

    /**
     * Describe return value.
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Whether the ticket was created'),
            'id'      => new external_value(PARAM_INT, 'New ticket ID'),
        ]);
    }
}
