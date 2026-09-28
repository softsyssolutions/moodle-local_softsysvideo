#!/usr/bin/env bash
# One-time setup: point git at the repo's own hooks dir instead of .git/hooks,
# so `.githooks/pre-push` (the local CI gate) runs before every push.
set -euo pipefail

cd "$(git rev-parse --show-toplevel)"
git config core.hooksPath .githooks
chmod +x .githooks/pre-push scripts/ci.sh scripts/install-hooks.sh 2>/dev/null || true

echo "core.hooksPath -> .githooks (scripts/ci.sh now runs on every push)"
