# local_softsysvideo

`local_softsysvideo` is a Moodle local companion plugin for `mod_bigbluebutton`.

## What This Plugin Does

- Stores SoftSys Video connection settings with Moodle's config API.
- Provides an admin setup wizard to test connectivity against the SoftSys Video API Worker.
- Explicitly configures `mod_bigbluebutton` to use the SoftSys Video server URL and shared secret only when an authorized administrator clicks `Configure BBB`.
- Adds the foundation for credits and balance display, analytics, chat export, AI summary, and support integrations through `/api/plugin/*` endpoints.

## What This Plugin Does Not Do

- It does not replace `mod_bigbluebutton`.
- It does not create or manage BigBlueButton meetings directly.
- It does not write to BigBlueButton settings during install, upgrade, or passive page loads.
- It does not add custom database tables in phase 1.

## Requirements

- Moodle 4.1 or newer
- `mod_bigbluebutton` installed
- A SoftSys Video account
- A tenant Plugin API Key from `app.softsysvideo.com`

## Installation

### Upload ZIP

1. Package this plugin as a zip file.
2. Go to `Site administration -> Plugins -> Install plugins`.
3. Upload the zip and complete the Moodle upgrade flow.

### CLI

1. Place the plugin in `local/softsysvideo`.
2. Run:

```bash
php admin/cli/upgrade.php
```

## Configuration

1. Go to `Site administration -> Plugins -> Local plugins -> SoftSys Video companion`.
2. Enter the SoftSys Video API URL.
3. Enter the tenant Plugin API Key.
4. Enter the BBB shared secret supplied for your tenant.
5. Open the Setup Wizard.
6. Click `Test connection`.
7. After a successful test, click `Configure BBB`.

Only the explicit `Configure BBB` action writes to `mod_bigbluebutton` settings.

## Getting Your Plugin API Key

1. Sign in at `https://app.softsysvideo.com`.
2. Open your tenant administration area.
3. Generate a Moodle Plugin API Key.
4. Copy the key into the plugin settings or setup wizard.

## Security

- Plugin API Keys are tenant-specific.
- Requests to the SoftSys Video API use the `X-Plugin-Key` header.
- The setup wizard does not expose the BBB shared secret in browser requests.
- BigBlueButton configuration is updated only during an explicit authorized connect flow.
- Phase 1 uses Moodle's config API and avoids direct SQL for plugin state.

## Coexistence With Existing BBB

`local_softsysvideo` coexists with `mod_bigbluebutton`. It uses its own namespace, capabilities, templates, and settings. It does not create conflicting BigBlueButton tables or capabilities. Both plugins can be installed simultaneously, and the companion plugin only touches the `server_url` and `shared_secret` config keys in `mod_bigbluebutton` during the explicit setup action.

## Support

For tenant support, billing questions, analytics questions, chat export help, or AI summary issues, contact SoftSys Solutions through your SoftSys Video support channel.
