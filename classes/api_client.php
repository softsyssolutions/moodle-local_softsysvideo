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
 * API client for SoftSys Video external service.
 *
 * Centralises all HTTP communication with the SoftSys Video API so that
 * the Bearer token never leaves the server.
 *
 * @package    local_softsysvideo
 * @copyright  2026 SoftSys Solutions {@link https://softsyssolutions.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_softsysvideo;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/filelib.php');

/**
 * Thin wrapper around the SoftSys Video REST API.
 */
class api_client {

    /** @var string Base URL of the API (e.g. https://api.softsysvideo.com). */
    private string $apiurl;

    /** @var string Bearer token (plugin key). */
    private string $pluginkey;

    /**
     * Build a client from saved plugin configuration.
     *
     * @return self
     * @throws \moodle_exception When the plugin is not connected.
     */
    public static function from_config(): self {
        $apiurl = get_config('local_softsysvideo', 'softsysvideo_api_url');
        $key    = get_config('local_softsysvideo', 'softsysvideo_plugin_key');
        if (empty($apiurl) || empty($key)) {
            throw new \moodle_exception('not_connected', 'local_softsysvideo');
        }
        return new self($apiurl, $key);
    }

    /**
     * Constructor.
     *
     * @param string $apiurl  Base API URL.
     * @param string $pluginkey Bearer token.
     */
    public function __construct(string $apiurl, string $pluginkey) {
        $this->apiurl    = rtrim($apiurl, '/');
        $this->pluginkey = $pluginkey;
    }

    /**
     * Perform a GET request.
     *
     * @param string $path  API path (e.g. /api/moodle/stats).
     * @param array  $params Query parameters.
     * @return array Decoded JSON response.
     * @throws \moodle_exception On HTTP errors.
     */
    public function get(string $path, array $params = []): array {
        $url = $this->apiurl . $path;
        if (!empty($params)) {
            $url .= '?' . http_build_query($params, '', '&');
        }

        $curl = new \curl();
        $curl->setHeader([
            'Authorization: Bearer ' . $this->pluginkey,
            'Accept: application/json',
        ]);

        $response = $curl->get($url);
        $httpcode = $curl->get_info()['http_code'] ?? 0;

        if ($httpcode < 200 || $httpcode >= 300) {
            throw new \moodle_exception('connection_error_http', 'local_softsysvideo', '', $httpcode);
        }

        $decoded = json_decode($response, true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Perform a POST request with a JSON body.
     *
     * @param string $path  API path.
     * @param array  $body  Request body (will be JSON-encoded).
     * @return array Decoded JSON response.
     * @throws \moodle_exception On HTTP errors.
     */
    public function post(string $path, array $body = []): array {
        $url = $this->apiurl . $path;
        $payload = json_encode($body);

        $curl = new \curl();
        $curl->setHeader([
            'Authorization: Bearer ' . $this->pluginkey,
            'Content-Type: application/json',
            'Accept: application/json',
        ]);

        $response = $curl->post($url, $payload);
        $httpcode = $curl->get_info()['http_code'] ?? 0;

        if ($httpcode < 200 || $httpcode >= 300) {
            throw new \moodle_exception('connection_error_http', 'local_softsysvideo', '', $httpcode);
        }

        $decoded = json_decode($response, true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Fetch a binary resource (e.g. image attachment) and return its blob.
     *
     * @param string $path API path for the resource.
     * @return array{content: string, content_type: string}
     * @throws \moodle_exception On HTTP errors.
     */
    public function get_binary(string $path): array {
        $url = $this->apiurl . $path;

        $curl = new \curl();
        $curl->setHeader([
            'Authorization: Bearer ' . $this->pluginkey,
        ]);

        $response = $curl->get($url);
        $info     = $curl->get_info();
        $httpcode = $info['http_code'] ?? 0;

        if ($httpcode < 200 || $httpcode >= 300) {
            throw new \moodle_exception('connection_error_http', 'local_softsysvideo', '', $httpcode);
        }

        return [
            'content'      => $response,
            'content_type' => $info['content_type'] ?? 'application/octet-stream',
        ];
    }
}
