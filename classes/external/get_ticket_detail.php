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
 * External function: get a single support ticket with messages.
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
 * Proxy for /api/moodle/support/tickets/:id.
 */
class get_ticket_detail extends external_api {
    /**
     * Describe parameters.
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'ticketid' => new external_value(PARAM_INT, 'Ticket ID'),
        ]);
    }

    /**
     * Execute the external function.
     * @param int $ticketid Ticket ID.
     * @return array
     */
    public static function execute(int $ticketid): array {
        global $CFG;

        $params = self::validate_parameters(self::execute_parameters(), ['ticketid' => $ticketid]);

        $context = \context_system::instance();
        self::validate_context($context);
        require_capability('local/softsysvideo:manage', $context);

        $client = \local_softsysvideo\api_client::from_config();
        $data = $client->get('/api/moodle/support/tickets/' . $params['ticketid']);

        $ticket = $data['ticket'] ?? [];
        $messages = [];
        if (!empty($data['messages']) && is_array($data['messages'])) {
            foreach ($data['messages'] as $msg) {
                $body = self::rewrite_image_urls($msg['body'] ?? '');
                $attachments = [];
                if (!empty($msg['attachments']) && is_array($msg['attachments'])) {
                    foreach ($msg['attachments'] as $att) {
                        $rawurl = $att['url'] ?? '';
                        $proxyurl = !empty($rawurl)
                            ? $CFG->wwwroot . '/local/softsysvideo/image_proxy.php?path=' . urlencode($rawurl)
                            : '';
                        $attachments[] = [
                            'name'     => $att['name'] ?? '',
                            'mimetype' => $att['mimetype'] ?? '',
                            'url'      => $proxyurl,
                        ];
                    }
                }
                $messages[] = [
                    'author'      => $msg['author'] ?? 'System',
                    'body'        => format_text($body, FORMAT_HTML, ['context' => $context]),
                    'date'        => $msg['date'] ?? '',
                    'attachments' => $attachments,
                ];
            }
        }

        $rawdesc = self::rewrite_image_urls($ticket['description'] ?? '');

        return [
            'subject'     => $ticket['subject'] ?? '',
            'status'      => $ticket['status'] ?? '',
            'priority'    => $ticket['priority'] ?? '',
            'created_at'  => $ticket['createdAt'] ?? $ticket['created_at'] ?? '',
            'description' => format_text($rawdesc, FORMAT_HTML, ['context' => $context]),
            'messages'    => $messages,
        ];
    }

    /**
     * Rewrite backend-relative image URLs to point at the local image proxy.
     *
     * The backend returns src="/api/moodle/support/image/web/image/123".
     * We rewrite those to the Moodle-side proxy so the browser can load them
     * without needing direct access to the API backend.
     *
     * @param string $html Raw HTML from the API.
     * @return string HTML with rewritten image src attributes.
     */
    private static function rewrite_image_urls(string $html): string {
        global $CFG;
        if (empty($html)) {
            return '';
        }
        $proxybase = $CFG->wwwroot . '/local/softsysvideo/image_proxy.php?path=';
        return preg_replace_callback(
            '#src=(["\'])(/api/(?:moodle/)?support/image/[^"\']+)\1#',
            static function (array $matches) use ($proxybase): string {
                return 'src=' . $matches[1] . $proxybase . rawurlencode($matches[2]) . $matches[1];
            },
            $html
        );
    }

    /**
     * Describe return value.
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'subject'     => new external_value(PARAM_TEXT, 'Subject'),
            'status'      => new external_value(PARAM_TEXT, 'Status'),
            'priority'    => new external_value(PARAM_TEXT, 'Priority'),
            'created_at'  => new external_value(PARAM_TEXT, 'Created date'),
            'description' => new external_value(PARAM_RAW, 'Description'),
            'messages'    => new external_multiple_structure(
                new external_single_structure([
                    'author'      => new external_value(PARAM_TEXT, 'Author'),
                    'body'        => new external_value(PARAM_RAW, 'Message body'),
                    'date'        => new external_value(PARAM_TEXT, 'Message date'),
                    'attachments' => new external_multiple_structure(
                        new external_single_structure([
                            'name'     => new external_value(PARAM_TEXT, 'File name'),
                            'mimetype' => new external_value(PARAM_TEXT, 'MIME type'),
                            'url'      => new external_value(PARAM_RAW, 'Proxy URL'),
                        ]),
                        'Image attachments',
                        VALUE_DEFAULT,
                        []
                    ),
                ])
            ),
        ]);
    }
}
