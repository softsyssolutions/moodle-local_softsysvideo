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
 * Supports pagination, search with debounce, and spinner.
 *
 * @module     local_softsysvideo/meetings
 * @copyright  2026 SoftSys Solutions {@link https://softsyssolutions.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['core/ajax', 'core/notification'], function(Ajax, Notification) {

    var apiUrl = '';
    var pluginKey = '';
    var strs = {};
    var currentPage = 1;
    var currentSearch = '';
    var debounceTimer = null;

    function buildFilterParams() {
        var params = '';
        var status = document.getElementById('ssv-filter-status');
        if (status && status.value) { params += '&status=' + encodeURIComponent(status.value); }
        var recording = document.getElementById('ssv-filter-recording');
        if (recording && recording.value) { params += '&has_recording=' + encodeURIComponent(recording.value); }
        var dateFrom = document.getElementById('ssv-filter-date-from');
        if (dateFrom && dateFrom.value) {
            params += '&date_from=' + Math.floor(new Date(dateFrom.value + 'T00:00:00').getTime() / 1000);
        }
        var dateTo = document.getElementById('ssv-filter-date-to');
        if (dateTo && dateTo.value) {
            params += '&date_to=' + Math.floor(new Date(dateTo.value + 'T23:59:59').getTime() / 1000);
        }
        var sortBy = document.getElementById('ssv-filter-sort-by');
        if (sortBy && sortBy.value) { params += '&sort_by=' + encodeURIComponent(sortBy.value); }
        var sortOrder = document.getElementById('ssv-filter-sort-order');
        if (sortOrder && sortOrder.value) { params += '&sort_order=' + encodeURIComponent(sortOrder.value); }
        return params;
    }

    function initFilters() {
        var applyBtn = document.getElementById('ssv-filter-apply');
        if (applyBtn) {
            applyBtn.addEventListener('click', function() {
                loadPage(1, currentSearch);
            });
        }
    }

    function showSpinner() {
        var spinner = document.getElementById('ssv-meetings-spinner');
        if (spinner) { spinner.classList.remove('d-none'); }
        var container = document.getElementById('ssv-meetings-container');
        if (container) { container.classList.add('d-none'); }
    }

    function hideSpinner() {
        var spinner = document.getElementById('ssv-meetings-spinner');
        if (spinner) { spinner.classList.add('d-none'); }
    }

    function renderPagination(page, totalPages) {
        var pag = document.getElementById('ssv-meetings-pagination');
        if (!pag) { return; }
        var prevDisabled = page <= 1 ? ' disabled' : '';
        var nextDisabled = page >= totalPages ? ' disabled' : '';
        pag.innerHTML =
            '<button class="btn btn-sm btn-outline-secondary' + prevDisabled + '" id="ssv-meet-prev">\u2039 Anterior</button>' +
            '<span class="mx-2 align-self-center">P\u00e1gina ' + page + ' de ' + totalPages + '</span>' +
            '<button class="btn btn-sm btn-outline-secondary' + nextDisabled + '" id="ssv-meet-next">Siguiente \u203a</button>';

        var prevBtn = document.getElementById('ssv-meet-prev');
        if (prevBtn && page > 1) {
            prevBtn.addEventListener('click', function() {
                loadPage(page - 1, currentSearch);
            });
        }
        var nextBtn = document.getElementById('ssv-meet-next');
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

        var url = apiUrl + '/api/moodle/meetings?page=' + page + '&per_page=20';
        if (search) { url += '&search=' + encodeURIComponent(search); }
        url += buildFilterParams();

        fetch(url, {headers: {'Authorization': 'Bearer ' + pluginKey}})
        .then(function(r) { return r.json(); })
        .then(function(data) {
            hideSpinner();
            var meetings = data.meetings || [];
            var total = data.total || 0;
            var totalPages = data.total_pages || 1;

            var tbody = document.getElementById('ssv-meetings-tbody');
            var count = document.getElementById('ssv-meetings-count');
            var container = document.getElementById('ssv-meetings-container');

            if (count) { count.textContent = total + ' reuni\u00f3n(es) encontrada(s).'; }
            renderPagination(page, totalPages);

            if (!tbody) { return; }

            if (meetings.length === 0) {
                tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted">No hay reuniones recientes</td></tr>';
            } else {
                tbody.innerHTML = meetings.map(function(m) {
                    var date = m.started_at ? new Date(m.started_at * 1000).toLocaleString() : '\u2014';
                    var dur = m.duration_seconds ? Math.round(m.duration_seconds / 60) + ' min' : '\u2014';
                    var parts = m.participant_count !== undefined ? m.participant_count : '\u2014';
                    return '<tr><td>' + (m.name || '\u2014') + '</td><td>' + date + '</td><td>' +
                        dur + '</td><td>' + parts + '</td></tr>';
                }).join('');
            }

            if (container) { container.classList.remove('d-none'); }
        })
        .catch(function() {
            hideSpinner();
            var err = document.getElementById('ssv-meetings-error');
            if (err) { err.classList.remove('d-none'); }
        });
    }

    return {
        init: function(url, key, filterStrs) {
            apiUrl = url;
            pluginKey = key;
            strs = filterStrs || {};

            var searchInput = document.getElementById('ssv-meetings-search');
            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    clearTimeout(debounceTimer);
                    var val = searchInput.value;
                    debounceTimer = setTimeout(function() {
                        loadPage(1, val);
                    }, 400);
                });
            }

            initFilters();
            loadPage(1, '');
        }
    };
});
