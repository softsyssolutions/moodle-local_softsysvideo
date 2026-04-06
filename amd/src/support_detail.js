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
define(['core/ajax'], function(Ajax) {

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

    function renderTicketCard(data) {
        var subjectEl  = document.getElementById('ssv-detail-subject');
        var statusEl   = document.getElementById('ssv-detail-status');
        var priorityEl = document.getElementById('ssv-detail-priority');
        var dateEl     = document.getElementById('ssv-detail-date');

        if (subjectEl)  { subjectEl.textContent = data.subject || '\u2014'; }

        if (statusEl) {
            while (statusEl.firstChild) {
                statusEl.removeChild(statusEl.firstChild);
            }
            var badge = document.createElement('span');
            badge.className = statusBadgeClass(data.status);
            badge.textContent = data.status || '\u2014';
            statusEl.appendChild(badge);
        }

        if (priorityEl) { priorityEl.textContent = data.priority || '\u2014'; }

        if (dateEl) {
            dateEl.textContent = data.created_at
                ? new Date(data.created_at).toLocaleString()
                : '\u2014';
        }

        var descEl = document.getElementById('ssv-detail-description');
        if (descEl && data.description) {
            // HTML is sanitized server-side via format_text(FORMAT_HTML).
            descEl.innerHTML = data.description;
            descEl.classList.remove('d-none');
        }

        var card = document.getElementById('ssv-detail-card');
        if (card) { card.classList.remove('d-none'); }
    }

    function renderMessages(messages) {
        var timeline = document.getElementById('ssv-detail-timeline');
        if (!timeline) { return; }
        while (timeline.firstChild) {
            timeline.removeChild(timeline.firstChild);
        }

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
                authorEl.textContent = msg.author || (strings.system_author || 'System');
                header.appendChild(authorEl);

                var dateEl = document.createElement('small');
                dateEl.className = 'text-muted';
                dateEl.textContent = msg.date ? new Date(msg.date).toLocaleString() : '\u2014';
                header.appendChild(dateEl);

                card.appendChild(header);

                var bodyEl = document.createElement('div');
                bodyEl.className = 'card-body';
                // HTML is sanitized server-side via format_text(FORMAT_HTML).
                bodyEl.innerHTML = msg.body || '';

                if (msg.attachments && msg.attachments.length > 0) {
                    var attContainer = document.createElement('div');
                    attContainer.className = 'mt-2';
                    msg.attachments.forEach(function(att) {
                        if (att.url && att.mimetype && att.mimetype.indexOf('image/') === 0) {
                            var img = document.createElement('img');
                            img.src = att.url;
                            img.alt = att.name || 'attachment';
                            img.className = 'd-block mb-2 rounded';
                            img.style.maxWidth = '100%';
                            img.style.maxHeight = '400px';
                            attContainer.appendChild(img);
                        }
                    });
                    bodyEl.appendChild(attContainer);
                }

                card.appendChild(bodyEl);
                timeline.appendChild(card);
            });
        }

        var messagesSection = document.getElementById('ssv-detail-messages');
        if (messagesSection) { messagesSection.classList.remove('d-none'); }
    }

    return {
        init: function(ticketId, strs) {
            strings = strs || {};

            showSpinner();

            Ajax.call([{
                methodname: 'local_softsysvideo_get_ticket_detail',
                args: {ticketid: ticketId}
            }])[0].then(function(data) {
                hideSpinner();
                renderTicketCard(data);
                renderMessages(data.messages || []);
                return;
            }).catch(function() {
                hideSpinner();
                var err = document.getElementById('ssv-detail-error');
                if (err) { err.classList.remove('d-none'); }
            });
        }
    };
});
