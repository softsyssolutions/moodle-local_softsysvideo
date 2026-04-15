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
 * External function: get participants for a specific meeting.
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
 * Proxy for /api/moodle/meetings/{meetingId}/participants.
 */
class get_meeting_participants extends external_api {
    /**
     * Describe parameters.
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'meeting_id' => new external_value(PARAM_TEXT, 'The BigBlueButton meeting ID'),
        ]);
    }

    /**
     * Execute the external function.
     * @param string $meeting_id The meeting ID.
     * @return array
     */
    public static function execute(string $meeting_id): array {
        $params = self::validate_parameters(self::execute_parameters(), ['meeting_id' => $meeting_id]);
        $context = \context_system::instance();
        self::validate_context($context);
        require_capability('local/softsysvideo:manage', $context);

        $client = \local_softsysvideo\api_client::from_config();
        $data = $client->get('/api/moodle/meetings/' . urlencode($params['meeting_id']) . '/participants');

        $participants = [];
        if (!empty($data['participants']) && is_array($data['participants'])) {
            foreach ($data['participants'] as $p) {
                $participants[] = [
                    'full_name'        => (string)($p['full_name'] ?? ''),
                    'role'             => (string)($p['role'] ?? ''),
                    'joined_at'        => (int)($p['joined_at'] ?? 0),
                    'left_at'          => (int)($p['left_at'] ?? 0),
                    'duration_seconds' => (int)($p['duration_seconds'] ?? 0),
                    'video_enabled'    => (bool)($p['video_enabled'] ?? false),
                    'audio_enabled'    => (bool)($p['audio_enabled'] ?? false),
                    'screen_shared'    => (bool)($p['screen_shared'] ?? false),
                ];
            }
        }

        return ['participants' => $participants];
    }

    /**
     * Describe return value.
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'participants' => new external_multiple_structure(
                new external_single_structure([
                    'full_name'        => new external_value(PARAM_TEXT, 'Participant full name'),
                    'role'             => new external_value(PARAM_TEXT, 'Role: moderator or attendee'),
                    'joined_at'        => new external_value(PARAM_INT, 'Unix join timestamp'),
                    'left_at'          => new external_value(PARAM_INT, 'Unix leave timestamp (0 if still in session)'),
                    'duration_seconds' => new external_value(PARAM_INT, 'Duration in seconds'),
                    'video_enabled'    => new external_value(PARAM_BOOL, 'Had video enabled'),
                    'audio_enabled'    => new external_value(PARAM_BOOL, 'Had audio enabled'),
                    'screen_shared'    => new external_value(PARAM_BOOL, 'Shared screen'),
                ])
            ),
        ]);
    }
}
