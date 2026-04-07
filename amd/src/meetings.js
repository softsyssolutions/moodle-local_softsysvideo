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
define(['core/ajax'], function(Ajax) {

    var strs = {};
    var currentSearch = '';
    var debounceTimer = null;

    /**
     * Build the filter arguments from the DOM filter controls.
     *
     * @return {Object} Filter key-value pairs.
     */
    function buildFilterArgs() {
        var args = {};
        var status = document.getElementById('ssv-filter-status');
        if (status && status.value) { args.status = status.value; }
        var recording = document.getElementById('ssv-filter-recording');
        if (recording && recording.value) { args.has_recording = recording.value; }
        var dateFrom = document.getElementById('ssv-filter-date-from');
        if (dateFrom && dateFrom.value) {
            args.date_from = Math.floor(new Date(dateFrom.value + 'T00:00:00').getTime() / 1000);
        }
        var dateTo = document.getElementById('ssv-filter-date-to');
        if (dateTo && dateTo.value) {
            args.date_to = Math.floor(new Date(dateTo.value + 'T23:59:59').getTime() / 1000);
        }
        var sortBy = document.getElementById('ssv-filter-sort-by');
        if (sortBy && sortBy.value) { args.sort_by = sortBy.value; }
        var sortOrder = document.getElementById('ssv-filter-sort-order');
        if (sortOrder && sortOrder.value) { args.sort_order = sortOrder.value; }
        return args;
    }

    /**
     * Bind click handler on the Apply Filters button.
     */
    function initFilters() {
        var applyBtn = document.getElementById('ssv-filter-apply');
        if (applyBtn) {
            applyBtn.addEventListener('click', function() {
                loadPage(1, currentSearch);
            });
        }
    }

    /**
     * Show the loading spinner and hide the content container.
     */
    function showSpinner() {
        var spinner = document.getElementById('ssv-meetings-spinner');
        if (spinner) { spinner.classList.remove('d-none'); }
        var container = document.getElementById('ssv-meetings-container');
        if (container) { container.classList.add('d-none'); }
    }

    /**
     * Hide the loading spinner.
     */
    function hideSpinner() {
        var spinner = document.getElementById('ssv-meetings-spinner');
        if (spinner) { spinner.classList.add('d-none'); }
    }

    /**
     * Render pagination controls.
     *
     * @param {number} page       Current page number.
     * @param {number} totalPages Total number of pages.
     */
    function renderPagination(page, totalPages) {
        var pag = document.getElementById('ssv-meetings-pagination');
        if (!pag) { return; }
        while (pag.firstChild) {
            pag.removeChild(pag.firstChild);
        }

        var prevBtn = document.createElement('button');
        prevBtn.className = 'btn btn-sm btn-outline-secondary';
        if (page <= 1) { prevBtn.disabled = true; }
        prevBtn.textContent = '\u2039 ' + (strs.previous || 'Previous');
        if (page > 1) {
            prevBtn.addEventListener('click', function() {
                loadPage(page - 1, currentSearch);
            });
        }
        pag.appendChild(prevBtn);

        var pageInfo = document.createElement('span');
        pageInfo.className = 'mx-2 align-self-center';
        pageInfo.textContent = (strs.page_x_of_y || 'Page {current} of {total}')
            .replace('{current}', page).replace('{total}', totalPages);
        pag.appendChild(pageInfo);

        var nextBtn = document.createElement('button');
        nextBtn.className = 'btn btn-sm btn-outline-secondary';
        if (page >= totalPages) { nextBtn.disabled = true; }
        nextBtn.textContent = (strs.next || 'Next') + ' \u203a';
        if (page < totalPages) {
            nextBtn.addEventListener('click', function() {
                loadPage(page + 1, currentSearch);
            });
        }
        pag.appendChild(nextBtn);
    }

    /**
     * Load a page of meetings from the server.
     *
     * @param {number} page   Page number to load.
     * @param {string} search Search query string.
     */
    function loadPage(page, search) {
        currentSearch = search;
        showSpinner();

        var args = buildFilterArgs();
        args.page = page;
        args.per_page = 20;
        if (search) { args.search = search; }

        Ajax.call([{
            methodname: 'local_softsysvideo_get_meetings',
            args: args
        }])[0].then(function(data) {
            hideSpinner();
            var meetings = data.meetings || [];
            var total = data.total || 0;
            var totalPages = data.total_pages || 1;

            var tbody = document.getElementById('ssv-meetings-tbody');
            var count = document.getElementById('ssv-meetings-count');
            var container = document.getElementById('ssv-meetings-container');

            if (count) { count.textContent = total + ' ' + (strs.total_meetings || 'meeting(s)'); }
            renderPagination(page, totalPages);

            if (!tbody) { return; }

            while (tbody.firstChild) {
                tbody.removeChild(tbody.firstChild);
            }
            if (meetings.length === 0) {
                var emptyRow = document.createElement('tr');
                var emptyCell = document.createElement('td');
                emptyCell.setAttribute('colspan', '4');
                emptyCell.className = 'text-center text-muted';
                emptyCell.textContent = strs.no_meetings || 'No meetings available.';
                emptyRow.appendChild(emptyCell);
                tbody.appendChild(emptyRow);
            } else {
                meetings.forEach(function(m) {
                    var tr = document.createElement('tr');

                    var tdName = document.createElement('td');
                    tdName.textContent = m.name || '\u2014';
                    tr.appendChild(tdName);

                    var tdDate = document.createElement('td');
                    tdDate.textContent = m.started_at
                        ? new Date(m.started_at * 1000).toLocaleString() : '\u2014';
                    tr.appendChild(tdDate);

                    var tdDur = document.createElement('td');
                    tdDur.textContent = m.duration_seconds
                        ? Math.round(m.duration_seconds / 60) + ' min' : '\u2014';
                    tr.appendChild(tdDur);

                    var tdParts = document.createElement('td');
                    tdParts.textContent = m.participant_count !== undefined
                        ? m.participant_count : '\u2014';
                    tr.appendChild(tdParts);

                    tbody.appendChild(tr);
                });
            }

            if (container) { container.classList.remove('d-none'); }
            return;
        }).catch(function() {
            hideSpinner();
            var err = document.getElementById('ssv-meetings-error');
            if (err) { err.classList.remove('d-none'); }
        });
    }

    return {
        init: function(filterStrs) {
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
