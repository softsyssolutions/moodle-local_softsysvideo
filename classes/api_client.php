<?php
namespace local_softsysvideo;

defined("MOODLE_INTERNAL") || die();

/**
 * HTTP client for SoftSys Video API Worker.
 *
 * Authenticates with X-Plugin-Key header (per-tenant key).
 * All endpoints are read-only except support ticket creation.
 */
class api_client {

    private string $api_url;
    private string $plugin_key;

    public function __construct(string $api_url, string $plugin_key) {
        $this->api_url    = rtrim($api_url, "/");
        $this->plugin_key = $plugin_key;
    }

    /**
     * Test connectivity — calls /health (no auth required).
     */
    public function test_connection(): bool {
        try {
            $result = $this->make_request("GET", "/health");
            return isset($result["status"]) && $result["status"] === "ok";
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get credit balance and recent ledger.
     * Scope: credits:read
     */
    public function get_credits(): array {
        return $this->make_request("GET", "/api/plugin/credits");
    }

    /**
     * Get usage statistics for last 30 days.
     * Scope: analytics:read
     */
    public function get_usage(): array {
        return $this->make_request("GET", "/api/plugin/usage");
    }

    /**
     * List meetings with optional filters.
     * Scope: meetings:read
     *
     * @param array $params Optional: limit, offset, status (active|ended)
     */
    public function get_meetings(array $params = []): array {
        $qs = !empty($params) ? "?" . http_build_query($params) : "";
        return $this->make_request("GET", "/api/plugin/meetings" . $qs);
    }

    /**
     * Get chat download URL for a meeting.
     * Scope: meetings:read
     *
     * @return string|null Download URL or null if not available
     */
    public function get_meeting_chat(string $session_id): ?string {
        $result = $this->make_request("GET", "/api/plugin/meetings/{$session_id}/chat");
        return $result["download_url"] ?? null;
    }

    /**
     * Get AI summary for a meeting.
     * Scope: meetings:read
     *
     * @return array|null Array with status + summary_url, or null on error
     */
    public function get_meeting_summary(string $session_id): ?array {
        return $this->make_request("GET", "/api/plugin/meetings/{$session_id}/summary") ?: null;
    }

    /**
     * Create a support ticket with optional Moodle context.
     * Scope: support:write
     *
     * @param array $data Keys: subject, description, moodle_course_id?, moodle_site_url?, moodle_course_name?
     */
    public function create_support_ticket(array $data): array {
        return $this->make_request("POST", "/api/plugin/support/ticket", $data);
    }

    /**
     * Internal HTTP request using Moodle curl class.
     */
    private function make_request(string $method, string $path, array $data = []): array {
        global $CFG;
        require_once($CFG->libdir . "/filelib.php");

        $curl = new \curl();
        $curl->setHeader([
            "X-Plugin-Key: " . $this->plugin_key,
            "Content-Type: application/json",
            "Accept: application/json",
        ]);

        $url = $this->api_url . $path;

        if ($method === "GET") {
            $response = $curl->get($url);
        } else {
            $response = $curl->post($url, json_encode($data));
        }

        $info = $curl->get_info();
        $httpCode = $info["http_code"] ?? 0;

        if (!$response || $httpCode >= 400) {
            debugging("local_softsysvideo: API request failed [{$method} {$path}] HTTP {$httpCode}", DEBUG_DEVELOPER);
            return [];
        }

        return json_decode($response, true) ?? [];
    }
}
