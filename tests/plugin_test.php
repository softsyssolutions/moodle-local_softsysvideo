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
 * Basic plugin tests.
 *
 * @package    local_softsysvideo
 * @covers     \local_softsysvideo\privacy\provider
 * @covers     \local_softsysvideo\output\plugin_navigation
 * @covers     \local_softsysvideo\api_client
 * @copyright  2026 SoftSys Solutions {@link https://softsyssolutions.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_softsysvideo;

/**
 * Basic smoke tests for local_softsysvideo.
 *
 * @package    local_softsysvideo
 * @copyright  2026 SoftSys Solutions
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class plugin_test extends \advanced_testcase {
    /**
     * Test that the plugin is installed correctly.
     *
     * @covers \core_plugin_manager::instance
     */
    public function test_plugin_installed(): void {
        $this->resetAfterTest();
        $plugininfo = \core_plugin_manager::instance()->get_plugin_info('local_softsysvideo');
        $this->assertNotNull($plugininfo, 'Plugin local_softsysvideo should be installed.');
        $this->assertTrue($plugininfo->is_installed_and_upgraded());
    }

    /**
     * Test privacy provider returns a non-empty metadata reason string.
     *
     * @covers \local_softsysvideo\privacy\provider::get_reason
     */
    public function test_privacy_provider(): void {
        $this->resetAfterTest();
        $reason = \local_softsysvideo\privacy\provider::get_reason();
        $this->assertNotEmpty($reason);
        $this->assertIsString($reason);
    }

    /**
     * Test capability definitions exist.
     *
     * @covers \has_capability
     */
    public function test_capabilities_defined(): void {
        $this->resetAfterTest();
        $context = \context_system::instance();
        $result = has_capability('local/softsysvideo:manage', $context, get_admin());
        $this->assertIsBool($result);
    }

    /**
     * Test admin has the manage capability.
     *
     * @covers \has_capability
     */
    public function test_admin_has_manage_capability(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $context = \context_system::instance();
        $this->assertTrue(
            has_capability('local/softsysvideo:manage', $context),
            'Admin should have local/softsysvideo:manage capability.'
        );
    }

    /**
     * Test all three capabilities have corresponding lang strings.
     *
     * @covers \get_string
     */
    public function test_capability_lang_strings(): void {
        $this->resetAfterTest();
        $caps = ['manage', 'viewanalytics', 'viewcredits'];
        foreach ($caps as $cap) {
            $str = get_string('softsysvideo:' . $cap, 'local_softsysvideo');
            $this->assertNotEmpty($str, "Lang string for softsysvideo:{$cap} should exist.");
        }
    }

    /**
     * Test plugin navigation renderable exports correct tab structure.
     *
     * @covers \local_softsysvideo\output\plugin_navigation
     */
    public function test_plugin_navigation_export(): void {
        global $PAGE;
        $this->resetAfterTest();

        $nav = new \local_softsysvideo\output\plugin_navigation('dashboard');
        $data = $nav->export_for_template($PAGE->get_renderer('core'));

        $this->assertArrayHasKey('tabs', $data);
        $this->assertCount(5, $data['tabs']);

        $activecount = 0;
        foreach ($data['tabs'] as $tab) {
            $this->assertArrayHasKey('id', $tab);
            $this->assertArrayHasKey('label', $tab);
            $this->assertArrayHasKey('url', $tab);
            $this->assertArrayHasKey('active', $tab);
            if ($tab['active']) {
                $activecount++;
                $this->assertEquals('dashboard', $tab['id']);
            }
        }
        $this->assertEquals(1, $activecount, 'Exactly one tab should be active.');
    }

    /**
     * Test API client constructor and that from_config throws when not connected.
     *
     * @covers \local_softsysvideo\api_client::from_config
     */
    public function test_api_client_not_connected(): void {
        $this->resetAfterTest();
        unset_config('softsysvideo_plugin_key', 'local_softsysvideo');
        unset_config('softsysvideo_api_url', 'local_softsysvideo');

        $this->expectException(\moodle_exception::class);
        \local_softsysvideo\api_client::from_config();
    }

    /**
     * Test that web services are properly defined.
     *
     * @covers \local_softsysvideo\external\get_stats
     */
    public function test_services_defined(): void {
        $this->resetAfterTest();
        $expected = [
            'local_softsysvideo_get_stats',
            'local_softsysvideo_get_analytics',
            'local_softsysvideo_get_recordings',
            'local_softsysvideo_get_meetings',
            'local_softsysvideo_get_meeting_participants',
            'local_softsysvideo_get_tickets',
            'local_softsysvideo_get_ticket_detail',
            'local_softsysvideo_create_ticket',
        ];

        $servicespath = __DIR__ . '/../db/services.php';
        $this->assertFileExists($servicespath);
        $functions = [];
        require($servicespath);
        $this->assertNotEmpty($functions, 'External functions should be defined in db/services.php.');

        foreach ($expected as $funcname) {
            $this->assertArrayHasKey(
                $funcname,
                $functions,
                "External function {$funcname} should be defined in db/services.php."
            );
            $this->assertTrue(
                !empty($functions[$funcname]['ajax']),
                "External function {$funcname} should be AJAX-enabled."
            );
        }
    }

    /**
     * Test that the helper function renders navigation HTML.
     *
     * @covers ::local_softsysvideo_render_navigation
     */
    public function test_render_navigation(): void {
        global $PAGE, $CFG;
        $this->resetAfterTest();
        $PAGE->set_context(\context_system::instance());
        $PAGE->set_url(new \moodle_url('/local/softsysvideo/dashboard.php'));

        require_once($CFG->dirroot . '/local/softsysvideo/lib.php');
        $html = \local_softsysvideo_render_navigation('dashboard');
        $this->assertStringContainsString('nav-tabs', $html);
        $this->assertStringContainsString('active', $html);
        $this->assertStringContainsString('dashboard.php', $html);
    }
}
