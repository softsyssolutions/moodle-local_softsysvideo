# Changelog

All notable changes to this project will be documented in this file.

## [2.0.0] — 2026-04-15

### Added

- Credit Balance stat card on dashboard (shows tenant credit balance in USD)
- Low-balance warning: card turns yellow + "Low balance — top up soon" badge when balance < $0.50
- Session Minutes stat card (minutes of video sessions consumed this calendar month)
- New fields in `/api/moodle/stats` response: `credit_balance`, `low_balance_warning`, `session_minutes`, `recording_minutes`
- 4 new lang strings: `creditbalance`, `lowbalancewarning`, `sessionminutes`, `recordingminutes`
- Spec SDD at `specs/003-moodle-plugin-v2/spec.md` (in api-worker repo)

### Changed

- Dashboard layout: 6 stat cards in 2-column grid (was 4 cards in 4-column grid)
- `get_stats` external function now returns 9 fields (was 5)
- Stat card hover now includes subtle upward translation in addition to shadow deepening
- Stat label style: uppercase, slightly larger letter-spacing for improved readability

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
