# Changelog

All notable changes to this project will be documented in this file.

## [0.2.0] — 2026-03-22

### Added

- Dashboard page with monthly usage stats (meetings, video hours, participants, recordings)
- Analytics charts: sessions over time (bar), minutes consumed (line) — 7d/30d/90d range
- Recordings page with pagination (10/page) and search
- Meetings page with pagination (10/page) and search
- Connect wizard: email + password authentication against the platform API
- Support page: submit requests via Moodle's internal email system
- AMD modules (RequireJS-compatible) for all interactive UI components
- Privacy provider (null_provider — no personal data stored)

### Changed

- Removed BBB configuration from plugin scope (BigBlueButton is configured via its own Moodle module)
- Removed legacy setup wizard and related dead code
- All JavaScript moved from inline `<script>` tags to AMD modules (`amd/src/`)
- Bumped maturity from ALPHA to BETA

## [0.1.0] — 2026-03-18

### Added

- Initial plugin structure and connect flow
