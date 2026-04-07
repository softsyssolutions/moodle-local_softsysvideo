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
 * Support ticket list AMD module for local_softsysvideo.
 * Loads the ticket list via Moodle AJAX, renders the table,
 * handles pagination, and manages the create-ticket form.
 *
 * @module     local_softsysvideo/support_list
 * @copyright  2026 SoftSys Solutions {@link https://softsyssolutions.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['core/ajax'], function(Ajax) {

    var wwwroot = '';
    var strings = {};
    var limit = 20;

    /**
     * Map a ticket status value to a Bootstrap badge class.
     *
     * @param {string} status
     * @return {string} Bootstrap badge CSS classes
     */
    function statusBadgeClass(status) {
        switch (status) {
            case 'new':         return 'badge bg-primary';
            case 'in_progress': return 'badge bg-warning text-dark';
            case 'answered':    return 'badge bg-success';
            case 'resolved':    return 'badge bg-success';
            default:            return 'badge bg-secondary';
        }
    }

    /**
     * Show the loading spinner and hide the content container.
     */
    function showSpinner() {
        var spinner = document.getElementById('ssv-support-spinner');
        if (spinner) { spinner.classList.remove('d-none'); }
        var container = document.getElementById('ssv-support-container');
        if (container) { container.classList.add('d-none'); }
    }

    /**
     * Hide the loading spinner.
     */
    function hideSpinner() {
        var spinner = document.getElementById('ssv-support-spinner');
        if (spinner) { spinner.classList.add('d-none'); }
    }

    /**
     * Render the Previous / Next pagination controls.
     *
     * @param {number} offset  Current offset.
     * @param {number} total   Total number of tickets.
     */
    function renderPagination(offset, total) {
        var pag = document.getElementById('ssv-support-pagination');
        if (!pag) { return; }

        while (pag.firstChild) {
            pag.removeChild(pag.firstChild);
        }

        var prevBtn = document.createElement('button');
        prevBtn.className = 'btn btn-sm btn-outline-secondary';
        if (offset <= 0) { prevBtn.disabled = true; }
        prevBtn.textContent = '\u2039 ' + (strings.previous || 'Previous');
        pag.appendChild(prevBtn);

        var nextBtn = document.createElement('button');
        nextBtn.className = 'btn btn-sm btn-outline-secondary';
        if ((offset + limit) >= total) { nextBtn.disabled = true; }
        nextBtn.textContent = (strings.next || 'Next') + ' \u203a';
        pag.appendChild(nextBtn);

        if (offset > 0) {
            prevBtn.addEventListener('click', function() {
                loadTickets(offset - limit);
            });
        }
        if ((offset + limit) < total) {
            nextBtn.addEventListener('click', function() {
                loadTickets(offset + limit);
            });
        }
    }

    /**
     * Render the ticket rows into the table body.
     *
     * @param {Array} tickets  Array of ticket objects.
     */
    function renderTable(tickets) {
        var tbody = document.getElementById('ssv-support-tbody');
        if (!tbody) { return; }
        while (tbody.firstChild) {
            tbody.removeChild(tbody.firstChild);
        }

        if (!tickets || tickets.length === 0) {
            var emptyRow = document.createElement('tr');
            var emptyCell = document.createElement('td');
            emptyCell.setAttribute('colspan', '4');
            emptyCell.className = 'text-center text-muted';
            emptyCell.textContent = strings.no_tickets || 'No support tickets found.';
            emptyRow.appendChild(emptyCell);
            tbody.appendChild(emptyRow);
            return;
        }

        tickets.forEach(function(ticket) {
            var tr = document.createElement('tr');

            var tdSubject = document.createElement('td');
            var link = document.createElement('a');
            link.href = wwwroot + '/local/softsysvideo/support_detail.php?id=' + encodeURIComponent(ticket.id);
            link.textContent = ticket.subject || '\u2014';
            tdSubject.appendChild(link);
            tr.appendChild(tdSubject);

            var tdStatus = document.createElement('td');
            var badge = document.createElement('span');
            badge.className = statusBadgeClass(ticket.status);
            badge.textContent = ticket.status || '\u2014';
            tdStatus.appendChild(badge);
            tr.appendChild(tdStatus);

            var tdPriority = document.createElement('td');
            tdPriority.textContent = ticket.priority || '\u2014';
            tr.appendChild(tdPriority);

            var tdDate = document.createElement('td');
            tdDate.textContent = ticket.created_at ? new Date(ticket.created_at).toLocaleString() : '\u2014';
            tr.appendChild(tdDate);

            tbody.appendChild(tr);
        });
    }

    /**
     * Fetch tickets via Moodle AJAX and update the page.
     *
     * @param {number} offset  Pagination offset.
     */
    function loadTickets(offset) {
        showSpinner();

        Ajax.call([{
            methodname: 'local_softsysvideo_get_tickets',
            args: {limit: limit, offset: offset}
        }])[0].then(function(data) {
            hideSpinner();
            var tickets = data.tickets || [];
            var total = data.total || 0;

            var errAlert = document.getElementById('ssv-support-error');
            if (errAlert) { errAlert.classList.add('d-none'); }
            var successAlert = document.getElementById('ssv-support-success');
            if (successAlert) { successAlert.classList.add('d-none'); }

            var count = document.getElementById('ssv-support-count');
            if (count) { count.textContent = total + ' ticket(s)'; }

            renderTable(tickets);
            renderPagination(offset, total);

            var container = document.getElementById('ssv-support-container');
            if (container) { container.classList.remove('d-none'); }
            return;
        }).catch(function() {
            hideSpinner();
            var err = document.getElementById('ssv-support-error');
            if (err) { err.classList.remove('d-none'); }
        });
    }

    /**
     * Wire up the create-ticket form events.
     */
    function initCreateForm() {
        var createBtn = document.getElementById('ssv-support-create-btn');
        var formDiv   = document.getElementById('ssv-support-form');
        var cancelBtn = document.getElementById('ssv-ticket-cancel');
        var submitBtn = document.getElementById('ssv-ticket-submit');

        if (createBtn && formDiv) {
            createBtn.addEventListener('click', function() {
                formDiv.classList.toggle('d-none');
            });
        }

        if (cancelBtn && formDiv) {
            cancelBtn.addEventListener('click', function() {
                formDiv.classList.add('d-none');
            });
        }

        if (submitBtn) {
            submitBtn.addEventListener('click', function() {
                var subject     = document.getElementById('ssv-ticket-subject');
                var description = document.getElementById('ssv-ticket-description');
                var courseId    = document.getElementById('ssv-ticket-course-id');

                if (!subject || !subject.value.trim()) { return; }
                if (!description || !description.value.trim()) { return; }

                var errPrev = document.getElementById('ssv-support-error');
                if (errPrev) { errPrev.classList.add('d-none'); }
                var successPrev = document.getElementById('ssv-support-success');
                if (successPrev) { successPrev.classList.add('d-none'); }

                submitBtn.disabled = true;
                submitBtn.textContent = strings.submitting || 'Loading...';

                var args = {
                    subject: subject.value.trim(),
                    description: description.value.trim()
                };
                if (courseId && courseId.value.trim()) {
                    args.course_id = courseId.value.trim();
                }

                Ajax.call([{
                    methodname: 'local_softsysvideo_create_ticket',
                    args: args
                }])[0].then(function() {
                    if (subject)     { subject.value = ''; }
                    if (description) { description.value = ''; }
                    if (courseId)    { courseId.value = ''; }

                    var successDiv = document.getElementById('ssv-support-success');
                    if (successDiv) { successDiv.classList.remove('d-none'); }
                    if (formDiv)    { formDiv.classList.add('d-none'); }

                    submitBtn.disabled = false;
                    submitBtn.textContent = strings.submit_ticket || 'Submit ticket';

                    loadTickets(0);
                    return;
                }).catch(function() {
                    submitBtn.disabled = false;
                    submitBtn.textContent = strings.submit_ticket || 'Submit ticket';
                    var err = document.getElementById('ssv-support-error');
                    if (err) { err.classList.remove('d-none'); }
                });
            });
        }
    }

    return {
        /**
         * Initialise the support list page.
         *
         * @param {string} siteroot  Moodle wwwroot for building detail links.
         * @param {Object} strs      Translated UI strings from PHP.
         */
        init: function(siteroot, strs) {
            wwwroot   = siteroot;
            strings   = strs || {};

            initCreateForm();
            loadTickets(0);
        }
    };
});
