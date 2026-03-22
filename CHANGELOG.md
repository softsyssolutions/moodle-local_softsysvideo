# Changelog

All notable changes to this project will be documented in this file.

## [0.1.0] — 2026-03-22

### Added
- Setup wizard to auto-configure `mod_bigbluebutton` with SoftSys Video credentials
- PHP API client (`classes/api_client.php`) for `/api/plugin/*` endpoints using `X-Plugin-Key` auth
- BBB configurator (`classes/bbb_configurator.php`) — safely writes to `bigbluebutton` config on explicit user action only
- Admin capabilities: `manage`, `viewanalytics`, `viewcredits`
- Language strings in English (`lang/en/`) and Spanish (`lang/es/`)
- Setup wizard Mustache template with connection status indicator
- AMD JavaScript (`amd/src/setup.js`) for AJAX test and configure actions
- Privacy API metadata declaration (`privacy:metadata` string)
- Full README with security model, coexistence documentation, and installation instructions

### Security
- Uses per-tenant `X-Plugin-Key` authentication (never the superadmin token)
- Keys are scoped and revocable from the SoftSys Video dashboard
- No cross-tenant data exposure possible
