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
 * Dashboard AMD module for local_softsysvideo.
 *
 * @module     local_softsysvideo/dashboard
 * @copyright  2026 SoftSys Solutions {@link https://softsyssolutions.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['core/ajax'], function(Ajax) {

    /**
     * Set text content of an element by id, if present.
     * @param {string} id
     * @param {string|number} value
     */
    function setText(id, value) {
        var el = document.getElementById(id);
        if (el) {
            el.textContent = (value !== undefined && value !== null) ? String(value) : '\u2014';
        }
    }

    return {
        init: function() {
            Ajax.call([{
                methodname: 'local_softsysvideo_get_stats',
                args: {}
            }])[0].then(function(data) {

                // Core monthly KPIs.
                setText('ssv-stat-meetings', data.meetings);
                setText('ssv-stat-hours', data.total_hours);
                setText('ssv-stat-participants', data.participants);
                setText('ssv-stat-recordings', data.recordings);

                // Consumption data (v2).
                setText('ssv-stat-session-minutes', data.session_minutes !== undefined ? data.session_minutes + ' min' : '\u2014');
                setText('ssv-stat-recording-minutes', data.recording_minutes !== undefined ? data.recording_minutes + ' min' : '\u2014');

                // Tenant name badge.
                if (data.tenant_name) {
                    var tenantEl = document.getElementById('ssv-tenant-name');
                    if (tenantEl) {
                        tenantEl.innerHTML = 'Connected &mdash; ' + data.tenant_name;
                    }
                }

                return;
            }).catch(function() {
                var err = document.getElementById('ssv-stats-error');
                if (err) {
                    err.classList.remove('d-none');
                }
            });
        }
    };
});
