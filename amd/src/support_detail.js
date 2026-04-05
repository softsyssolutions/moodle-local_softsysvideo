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
 * Support ticket detail AMD module for local_softsysvideo.
 * Fetches a single ticket from the external API and renders
 * the ticket card and message timeline.
 *
 * @module     local_softsysvideo/support_detail
 * @copyright  2026 SoftSys Solutions {@link https://softsyssolutions.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['core/ajax', 'core/notification'], function(Ajax, Notification) {

    var apiUrl = '';
    var pluginKey = '';

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
        var spinner = document.getElementById('ssv-detail-spinner');
        if (spinner) { spinner.classList.remove('d-none'); }
    }

    function hideSpinner() {
        var spinner = document.getElementById('ssv-detail-spinner');
        if (spinner) { spinner.classList.add('d-none'); }
    }

    /**
     * Populate the ticket info card from the ticket object.
     *
     * @param {Object} ticket  Ticket object returned by the API.
     */
    function renderTicketCard(ticket) {
        var subjectEl  = document.getElementById('ssv-detail-subject');
        var statusEl   = document.getElementById('ssv-detail-status');
        var priorityEl = document.getElementById('ssv-detail-priority');
        var dateEl     = document.getElementById('ssv-detail-date');

        if (subjectEl)  { subjectEl.textContent = ticket.subject || '\u2014'; }

        if (statusEl) {
            statusEl.innerHTML = '<span class="' + statusBadgeClass(ticket.status) + '">' +
                (ticket.status || '\u2014') + '</span>';
        }

        if (priorityEl) { priorityEl.textContent = ticket.priority || '\u2014'; }

        if (dateEl) {
            dateEl.textContent = ticket.created_at ?
                new Date(ticket.created_at).toLocaleString() : '\u2014';
        }

        var card = document.getElementById('ssv-detail-card');
        if (card) { card.classList.remove('d-none'); }
    }

    /**
     * Render the message timeline from the messages array.
     *
     * @param {Array} messages  Array of message objects returned by the API.
     */
    function renderMessages(messages) {
        var timeline = document.getElementById('ssv-detail-timeline');
        if (!timeline) { return; }

        if (!messages || messages.length === 0) {
            timeline.innerHTML = '<p class="text-muted">No hay mensajes en este ticket.</p>';
        } else {
            timeline.innerHTML = messages.map(function(msg) {
                var author = msg.author_name || msg.author_email || 'System';
                var date   = msg.created_at ? new Date(msg.created_at).toLocaleString() : '\u2014';
                var body   = msg.body || '';
                return '<div class="card mb-3">' +
                    '<div class="card-header d-flex justify-content-between align-items-center">' +
                    '<strong>' + author + '</strong>' +
                    '<small class="text-muted">' + date + '</small>' +
                    '</div>' +
                    '<div class="card-body">' + body + '</div>' +
                    '</div>';
            }).join('');
        }

        var messagesSection = document.getElementById('ssv-detail-messages');
        if (messagesSection) { messagesSection.classList.remove('d-none'); }
    }

    return {
        /**
         * Initialise the support detail page.
         *
         * @param {string} url       Base API URL.
         * @param {string} key       Plugin API key (Bearer token).
         * @param {number} ticketId  ID of the ticket to display.
         */
        init: function(url, key, ticketId) {
            apiUrl    = url;
            pluginKey = key;

            showSpinner();

            fetch(apiUrl + '/api/moodle/support/tickets/' + ticketId, {
                headers: {'Authorization': 'Bearer ' + pluginKey}
            })
            .then(function(r) {
                if (!r.ok) { throw new Error('HTTP ' + r.status); }
                return r.json();
            })
            .then(function(data) {
                hideSpinner();
                if (data.ticket) {
                    renderTicketCard(data.ticket);
                }
                renderMessages(data.messages || []);
            })
            .catch(function() {
                hideSpinner();
                var err = document.getElementById('ssv-detail-error');
                if (err) { err.classList.remove('d-none'); }
            });
        }
    };
});
