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
 * API client class for the SoftSys Video plugin endpoints.
 *
 * @package    local_softsysvideo
 * @copyright  2026 SoftSys Solutions {@link https://softsyssolutions.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_softsysvideo;

defined('MOODLE_INTERNAL') || die();

/**
 * API client for the SoftSys Video plugin endpoints.
 */
class api_client {
    /** @var string */
    private string $api_url;

    /** @var string */
    private string $plugin_key;

    /** @var array */
    private array $last_health = [];

    /**
     * @param string $api_url
     * @param string $plugin_key
     */
    public function __construct(string $api_url, string $plugin_key) {
        $this->api_url = rtrim($api_url, '/');
        $this->plugin_key = trim($plugin_key);
    }

    /**
     * Test the tenant connection using the health endpoint.
     *
     * @return bool
     */
    public function test_connection(): bool {
        $response = $this->make_request('GET', '/health');
        $this->last_health = $response['body'];
        return !empty($response['ok']);
    }

    /**
     * Return the most recent health payload.
     *
     * @return array
     */
    public function get_last_health(): array {
        return $this->last_health;
    }

    /**
     * Get current credits and balance data.
     *
     * @return array
     */
    public function get_credits(): array {
        $response = $this->make_request('GET', '/api/plugin/credits');
        return $response['body'];
    }

    /**
     * Get current usage data.
     *
     * @return array
     */
    public function get_usage(): array {
        $response = $this->make_request('GET', '/api/plugin/usage');
        return $response['body'];
    }

    /**
     * Get meeting records with optional filters.
     *
     * @param array $params
     * @return array
     */
    public function get_meetings(array $params = []): array {
        $response = $this->make_request('GET', '/api/plugin/meetings', $params);
        return $response['body'];
    }

    /**
     * Get meeting chat content or export payload.
     *
     * @param string $session_id
     * @return string|null
     */
    public function get_meeting_chat(string $session_id): ?string {
        $response = $this->make_request('GET', '/api/plugin/meetings/' . rawurlencode($session_id) . '/chat');

        if (isset($response['body']['raw']) && is_string($response['body']['raw'])) {
            return $response['body']['raw'];
        }

        if (isset($response['body']['chat']) && is_string($response['body']['chat'])) {
            return $response['body']['chat'];
        }

        if (isset($response['body']['download_url']) && is_string($response['body']['download_url'])) {
            return $response['body']['download_url'];
        }

        return null;
    }

    /**
     * Get the AI summary payload for a meeting.
     *
     * @param string $session_id
     * @return array|null
     */
    public function get_meeting_summary(string $session_id): ?array {
        $response = $this->make_request('GET', '/api/plugin/meetings/' . rawurlencode($session_id) . '/summary');
        return $response['body'] ?: null;
    }

    /**
     * Create a support ticket for the current tenant.
     *
     * @param array $data
     * @return array
     */
    public function create_support_ticket(array $data): array {
        $response = $this->make_request('POST', '/api/plugin/support/ticket', $data);
        return $response['body'];
    }

    /**
     * Execute an HTTP request with Moodle's curl class.
     *
     * @param string $method
     * @param string $path
     * @param array $data
     * @return array
     */
    private function make_request(string $method, string $path, array $data = []): array {
        global $CFG;

        require_once($CFG->libdir . '/filelib.php');

        $method = strtoupper($method);
        $url = $this->api_url . $path;
        $headers = [
            'Accept: application/json',
            'X-Plugin-Key: ' . $this->plugin_key,
        ];

        $options = [
            'CURLOPT_RETURNTRANSFER' => true,
            'CURLOPT_TIMEOUT' => 20,
            'CURLOPT_FOLLOWLOCATION' => false,
            'CURLOPT_HTTPHEADER' => $headers,
        ];

        $curl = new \curl();

        if ($method === 'GET') {
            $rawbody = $curl->get($url, $data, $options);
        } else if ($method === 'POST') {
            $headers[] = 'Content-Type: application/json';
            $options['CURLOPT_HTTPHEADER'] = $headers;
            $rawbody = $curl->post($url, json_encode($data), $options);
        } else {
            throw new \moodle_exception('unsupportedmethod', 'error', '', $method);
        }

        $info = $curl->get_info();
        $httpcode = (int)($info['http_code'] ?? 0);

        if ($rawbody === false || $httpcode === 0 || $httpcode >= 400) {
            throw new \moodle_exception('connection_failed', 'local_softsysvideo');
        }

        $body = [];
        if (is_string($rawbody) && $rawbody !== '') {
            $decoded = json_decode($rawbody, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $body = $decoded;
            } else {
                $body = ['raw' => $rawbody];
            }
        }

        return [
            'ok' => $httpcode >= 200 && $httpcode < 300,
            'status' => $httpcode,
            'body' => $body,
        ];
    }
}
