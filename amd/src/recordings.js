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
define(['core/ajax'], function(Ajax) {

    var strs = {};
    var currentPage = 1;
    var currentSearch = '';
    var debounceTimer = null;

    function buildFilterArgs() {
        var args = {};
        var state = document.getElementById('ssv-filter-state');
        if (state && state.value) { args.state = state.value; }
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

    function initFilters() {
        var applyBtn = document.getElementById('ssv-filter-apply');
        if (applyBtn) {
            applyBtn.addEventListener('click', function() {
                loadPage(1, currentSearch);
            });
        }
    }

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

    function loadPage(page, search) {
        currentPage = page;
        currentSearch = search;
        showSpinner();

        var args = buildFilterArgs();
        args.page = page;
        args.per_page = 20;
        if (search) { args.search = search; }

        Ajax.call([{
            methodname: 'local_softsysvideo_get_recordings',
            args: args
        }])[0].then(function(data) {
            hideSpinner();
            var recordings = data.recordings || [];
            var total = data.total || 0;
            var totalPages = data.total_pages || 1;

            var tbody = document.getElementById('ssv-recordings-tbody');
            var count = document.getElementById('ssv-recordings-count');
            var container = document.getElementById('ssv-recordings-container');

            if (count) { count.textContent = total + ' ' + (strs.total_recordings || 'recording(s)'); }
            renderPagination(page, totalPages);

            if (!tbody) { return; }

            while (tbody.firstChild) {
                tbody.removeChild(tbody.firstChild);
            }
            if (recordings.length === 0) {
                var emptyRow = document.createElement('tr');
                var emptyCell = document.createElement('td');
                emptyCell.setAttribute('colspan', '6');
                emptyCell.className = 'text-center text-muted';
                emptyCell.textContent = strs.no_recordings || 'No recordings available.';
                emptyRow.appendChild(emptyCell);
                tbody.appendChild(emptyRow);
            } else {
                recordings.forEach(function(rec) {
                    var tr = document.createElement('tr');
                    var tdName = document.createElement('td');
                    tdName.textContent = rec.name || '\u2014';
                    tr.appendChild(tdName);

                    var tdMeeting = document.createElement('td');
                    tdMeeting.textContent = rec.meeting_name || '\u2014';
                    tr.appendChild(tdMeeting);

                    var tdDate = document.createElement('td');
                    tdDate.textContent = rec.created_at
                        ? new Date(rec.created_at * 1000).toLocaleDateString() : '\u2014';
                    tr.appendChild(tdDate);

                    var tdDur = document.createElement('td');
                    tdDur.textContent = rec.duration_seconds
                        ? Math.round(rec.duration_seconds / 60) + ' min' : '\u2014';
                    tr.appendChild(tdDur);

                    var tdSize = document.createElement('td');
                    tdSize.textContent = rec.size_bytes
                        ? (rec.size_bytes / 1048576).toFixed(1) + ' MB' : '\u2014';
                    tr.appendChild(tdSize);

                    var tdPlay = document.createElement('td');
                    if (rec.playback_url) {
                        var link = document.createElement('a');
                        link.href = rec.playback_url;
                        link.target = '_blank';
                        link.rel = 'noopener noreferrer';
                        link.className = 'btn btn-sm btn-outline-primary';
                        link.textContent = '\u25b6 ' + (strs.play || 'Play');
                        tdPlay.appendChild(link);
                    } else {
                        tdPlay.textContent = '\u2014';
                    }
                    tr.appendChild(tdPlay);

                    tbody.appendChild(tr);
                });
            }

            if (container) { container.classList.remove('d-none'); }
            return;
        }).catch(function() {
            hideSpinner();
            var err = document.getElementById('ssv-recordings-error');
            if (err) { err.classList.remove('d-none'); }
        });
    }

    return {
        init: function(filterStrs) {
            strs = filterStrs || {};

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

            initFilters();
            loadPage(1, '');
        }
    };
});
