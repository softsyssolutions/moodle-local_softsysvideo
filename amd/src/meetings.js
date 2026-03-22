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
 * Meetings AMD module for local_softsysvideo.
 *
 * @module     local_softsysvideo/meetings
 * @copyright  2026 SoftSys Solutions {@link https://softsyssolutions.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['core/ajax', 'core/notification'], function(Ajax, Notification) {
    return {
        init: function(apiUrl, pluginKey) {
            fetch(apiUrl + '/api/moodle/meetings', {
                headers: {'Authorization': 'Bearer ' + pluginKey}
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                var meetings = data.meetings || data || [];
                if (!Array.isArray(meetings)) {
                    meetings = [];
                }
                var tbody = document.getElementById('ssv-meetings-tbody');
                var count = document.getElementById('ssv-meetings-count');
                var loading = document.getElementById('ssv-meetings-loading');
                var container = document.getElementById('ssv-meetings-container');
                if (loading) {
                    loading.classList.add('d-none');
                }
                if (!tbody) {
                    return;
                }
                if (meetings.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted">No hay reuniones recientes</td></tr>';
                    if (count) {
                        count.textContent = '0 reuni\u00f3n(es) encontrada(s).';
                    }
                } else {
                    if (count) {
                        count.textContent = meetings.length + ' reuni\u00f3n(es) encontrada(s).';
                    }
                    tbody.innerHTML = meetings.map(function(m) {
                        var date = m.started_at ? new Date(m.started_at * 1000).toLocaleString() : '\u2014';
                        var dur = m.duration_seconds ? Math.round(m.duration_seconds / 60) + ' min' : '\u2014';
                        var parts = m.participant_count !== undefined ? m.participant_count : '\u2014';
                        return '<tr><td>' + (m.name || '\u2014') + '</td><td>' + date + '</td><td>'
                            + dur + '</td><td>' + parts + '</td></tr>';
                    }).join('');
                }
                if (container) {
                    container.classList.remove('d-none');
                }
            })
            .catch(function() {
                var loading = document.getElementById('ssv-meetings-loading');
                if (loading) {
                    loading.classList.add('d-none');
                }
                var err = document.getElementById('ssv-meetings-error');
                if (err) {
                    err.classList.remove('d-none');
                }
            });
        }
    };
});
