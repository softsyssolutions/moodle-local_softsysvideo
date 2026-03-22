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
define(['core/ajax', 'core/notification'], function(Ajax, Notification) {
    return {
        init: function(apiUrl, pluginKey) {
            fetch(apiUrl + '/api/moodle/stats', {
                headers: {'Authorization': 'Bearer ' + pluginKey}
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                var statMeetings = document.getElementById('ssv-stat-meetings');
                var statHours = document.getElementById('ssv-stat-hours');
                var statParticipants = document.getElementById('ssv-stat-participants');
                var statRecordings = document.getElementById('ssv-stat-recordings');
                if (statMeetings) {
                    statMeetings.textContent = (data.this_month && data.this_month.meetings !== undefined)
                        ? data.this_month.meetings : '\u2014';
                }
                if (statHours) {
                    statHours.textContent = (data.this_month && data.this_month.total_hours !== undefined)
                        ? data.this_month.total_hours : '\u2014';
                }
                if (statParticipants) {
                    statParticipants.textContent = (data.this_month && data.this_month.participants !== undefined)
                        ? data.this_month.participants : '\u2014';
                }
                if (statRecordings) {
                    statRecordings.textContent = (data.this_month && data.this_month.recordings !== undefined)
                        ? data.this_month.recordings : '\u2014';
                }
                if (data.tenant_name) {
                    var el = document.getElementById('ssv-tenant-name');
                    if (el) {
                        el.textContent = data.tenant_name;
                    }
                }
            })
            .catch(function() {
                var err = document.getElementById('ssv-stats-error');
                if (err) {
                    err.classList.remove('d-none');
                }
            });
        }
    };
});
