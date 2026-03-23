# Video Conferencing Dashboard for Moodle

A Moodle companion plugin that integrates your Moodle site with a
BigBlueButton-compatible video conferencing platform, providing a
central dashboard with usage statistics, meeting history, and
recording management — all within Moodle's admin interface.

## Features

- **Dashboard** — Monthly usage stats: meetings, video hours, participants, recordings
- **Analytics** — Usage trends with visual charts (sessions and minutes over time)
- **Recordings** — Paginated, searchable list of recordings with playback links
- **Meetings** — Meeting history with date, duration, and participant counts
- **Connect** — Secure connection wizard using a Plugin API Key

## Requirements

- Moodle 4.1 or later
- A compatible video conferencing platform account with plugin API access

## Installation

### Method 1: Upload ZIP (recommended)

1. Download or build the plugin as a ZIP file
2. Go to `Site administration → Plugins → Install plugins`
3. Upload the ZIP and follow the Moodle upgrade prompts

### Method 2: Manual

1. Copy the plugin folder to `{moodle_root}/local/softsysvideo/`
2. Run: `php admin/cli/upgrade.php`

## Configuration

1. Go to `Site administration → Local plugins → Video Conferencing Dashboard`
2. Click **Connect** and enter your account credentials
3. Once connected, the Dashboard, Recordings, and Meetings pages show live data

## Privacy

This plugin does not store personal data. See `classes/privacy/provider.php`.

## License

GNU GPL v3 or later. See [LICENSE](LICENSE) or <https://www.gnu.org/licenses/gpl-3.0.html>
