# Video Conferencing Dashboard for Moodle

`local_softsysvideo` — a Moodle local plugin that links a Moodle site to a
SoftSys Video account and surfaces usage statistics, meeting history,
recordings and support tickets inside Moodle's admin interface.

All calls to the remote API are made server-side through
`classes/api_client.php`, so the API key never reaches the browser.

- **Component:** `local_softsysvideo`
- **Release:** 2.1.0 (`version.php`)
- **Maturity:** `MATURITY_STABLE`

## Requirements

- Moodle 4.1 to 5.1 — `version.php` declares `$plugin->requires = 2022041200`
  and `$plugin->supported = [401, 501]`. A single branch covers the whole range.
- PHP 8.1–8.3 (the versions exercised by CI; PHP 8.1 is only tested against
  Moodle 4.1/4.4/4.5, since Moodle 5.x dropped it).
- A SoftSys Video account that can issue a Dashboard API key.

## Installation

### Upload ZIP

1. Package the plugin directory as a ZIP.
2. Go to `Site administration → Plugins → Install plugins`.
3. Upload the ZIP and follow the Moodle upgrade prompts.

### Manual

1. Copy the plugin into `{moodle_root}/local/softsysvideo/`.
2. Run `php admin/cli/upgrade.php` from the Moodle root.

## Connecting the site

The plugin is linked with a single **API key** — there is no email/password
login and no manual API URL field.

1. Generate a key in the SoftSys Video app under
   `Settings → API Keys` (see the `api_key_help` language string).
2. In Moodle, go to
   `Site administration → Plugins → Local plugins → Video Conferencing Dashboard`.
   While the site is unconnected, that page shows a **Connect account** button
   pointing at `/local/softsysvideo/connect.php`.
3. Paste the key (format `sss_live_…`) and submit.

On submit, `connect.php` POSTs `{api_key, moodle_url, label}` to
`https://api.softsysvideo.com/api/moodle/connect`. On success it stores the
returned `api_url`, `shared_secret`, `tenant_name`, `connection_id` and the key
itself in plugin config, then redirects to `dashboard.php`.

Once connected, the admin menu entry becomes an `admin_externalpage` that opens
the dashboard directly (`settings.php`), and `dashboard.php` / `support.php`
redirect back to `connect.php` if the stored key is ever removed.

**Disconnecting** posts to `/api/moodle/disconnect` on the stored API URL so the
remote connection record is cleared, then unsets every locally stored config key.

## Pages

| Page | File | What it shows |
| --- | --- | --- |
| Dashboard | `dashboard.php` | Six current-month stat cards — meetings, video hours, session minutes, participants, recordings, recording minutes — plus the analytics section |
| Analytics | part of `dashboard.php` | 7d / 30d / 90d range selector, four KPI cards (total sessions, total minutes, total recording minutes, recordings) and two charts: sessions over time and minutes consumed |
| Recordings | `recordings.php` | Recording list with search, status filter, date-from/date-to, sort field + order, and pagination |
| Meetings | `meetings.php` | Meeting list with the same filter/sort/pagination controls, plus a per-meeting participant detail modal |
| Connection | `connect.php` | Connect / reconnect form and the connected-account card with a disconnect action |
| Support | `support.php`, `support_detail.php` | Support ticket list (subject, status, priority, date) with pagination, a create-ticket form (subject, description, optional course ID), and a per-ticket detail view with messages |

Charts are rendered with Moodle core's `core/chartjs-lazy` module; no external
chart library is bundled.

`image_proxy.php` serves image attachments referenced from ticket details. It
requires `local/softsysvideo:manage`, restricts the requested path with an
allowlist regex, fetches the bytes server-side with the stored key, and refuses
to emit anything whose content type is not `image/*`.

## Repository layout

```
amd/src/            AMD module sources (analytics, dashboard, meetings,
                    recordings, support_detail, support_list)
amd/build/          Minified build output, committed to the repo
classes/api_client.php        Server-side HTTP wrapper for the SoftSys Video API
classes/external/             8 external (web service) functions
classes/output/               plugin_navigation renderable + renderer
classes/privacy/provider.php  Privacy API implementation
db/access.php                 Capability definitions
db/services.php               External function registration
lang/en, lang/es              Language packs (137 strings each)
templates/                    plugin_navigation.mustache
tests/plugin_test.php         PHPUnit tests
```

## Web service functions

All eight functions are registered in `db/services.php` as AJAX-callable and
guarded by `local/softsysvideo:manage`:

`local_softsysvideo_get_stats`, `local_softsysvideo_get_analytics`,
`local_softsysvideo_get_recordings`, `local_softsysvideo_get_meetings`,
`local_softsysvideo_get_tickets`, `local_softsysvideo_get_ticket_detail`,
`local_softsysvideo_get_meeting_participants`, `local_softsysvideo_create_ticket`.

## Capabilities

| Capability | Archetypes | Notes |
| --- | --- | --- |
| `local/softsysvideo:manage` | manager | Required by every plugin page and every web service function |
| `local/softsysvideo:viewanalytics` | teacher, manager | Defined in `db/access.php` |
| `local/softsysvideo:viewcredits` | manager | Defined in `db/access.php`; not yet checked by any shipped page |

## Privacy

`classes/privacy/provider.php` implements `null_provider` — the plugin stores no
personal data in Moodle.

## Development

JavaScript sources live in `amd/src/` and the minified output in `amd/build/` is
committed (it is not gitignored), so rebuild with Moodle's core Grunt setup and
commit the result whenever a source module changes. See `CONTRIBUTING.md`.

PHPUnit tests live in `tests/plugin_test.php`.

### Continuous integration

`.github/workflows/ci.yml` runs on every push and pull request using
`moodlehq/moodle-plugin-ci ^4` across a matrix of Moodle
`MOODLE_401_STABLE`, `MOODLE_404_STABLE`, `MOODLE_405_STABLE` and
`MOODLE_501_STABLE`, PHP 8.1–8.3, against both PostgreSQL 15 and MariaDB 10.

Steps: PHP lint, PHP Mess Detector (non-blocking), Moodle Code Checker
(`phpcs --max-warnings 0`), PHPDoc checker, `validate`, upgrade savepoints,
Mustache lint, Grunt (non-blocking), PHPUnit (`--fail-on-warning`) and Behat.

## Changelog

See [CHANGELOG.md](CHANGELOG.md).

## License

GNU GPL v3 or later. See [LICENSE](LICENSE) or <https://www.gnu.org/licenses/gpl-3.0.html>
