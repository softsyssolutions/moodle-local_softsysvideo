# local_softsysvideo

`local_softsysvideo` is a Moodle companion plugin for [SoftSys Video](https://softsyssolutions.com). It connects your Moodle site to the SoftSys Video platform, providing a dashboard with usage stats, meeting history, recordings, and account management — all from within Moodle's admin interface.

## Features

- **Dashboard** — Usage stats: meetings this month, video hours, total participants, recordings count.
- **Recordings** — Paginated list of recordings with playback links.
- **Meetings** — Recent meeting history with date, duration, and participant counts.
- **Connect** — Authenticate your Moodle site against the SoftSys Video API using a Plugin API Key.
- **Support** — Submit support requests directly from within Moodle.

## Requirements

- Moodle 4.1 or newer
- A SoftSys Video account at [app.softsysvideo.com](https://app.softsysvideo.com)
- A tenant Plugin API Key (obtained from the SoftSys Video dashboard)

## Installation

### Upload ZIP

1. Package this plugin as a zip file.
2. Go to `Site administration → Plugins → Install plugins`.
3. Upload the zip and complete the Moodle upgrade flow.

### Manual (CLI)

1. Place the plugin folder in `{moodle_root}/local/softsysvideo/`.
2. Run:

```bash
php admin/cli/upgrade.php
```

## Configuration

1. Go to `Site administration → Local plugins → SoftSys Video`.
2. Enter the **SoftSys Video API URL** (e.g. `https://api.softsysvideo.com`).
3. Enter the **Plugin API Key** obtained from your SoftSys Video tenant dashboard.
4. Navigate to the **Connect** page to authenticate your Moodle site.
5. Once connected, the Dashboard, Recordings, and Meetings pages will display live data.

## Getting Your Plugin API Key

1. Sign in at [https://app.softsysvideo.com](https://app.softsysvideo.com).
2. Open your tenant settings.
3. Generate a **Moodle Plugin API Key**.
4. Copy the key into the plugin settings.

## Support

For issues or questions, visit the **Support** page inside the plugin, or contact us at [support@softsyssolutions.com](mailto:support@softsyssolutions.com).
