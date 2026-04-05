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
 *
 * @module     local_softsysvideo/support_detail
 * @copyright  2026 SoftSys Solutions {@link https://softsyssolutions.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['core/ajax', 'core/notification'], function() {

    var apiUrl = '';
    var pluginKey = '';
    var strings = {};

    function statusBadgeClass(status) {
        var s = (status || '').toLowerCase();
        if (s === 'new' || s === 'nuevo')                       { return 'badge bg-primary'; }
        if (s === 'in progress' || s === 'en progreso')         { return 'badge bg-warning text-dark'; }
        if (s === 'answered' || s === 'respondido')             { return 'badge bg-info text-dark'; }
        if (s === 'solved' || s === 'resolved' || s === 'resuelto') { return 'badge bg-success'; }
        if (s === 'closed' || s === 'cerrado')                  { return 'badge bg-secondary'; }
        return 'badge bg-secondary';
    }

    function showSpinner() {
        var el = document.getElementById('ssv-detail-spinner');
        if (el) { el.classList.remove('d-none'); }
    }

    function hideSpinner() {
        var el = document.getElementById('ssv-detail-spinner');
        if (el) { el.classList.add('d-none'); }
    }

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
            dateEl.textContent = ticket.createdAt
                ? new Date(ticket.createdAt).toLocaleString()
                : '\u2014';
        }

        // Show description if available.
        var descEl = document.getElementById('ssv-detail-description');
        if (descEl && ticket.description) {
            descEl.innerHTML = ticket.description;
            descEl.classList.remove('d-none');
        }

        var card = document.getElementById('ssv-detail-card');
        if (card) { card.classList.remove('d-none'); }
    }

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

                // Header: author + date.
                var header = document.createElement('div');
                header.className = 'card-header d-flex justify-content-between align-items-center';

                var authorEl = document.createElement('strong');
                authorEl.textContent = msg.author || (strings.system_author || 'System');
                header.appendChild(authorEl);

                var dateEl = document.createElement('small');
                dateEl.className = 'text-muted';
                dateEl.textContent = msg.date ? new Date(msg.date).toLocaleString() : '\u2014';
                header.appendChild(dateEl);

                card.appendChild(header);

                // Body: HTML content from Odoo (sanitized server-side).
                var bodyEl = document.createElement('div');
                bodyEl.className = 'card-body';
                bodyEl.innerHTML = msg.body || '';

                // Attachments (images).
                if (msg.attachments && msg.attachments.length > 0) {
                    var attDiv = document.createElement('div');
                    attDiv.className = 'mt-3 d-flex flex-wrap gap-2';

                    msg.attachments.forEach(function(att) {
                        var link = document.createElement('a');
                        // Build full URL for the moodle-authenticated image proxy.
                        link.href = apiUrl + att.url;
                        link.target = '_blank';
                        link.rel = 'noopener noreferrer';
                        link.title = att.name || 'Attachment';

                        var img = document.createElement('img');
                        img.src = apiUrl + att.url;
                        img.alt = att.name || 'Attachment';
                        img.style.cssText = 'max-width:200px;max-height:150px;border-radius:4px;border:1px solid #dee2e6';
                        img.onerror = function() {
                            // If image fails, show a text link instead.
                            link.textContent = att.name || 'Attachment';
                            link.className = 'btn btn-sm btn-outline-secondary';
                            if (link.contains(img)) { link.removeChild(img); }
                        };

                        link.appendChild(img);
                        attDiv.appendChild(link);
                    });

                    bodyEl.appendChild(attDiv);
                }

                card.appendChild(bodyEl);
                timeline.appendChild(card);
            });
        }

        var messagesSection = document.getElementById('ssv-detail-messages');
        if (messagesSection) { messagesSection.classList.remove('d-none'); }
    }

    return {
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
