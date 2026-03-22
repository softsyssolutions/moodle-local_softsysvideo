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
 * BBB configurator class for local_softsysvideo.
 *
 * @package    local_softsysvideo
 * @copyright  2026 SoftSys Solutions {@link https://softsyssolutions.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_softsysvideo;

defined('MOODLE_INTERNAL') || die();

/**
 * Configures mod_bigbluebutton without conflicting with its own settings namespace.
 */
class bbb_configurator {
    /**
     * Check whether mod_bigbluebutton is installed.
     *
     * @return bool
     */
    public function is_bbb_installed(): bool {
        $pluginmanager = \core_plugin_manager::instance();
        $plugininfo = $pluginmanager->get_plugin_info('mod_bigbluebutton');
        return $plugininfo !== null && $plugininfo->is_installed_and_upgraded();
    }

    /**
     * Read the current BBB connection settings.
     *
     * @return array
     */
    public function get_current_bbb_config(): array {
        return [
            'server_url' => (string)get_config('bigbluebutton', 'server_url'),
            'shared_secret' => (string)get_config('bigbluebutton', 'shared_secret'),
        ];
    }

    /**
     * Write BBB connection settings through Moodle's config API.
     *
     * @param string $server_url
     * @param string $shared_secret
     * @return bool
     */
    public function configure_bbb(string $server_url, string $shared_secret): bool {
        if (!$this->is_bbb_installed()) {
            return false;
        }

        set_config('server_url', rtrim($server_url, '/'), 'bigbluebutton');
        set_config('shared_secret', trim($shared_secret), 'bigbluebutton');

        return true;
    }

    /**
     * Determine whether the current BBB URL points at SoftSys Video.
     *
     * @param string $api_url
     * @return bool
     */
    public function is_configured_for_softsysvideo(string $api_url): bool {
        $current = $this->get_current_bbb_config();
        $currenturl = rtrim($current['server_url'], '/');
        $targeturl = rtrim($api_url, '/');

        return $currenturl !== '' && strpos($currenturl, $targeturl) === 0;
    }
}
