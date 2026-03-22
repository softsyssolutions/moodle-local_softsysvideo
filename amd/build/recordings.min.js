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
 * Recordings AMD module for local_softsysvideo.
 *
 * @module     local_softsysvideo/recordings
 * @copyright  2026 SoftSys Solutions {@link https://softsyssolutions.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['core/ajax', 'core/notification'], function(Ajax, Notification) {
    return {
        init: function(apiUrl, pluginKey) {
            fetch(apiUrl + '/api/moodle/recordings', {
                headers: {'Authorization': 'Bearer ' + pluginKey}
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                var recordings = data.recordings || data || [];
                if (!Array.isArray(recordings)) {
                    recordings = [];
                }
                var tbody = document.getElementById('ssv-recordings-tbody');
                var count = document.getElementById('ssv-recordings-count');
                var loading = document.getElementById('ssv-recordings-loading');
                var container = document.getElementById('ssv-recordings-container');
                if (loading) {
                    loading.classList.add('d-none');
                }
                if (!tbody) {
                    return;
                }
                if (recordings.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">No hay grabaciones disponibles</td></tr>';
                    if (count) {
                        count.textContent = '0 grabaci\u00f3n(es) encontrada(s).';
                    }
                } else {
                    if (count) {
                        count.textContent = recordings.length + ' grabaci\u00f3n(es) encontrada(s).';
                    }
                    tbody.innerHTML = recordings.map(function(rec) {
                        var date = rec.created_at ? new Date(rec.created_at * 1000).toLocaleDateString() : '\u2014';
                        var dur = rec.duration_seconds ? Math.round(rec.duration_seconds / 60) + ' min' : '\u2014';
                        var size = rec.size_bytes ? (rec.size_bytes / 1048576).toFixed(1) + ' MB' : '\u2014';
                        var play = (rec.url || rec.playback_url)
                            ? '<a href="' + (rec.url || rec.playback_url) + '" target="_blank" class="btn btn-sm btn-outline-primary">'
                              + '\u25b6 Reproducir</a>'
                            : '\u2014';
                        return '<tr><td>' + (rec.name || '\u2014') + '</td><td>'
                            + (rec.meeting_name || rec.meeting || '\u2014') + '</td><td>'
                            + date + '</td><td>' + dur + '</td><td>' + size + '</td><td>' + play + '</td></tr>';
                    }).join('');
                }
                if (container) {
                    container.classList.remove('d-none');
                }
            })
            .catch(function() {
                var loading = document.getElementById('ssv-recordings-loading');
                if (loading) {
                    loading.classList.add('d-none');
                }
                var err = document.getElementById('ssv-recordings-error');
                if (err) {
                    err.classList.remove('d-none');
                }
            });
        }
    };
});
