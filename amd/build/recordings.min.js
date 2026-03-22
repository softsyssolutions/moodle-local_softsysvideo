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
 * Supports pagination, search with debounce, and spinner.
 *
 * @module     local_softsysvideo/recordings
 * @copyright  2026 SoftSys Solutions {@link https://softsyssolutions.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['core/ajax', 'core/notification'], function(Ajax, Notification) {

    var apiUrl = '';
    var pluginKey = '';
    var currentPage = 1;
    var currentSearch = '';
    var debounceTimer = null;

    function showSpinner() {
        var spinner = document.getElementById('ssv-recordings-spinner');
        if (spinner) { spinner.classList.remove('d-none'); }
        var container = document.getElementById('ssv-recordings-container');
        if (container) { container.classList.add('d-none'); }
    }

    function hideSpinner() {
        var spinner = document.getElementById('ssv-recordings-spinner');
        if (spinner) { spinner.classList.add('d-none'); }
    }

    function renderPagination(page, totalPages) {
        var pag = document.getElementById('ssv-recordings-pagination');
        if (!pag) { return; }
        var prevDisabled = page <= 1 ? ' disabled' : '';
        var nextDisabled = page >= totalPages ? ' disabled' : '';
        pag.innerHTML =
            '<button class="btn btn-sm btn-outline-secondary' + prevDisabled + '" id="ssv-rec-prev">\u2039 Anterior</button>' +
            '<span class="mx-2 align-self-center">P\u00e1gina ' + page + ' de ' + totalPages + '</span>' +
            '<button class="btn btn-sm btn-outline-secondary' + nextDisabled + '" id="ssv-rec-next">Siguiente \u203a</button>';

        var prevBtn = document.getElementById('ssv-rec-prev');
        if (prevBtn && page > 1) {
            prevBtn.addEventListener('click', function() {
                loadPage(page - 1, currentSearch);
            });
        }
        var nextBtn = document.getElementById('ssv-rec-next');
        if (nextBtn && page < totalPages) {
            nextBtn.addEventListener('click', function() {
                loadPage(page + 1, currentSearch);
            });
        }
    }

    function loadPage(page, search) {
        currentPage = page;
        currentSearch = search;
        showSpinner();

        var url = apiUrl + '/api/moodle/recordings?page=' + page + '&per_page=20';
        if (search) { url += '&search=' + encodeURIComponent(search); }

        fetch(url, {headers: {'Authorization': 'Bearer ' + pluginKey}})
        .then(function(r) { return r.json(); })
        .then(function(data) {
            hideSpinner();
            var recordings = data.recordings || [];
            var total = data.total || 0;
            var totalPages = data.total_pages || 1;

            var tbody = document.getElementById('ssv-recordings-tbody');
            var count = document.getElementById('ssv-recordings-count');
            var container = document.getElementById('ssv-recordings-container');

            if (count) { count.textContent = total + ' grabaci\u00f3n(es) encontrada(s).'; }
            renderPagination(page, totalPages);

            if (!tbody) { return; }

            if (recordings.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">No hay grabaciones disponibles</td></tr>';
            } else {
                tbody.innerHTML = recordings.map(function(rec) {
                    var date = rec.created_at ? new Date(rec.created_at * 1000).toLocaleDateString() : '\u2014';
                    var dur = rec.duration_seconds ? Math.round(rec.duration_seconds / 60) + ' min' : '\u2014';
                    var size = rec.size_bytes ? (rec.size_bytes / 1048576).toFixed(1) + ' MB' : '\u2014';
                    var play = (rec.url || rec.playback_url)
                        ? '<a href="' + (rec.url || rec.playback_url) + '" target="_blank" class="btn btn-sm btn-outline-primary">\u25b6 Reproducir</a>'
                        : '\u2014';
                    return '<tr><td>' + (rec.name || '\u2014') + '</td><td>' +
                        (rec.meeting_name || rec.meeting || '\u2014') + '</td><td>' +
                        date + '</td><td>' + dur + '</td><td>' + size + '</td><td>' + play + '</td></tr>';
                }).join('');
            }

            if (container) { container.classList.remove('d-none'); }
        })
        .catch(function() {
            hideSpinner();
            var err = document.getElementById('ssv-recordings-error');
            if (err) { err.classList.remove('d-none'); }
        });
    }

    return {
        init: function(url, key) {
            apiUrl = url;
            pluginKey = key;

            var searchInput = document.getElementById('ssv-recordings-search');
            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    clearTimeout(debounceTimer);
                    var val = searchInput.value;
                    debounceTimer = setTimeout(function() {
                        loadPage(1, val);
                    }, 400);
                });
            }

            loadPage(1, '');
        }
    };
});
