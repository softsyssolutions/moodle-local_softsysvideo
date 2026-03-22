# local_softsysvideo — SoftSys Video Moodle Plugin

Companion plugin for `mod_bigbluebutton` that connects your Moodle to [SoftSys Video](https://softsysvideo.com).

## What it does

- **Auto-configures `mod_bigbluebutton`** to point to your SoftSys Video API endpoint
- Displays your **credit balance** and **usage statistics** in the Moodle admin panel
- Provides access to **chat export** and **AI meeting summaries** from BBB activities
- Integrated **support ticket** creation directly from Moodle

## What it does NOT do

- ❌ Does NOT replace `mod_bigbluebutton` — it works **alongside** it
- ❌ Does NOT modify or break your existing BBB activities
- ❌ Does NOT require uninstalling or reconfiguring any existing BBB setup
- ❌ Does NOT conflict with an existing institutional BBB server

## Requirements

- Moodle **4.1 or newer** (LTS recommended)
- `mod_bigbluebutton` plugin installed and enabled
- A **SoftSys Video account** — [sign up at softsysvideo.com](https://softsysvideo.com)

## Installation

### Option A — Upload via Moodle UI
1. Download this repository as a ZIP file
2. Go to **Site administration → Plugins → Install plugins**
3. Upload the ZIP and follow the prompts

### Option B — CLI
```bash
# From your Moodle root
git clone https://github.com/softsyssolutions/moodle-local_softsysvideo.git local/softsysvideo
php admin/cli/upgrade.php
```

## Getting your credentials

1. Log in to [app.softsysvideo.com](https://app.softsysvideo.com)
2. Go to **Settings → Moodle Integration**
3. Click **Generate Plugin Key**
4. Copy your:
   - **API URL** (your tenant subdomain, e.g. `https://api-yourcompany-xxxx.softsysvideo.com`)
   - **Plugin API Key** (format: `ssv_pk_...`) — shown only once, save it securely
   - **Shared Secret** — for BBB-compatible checksum validation

## Configuration in Moodle

1. Go to **Site administration → Plugins → Local plugins → SoftSys Video → Setup**
2. Enter your **API URL** and **Plugin API Key**
3. Click **Test Connection** — you should see your tenant name and balance
4. Enter your **Shared Secret**
5. Click **Configure BigBlueButton** to auto-apply the credentials

From this point, all BBB activities in Moodle will use SoftSys Video as the backend.

## Coexistence with existing BBB installations

Both plugins can run on the **same Moodle simultaneously** without any conflict:

| Aspect | mod_bigbluebutton (BBB official) | local_softsysvideo (this plugin) |
|---|---|---|
| Plugin type | Activity module | Local plugin |
| Tables | `mdl_bigbluebutton` | None (uses Moodle config) |
| Capabilities | `mod/bigbluebutton:*` | `local/softsysvideo:*` |
| Config keys | `bigbluebutton_*` | `local_softsysvideo_*` |

The only shared resource is the BBB server URL and shared secret in `mod_bigbluebutton` config. This plugin only writes to those during an **explicit "Configure BigBlueButton" action** — never automatically.

## Security

| Property | Detail |
|---|---|
| **Isolation** | Each Moodle uses a unique Plugin API Key (`ssv_pk_...`) |
| **Scope** | Keys are read-only by default (credits, analytics, meetings) |
| **No cross-tenant exposure** | Compromise of one key never exposes other tenants |
| **Revocation** | Keys can be revoked instantly from the SoftSys Video dashboard |
| **Transport** | All communication over HTTPS (TLS 1.2+) |
| **Key storage** | Keys stored as SHA-256 hashes on the server — plain key shown only once |

## Changelog

See [CHANGELOG.md](CHANGELOG.md)

## License

GNU General Public License v3.0 or later  
See [LICENSE](https://www.gnu.org/licenses/gpl-3.0.html)

## Support

- **Documentation:** [docs.softsysvideo.com](https://docs.softsysvideo.com)
- **Issues:** [GitHub Issues](https://github.com/softsyssolutions/moodle-local_softsysvideo/issues)
- **Email:** support@softsyssolutions.com
