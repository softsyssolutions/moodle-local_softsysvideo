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
 * Loads the ticket list from the external API, renders the table,
 * handles pagination, and manages the create-ticket form.
 *
 * @module     local_softsysvideo/support_list
 * @copyright  2026 SoftSys Solutions {@link https://softsyssolutions.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['core/ajax', 'core/notification'], function(Ajax, Notification) {

    var apiUrl = '';
    var pluginKey = '';
    var wwwroot = '';
    var currentOffset = 0;
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

    function showSpinner() {
        var spinner = document.getElementById('ssv-support-spinner');
        if (spinner) { spinner.classList.remove('d-none'); }
        var container = document.getElementById('ssv-support-container');
        if (container) { container.classList.add('d-none'); }
    }

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

        var prevDisabled = offset <= 0 ? ' disabled' : '';
        var nextDisabled = (offset + limit) >= total ? ' disabled' : '';

        pag.innerHTML =
            '<button class="btn btn-sm btn-outline-secondary' + prevDisabled + '" id="ssv-sup-prev">' +
            '\u2039 Anterior</button>' +
            '<button class="btn btn-sm btn-outline-secondary' + nextDisabled + '" id="ssv-sup-next">' +
            'Siguiente \u203a</button>';

        var prevBtn = document.getElementById('ssv-sup-prev');
        if (prevBtn && offset > 0) {
            prevBtn.addEventListener('click', function() {
                loadTickets(offset - limit);
            });
        }
        var nextBtn = document.getElementById('ssv-sup-next');
        if (nextBtn && (offset + limit) < total) {
            nextBtn.addEventListener('click', function() {
                loadTickets(offset + limit);
            });
        }
    }

    /**
     * Render the ticket rows into the table body.
     *
     * @param {Array} tickets  Array of ticket objects from the API.
     */
    function renderTable(tickets) {
        var tbody = document.getElementById('ssv-support-tbody');
        if (!tbody) { return; }

        if (!tickets || tickets.length === 0) {
            tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted">No hay tickets de soporte.</td></tr>';
            return;
        }

        tbody.innerHTML = tickets.map(function(ticket) {
            var subjectLink = '<a href="' + wwwroot + '/local/softsysvideo/support_detail.php?id=' +
                encodeURIComponent(ticket.id) + '">' +
                (ticket.subject || '\u2014') + '</a>';
            var statusBadge = '<span class="' + statusBadgeClass(ticket.status) + '">' +
                (ticket.status || '\u2014') + '</span>';
            var priority = ticket.priority || '\u2014';
            var date = ticket.created_at ? new Date(ticket.created_at).toLocaleString() : '\u2014';
            return '<tr><td>' + subjectLink + '</td><td>' + statusBadge + '</td><td>' +
                priority + '</td><td>' + date + '</td></tr>';
        }).join('');
    }

    /**
     * Fetch tickets from the API and update the page.
     *
     * @param {number} offset  Pagination offset.
     */
    function loadTickets(offset) {
        currentOffset = offset;
        showSpinner();

        var url = apiUrl + '/api/moodle/support/tickets?limit=' + limit + '&offset=' + offset;

        fetch(url, {headers: {'Authorization': 'Bearer ' + pluginKey}})
        .then(function(r) {
            if (!r.ok) { throw new Error('HTTP ' + r.status); }
            return r.json();
        })
        .then(function(data) {
            hideSpinner();
            var tickets = data.tickets || [];
            var total = data.total || 0;

            var count = document.getElementById('ssv-support-count');
            if (count) { count.textContent = total + ' ticket(s) encontrado(s).'; }

            renderTable(tickets);
            renderPagination(offset, total);

            var container = document.getElementById('ssv-support-container');
            if (container) { container.classList.remove('d-none'); }
        })
        .catch(function() {
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

                submitBtn.disabled = true;
                submitBtn.textContent = 'Enviando...';

                var body = {
                    subject: subject.value.trim(),
                    description: description.value.trim(),
                    moodle_site_url: wwwroot
                };
                if (courseId && courseId.value.trim()) {
                    body.moodle_course_id = courseId.value.trim();
                }

                fetch(apiUrl + '/api/moodle/support/tickets', {
                    method: 'POST',
                    headers: {
                        'Authorization': 'Bearer ' + pluginKey,
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(body)
                })
                .then(function(r) {
                    if (!r.ok) { throw new Error('HTTP ' + r.status); }
                    return r.json();
                })
                .then(function() {
                    // Reset form fields.
                    if (subject)     { subject.value = ''; }
                    if (description) { description.value = ''; }
                    if (courseId)    { courseId.value = ''; }

                    // Show success, hide form.
                    var successDiv = document.getElementById('ssv-support-success');
                    if (successDiv) { successDiv.classList.remove('d-none'); }
                    if (formDiv)    { formDiv.classList.add('d-none'); }

                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Enviar ticket';

                    // Reload the list from the first page.
                    loadTickets(0);
                })
                .catch(function() {
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Enviar ticket';
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
         * @param {string} url       Base API URL.
         * @param {string} key       Plugin API key (Bearer token).
         * @param {string} siteroot  Moodle wwwroot for building detail links.
         */
        init: function(url, key, siteroot) {
            apiUrl    = url;
            pluginKey = key;
            wwwroot   = siteroot;

            initCreateForm();
            loadTickets(0);
        }
    };
});
