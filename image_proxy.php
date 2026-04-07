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
 * Image proxy for support ticket attachments.
 *
 * Fetches images from the SoftSys Video API backend using the stored
 * API key so the browser never needs direct access to the backend.
 *
 * @package    local_softsysvideo
 * @copyright  2026 SoftSys Solutions {@link https://softsyssolutions.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');

require_login();
require_capability('local/softsysvideo:manage', context_system::instance());

$path = required_param('path', PARAM_PATH);

if (!preg_match('#^/api/(moodle/)?support/image/[a-zA-Z0-9/_.-]+$#', $path)) {
    send_header_404();
    die();
}

try {
    $client = \local_softsysvideo\api_client::from_config();
    $result = $client->get_binary($path);
} catch (\Exception $e) {
    send_header_404();
    die();
}

$contenttype = $result['content_type'] ?? 'application/octet-stream';

// Only serve image content types.
if (strpos($contenttype, 'image/') !== 0) {
    send_header_404();
    die();
}

header('Content-Type: ' . $contenttype);
header('Cache-Control: private, max-age=86400');
header('X-Content-Type-Options: nosniff');
header('Content-Length: ' . strlen($result['content']));
echo $result['content'];
