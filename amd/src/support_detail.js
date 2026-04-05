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
    var strings = {};

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
            statusEl.innerHTML = '';
            var badge = document.createElement('span');
            badge.className = statusBadgeClass(ticket.status);
            badge.textContent = ticket.status || '\u2014';
            statusEl.appendChild(badge);
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
        timeline.innerHTML = '';

        if (!messages || messages.length === 0) {
            var emptyMsg = document.createElement('p');
            emptyMsg.className = 'text-muted';
            emptyMsg.textContent = strings.no_messages || 'No messages in this ticket.';
            timeline.appendChild(emptyMsg);
        } else {
            messages.forEach(function(msg) {
                var card = document.createElement('div');
                card.className = 'card mb-3';

                var header = document.createElement('div');
                header.className = 'card-header d-flex justify-content-between align-items-center';

                var authorEl = document.createElement('strong');
                authorEl.textContent = msg.author_name || msg.author_email ||
                    (strings.system_author || 'System');
                header.appendChild(authorEl);

                var dateEl = document.createElement('small');
                dateEl.className = 'text-muted';
                dateEl.textContent = msg.created_at ? new Date(msg.created_at).toLocaleString() : '\u2014';
                header.appendChild(dateEl);

                card.appendChild(header);

                var bodyEl = document.createElement('div');
                bodyEl.className = 'card-body';
                // Body is HTML from Odoo messages, already sanitized server-side.
                bodyEl.innerHTML = msg.body || '';
                card.appendChild(bodyEl);

                timeline.appendChild(card);
            });
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
         * @param {Object} strs      Translated UI strings from PHP.
         */
        init: function(url, key, ticketId, strs) {
            apiUrl    = url;
            pluginKey = key;
            strings   = strs || {};

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
