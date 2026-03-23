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
 * Setup wizard AMD module for local_softsysvideo.
 *
 * @module     local_softsysvideo/setup
 * @copyright  2026 SoftSys Solutions {@link https://softsyssolutions.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define([], function() {
    const setStatus = (container, message, kind) => {
        container.className = `mt-3 alert ${kind === 'success' ? 'alert-success' : 'alert-danger'}`;
        container.textContent = message;
    };

    const postAction = async(setupPageUrl, payload, errorLabel) => {
        const body = new URLSearchParams(payload);
        const response = await fetch(setupPageUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
            },
            body: body.toString(),
        });

        if (!response.ok) {
            throw new Error(errorLabel || 'Request failed');
        }

        return response.json();
    };

    const init = async(setupPageUrl) => {
        const testButton = document.getElementById('softsysvideo-test-btn');
        const configureButton = document.getElementById('softsysvideo-configure-btn');
        const status = document.getElementById('softsysvideo-status');
        const apiUrlInput = document.getElementById('softsysvideo-api-url');
        const pluginKeyInput = document.getElementById('softsysvideo-plugin-key');
        const sesskey = document.getElementById('softsysvideo-sesskey')?.value || '';
        const requestFailedLabel = status?.dataset.requestFailedLabel || 'Request failed';

        if (!testButton || !configureButton || !status || !apiUrlInput || !pluginKeyInput) {
            return;
        }

        testButton.addEventListener('click', async() => {
            setStatus(status, status.dataset.testingLabel || 'Testing...', 'success');

            try {
                const result = await postAction(setupPageUrl, {
                    action: 'test',
                    api_url: apiUrlInput.value,
                    plugin_key: pluginKeyInput.value,
                    sesskey: sesskey,
                }, requestFailedLabel);

                const tenant = result.tenant_name ? ` (${result.tenant_name})` : '';
                const balance = result.balance !== undefined ? ` Balance: $${result.balance}.` : '';

                setStatus(status, `${result.message}${tenant}${balance}`, result.ok ? 'success' : 'error');

                if (result.ok) {
                    configureButton.classList.remove('d-none');
                } else {
                    configureButton.classList.add('d-none');
                }
            } catch (error) {
                configureButton.classList.add('d-none');
                setStatus(status, error.message, 'error');
            }
        });

        configureButton.addEventListener('click', async() => {
            try {
                const result = await postAction(setupPageUrl, {
                    action: 'configure',
                    api_url: apiUrlInput.value,
                    plugin_key: pluginKeyInput.value,
                    sesskey: sesskey,
                }, requestFailedLabel);

                setStatus(status, result.message || requestFailedLabel, result.ok ? 'success' : 'error');
            } catch (error) {
                setStatus(status, error.message, 'error');
            }
        });
    };

    return {
        init: init,
    };
});
