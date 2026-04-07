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
    return {
        init: function() {
            Ajax.call([{
                methodname: 'local_softsysvideo_get_stats',
                args: {}
            }])[0].then(function(data) {
                var statMeetings = document.getElementById('ssv-stat-meetings');
                var statHours = document.getElementById('ssv-stat-hours');
                var statParticipants = document.getElementById('ssv-stat-participants');
                var statRecordings = document.getElementById('ssv-stat-recordings');
                if (statMeetings) {
                    statMeetings.textContent = data.meetings !== undefined ? data.meetings : '\u2014';
                }
                if (statHours) {
                    statHours.textContent = data.total_hours !== undefined ? data.total_hours : '\u2014';
                }
                if (statParticipants) {
                    statParticipants.textContent = data.participants !== undefined ? data.participants : '\u2014';
                }
                if (statRecordings) {
                    statRecordings.textContent = data.recordings !== undefined ? data.recordings : '\u2014';
                }
                if (data.tenant_name) {
                    var el = document.getElementById('ssv-tenant-name');
                    if (el) {
                        el.innerHTML = 'Connected &mdash; ' + data.tenant_name;
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
