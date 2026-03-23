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
     * Test privacy provider returns correct metadata reason.
     *
     * @covers \local_softsysvideo\privacy\provider::get_reason
     */
    public function test_privacy_provider(): void {
        $this->resetAfterTest();
        $reason = \local_softsysvideo\privacy\provider::get_reason();
        $this->assertEquals('privacy:metadata', $reason);
    }

    /**
     * Test capability definitions exist.
     *
     * @covers \has_capability
     */
    public function test_capabilities_defined(): void {
        $this->resetAfterTest();
        $context = \context_system::instance();
        // Simply verify the capability can be checked without error.
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
}
