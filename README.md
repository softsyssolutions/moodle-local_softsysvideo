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

## CI / development

This repo has **no GitHub Actions**. The merge gate runs locally:

```bash
bash scripts/ci.sh                    # full moodle-plugin-ci gate (~5-10 min first run)
MOODLE_CI_CLEAN=1 bash scripts/ci.sh  # force a clean Moodle reinstall
bash scripts/ci.sh stop               # stop the Postgres container
```

It runs moodle-plugin-ci's phplint, phpmd, phpcs, phpdoc, validate, savepoints,
mustache, grunt, and phpunit against one representative combo (Moodle
`MOODLE_405_STABLE`, PHP 8.1-8.3, Postgres via `docker-compose.moodle-ci.yml`)
instead of the old workflow's full 10-cell matrix — see the comment at the top
of `scripts/ci.sh` for what that narrows and why, and for the one check
(Behat) it does not run yet.

Unlike the deleted workflow (which ran every step regardless of earlier
failures via `if: !cancelled()`), `scripts/ci.sh` stops at the first blocking
failure (`set -euo pipefail`) — phpmd and grunt are the two exceptions, kept
non-fatal to match the old `continue-on-error: true` on those two steps.

Enable the pre-push gate once per clone:

```bash
bash scripts/install-hooks.sh
```

This points git at `.githooks/` so `scripts/ci.sh` runs automatically before
every `git push`. A PR only merges after this script exits 0 on its head.

## License

GNU GPL v3 or later. See [LICENSE](LICENSE) or <https://www.gnu.org/licenses/gpl-3.0.html>
