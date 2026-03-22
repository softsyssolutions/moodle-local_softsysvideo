<?php
namespace local_softsysvideo;

defined("MOODLE_INTERNAL") || die();

/**
 * Manages the interaction with mod_bigbluebutton configuration.
 *
 * IMPORTANT: This class only writes to bigbluebutton config
 * during an EXPLICIT user action (clicking "Configure BBB").
 * It never auto-modifies BBB config on install or upgrade.
 * This ensures zero conflict with existing BBB installations.
 */
class bbb_configurator {

    /**
     * Check if mod_bigbluebutton is installed and enabled.
     */
    public function is_bbb_installed(): bool {
        $plugininfo = \core_plugin_manager::instance()->get_plugin_info("mod_bigbluebutton");
        return $plugininfo !== null && $plugininfo->is_installed_and_upgraded();
    }

    /**
     * Read the current BBB server configuration.
     * Returns the current server URL and shared secret (may be empty).
     */
    public function get_current_bbb_config(): array {
        return [
            "server_url"    => get_config("bigbluebutton", "server_url") ?: "",
            "shared_secret" => get_config("bigbluebutton", "shared_secret") ?: "",
        ];
    }

    /**
     * Configure mod_bigbluebutton to use SoftSys Video.
     *
     * Called ONLY on explicit user confirmation via setup.php.
     * Writes to bigbluebutton plugin config (same keys the BBB admin settings use).
     *
     * @param string $server_url    The SoftSys Video API URL (tenant subdomain)
     * @param string $shared_secret The BBB-compatible shared secret from SoftSys Video
     * @return bool True on success
     */
    public function configure_bbb(string $server_url, string $shared_secret): bool {
        set_config("server_url", $server_url, "bigbluebutton");
        set_config("shared_secret", $shared_secret, "bigbluebutton");

        // Purge the BBB plugin caches so the new config takes effect immediately
        if (function_exists("cache_helper::purge_by_definition")) {
            try {
                \cache_helper::purge_by_definition("mod_bigbluebutton", "serverinfo");
            } catch (\Exception $e) {
                // Non-critical — ignore if cache definition does not exist
            }
        }

        return true;
    }

    /**
     * Check if BBB is already pointing to a SoftSys Video API endpoint.
     *
     * @param string $api_url The expected SoftSys Video API URL
     */
    public function is_configured_for_softsysvideo(string $api_url): bool {
        $current = get_config("bigbluebutton", "server_url") ?: "";
        if (empty($current) || empty($api_url)) {
            return false;
        }
        $expectedHost = parse_url($api_url, PHP_URL_HOST);
        $currentHost  = parse_url($current, PHP_URL_HOST);
        return !empty($expectedHost) && $expectedHost === $currentHost;
    }
}
