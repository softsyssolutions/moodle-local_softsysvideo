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
 * Plugin navigation renderable.
 *
 * @package    local_softsysvideo
 * @copyright  2026 SoftSys Solutions {@link https://softsyssolutions.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_softsysvideo\output;

use renderable;
use templatable;
use renderer_base;
use moodle_url;

/**
 * Navigation tabs for the plugin pages.
 */
class plugin_navigation implements renderable, templatable {
    /** @var string Current page identifier. */
    private string $activepage;

    /**
     * Constructor.
     *
     * @param string $activepage One of: dashboard, recordings, meetings, connection, support, support_detail.
     */
    public function __construct(string $activepage) {
        $this->activepage = $activepage;
    }

    /**
     * Export data for the Mustache template.
     *
     * @param renderer_base $output
     * @return array
     */
    public function export_for_template(renderer_base $output): array {
        $tabs = [
            [
                'id'     => 'dashboard',
                'label'  => get_string('dashboard', 'local_softsysvideo'),
                'url'    => (new moodle_url('/local/softsysvideo/dashboard.php'))->out(false),
                'active' => $this->activepage === 'dashboard',
            ],
            [
                'id'     => 'recordings',
                'label'  => get_string('recordings', 'local_softsysvideo'),
                'url'    => (new moodle_url('/local/softsysvideo/recordings.php'))->out(false),
                'active' => $this->activepage === 'recordings',
            ],
            [
                'id'     => 'meetings',
                'label'  => get_string('meetings', 'local_softsysvideo'),
                'url'    => (new moodle_url('/local/softsysvideo/meetings.php'))->out(false),
                'active' => $this->activepage === 'meetings',
            ],
            [
                'id'     => 'connection',
                'label'  => get_string('connection', 'local_softsysvideo'),
                'url'    => (new moodle_url('/local/softsysvideo/connect.php'))->out(false),
                'active' => $this->activepage === 'connection',
            ],
            [
                'id'     => 'support',
                'label'  => get_string('support', 'local_softsysvideo'),
                'url'    => (new moodle_url('/local/softsysvideo/support.php'))->out(false),
                'active' => ($this->activepage === 'support' || $this->activepage === 'support_detail'),
            ],
        ];

        return ['tabs' => $tabs];
    }
}
