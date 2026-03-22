/**
 * AMD module for SoftSys Video setup wizard.
 * Handles AJAX test connection and BBB configuration.
 *
 * @module local_softsysvideo/setup
 */

/**
 * Initialize the setup wizard interactions.
 *
 * @param {string} setupPageUrl URL of setup.php for AJAX calls
 */
export async function init(setupPageUrl) {
    const testBtn       = document.getElementById("softsysvideo-test-btn");
    const configureBtn  = document.getElementById("softsysvideo-configure-btn");
    const statusDiv     = document.getElementById("softsysvideo-status");

    if (!testBtn || !statusDiv) {
        return;
    }

    /**
     * Show a status message with Bootstrap alert styling.
     *
     * @param {string} msg    Message text
     * @param {string} type   Bootstrap alert type (success|danger|info|warning)
     */
    function showStatus(msg, type) {
        statusDiv.style.display = "block";
        statusDiv.className = `alert alert-${type} my-3`;
        statusDiv.textContent = msg;
    }

    // ── Test connection ──────────────────────────────────────────────────────
    testBtn.addEventListener("click", async () => {
        const apiUrl    = document.getElementById("softsysvideo-api-url")?.value.trim();
        const pluginKey = document.getElementById("softsysvideo-plugin-key")?.value.trim();

        if (!apiUrl || !pluginKey) {
            showStatus("Please enter both API URL and Plugin Key.", "warning");
            return;
        }

        showStatus("Testing connection...", "info");
        testBtn.disabled = true;

        try {
            const body = new URLSearchParams({ api_url: apiUrl, plugin_key: pluginKey });
            const resp = await fetch(setupPageUrl + "?action=test", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body,
            });

            const data = await resp.json();

            if (data.ok) {
                showStatus(
                    `✅ Connection successful! Tenant: ${data.tenant_name} | Balance: $${Number(data.balance).toFixed(2)} USD`,
                    "success"
                );
                if (configureBtn) {
                    configureBtn.style.display = "inline-flex";
                }
            } else {
                showStatus("❌ Connection failed. Check your API URL and Plugin Key.", "danger");
            }
        } catch (err) {
            showStatus(`❌ Network error: ${err.message}`, "danger");
        } finally {
            testBtn.disabled = false;
        }
    });

    // ── Configure BBB ────────────────────────────────────────────────────────
    if (configureBtn) {
        configureBtn.addEventListener("click", async () => {
            const apiUrl       = document.getElementById("softsysvideo-api-url")?.value.trim();
            const pluginKey    = document.getElementById("softsysvideo-plugin-key")?.value.trim();
            const sharedSecret = document.getElementById("softsysvideo-shared-secret")?.value.trim();
            const sesskey      = configureBtn.dataset.sesskey;

            if (!sharedSecret) {
                showStatus("Please enter the Shared Secret before configuring BigBlueButton.", "warning");
                return;
            }

            configureBtn.disabled = true;
            showStatus("Configuring BigBlueButton...", "info");

            try {
                const body = new URLSearchParams({
                    api_url: apiUrl,
                    plugin_key: pluginKey,
                    shared_secret: sharedSecret,
                    sesskey,
                });

                const resp = await fetch(setupPageUrl + "?action=configure", {
                    method: "POST",
                    headers: { "Content-Type": "application/x-www-form-urlencoded" },
                    body,
                });

                const data = await resp.json();

                if (data.ok) {
                    showStatus(
                        "✅ BigBlueButton is now configured to use SoftSys Video! You can close this page.",
                        "success"
                    );
                    configureBtn.style.display = "none";
                    // Reload after 2s to update status indicator
                    setTimeout(() => window.location.reload(), 2000);
                } else {
                    showStatus("❌ Configuration failed. Please try again.", "danger");
                    configureBtn.disabled = false;
                }
            } catch (err) {
                showStatus(`❌ Error: ${err.message}`, "danger");
                configureBtn.disabled = false;
            }
        });
    }
}
