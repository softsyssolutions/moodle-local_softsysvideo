#!/usr/bin/env bash
# The merge gate, run locally. This repo does not use GitHub Actions: this
# script (plus `.githooks/pre-push`, enabled via `scripts/install-hooks.sh`)
# is the whole of CI. It replaces the deleted `.github/workflows/ci.yml`.
#
# The deleted workflow ran moodle-plugin-ci across a 10-cell matrix (Moodle
# 4.1/4.4/4.5/5.1 x PHP 8.1/8.2/8.3 x pgsql/mariadb). Reproducing that whole
# matrix locally is not realistic (~10 full Moodle installs). This gate runs
# ONE representative cell — the newest LTS-ish branch this plugin's
# version.php actually supports — and documents the narrowing explicitly:
#
#   Moodle:   MOODLE_405_STABLE (override: MOODLE_BRANCH)
#   PHP:      whatever `php` on PATH resolves to (must be 8.1-8.3)
#   Database: pgsql on a dedicated Docker Postgres (override: DB, see below)
#
# To check a different cell before a release: MOODLE_BRANCH=MOODLE_501_STABLE
# scripts/ci.sh, or DB=mariadb (with a mariadb service added to
# docker-compose.moodle-ci.yml) — not wired up by default because the day-to-day
# gate only needs one DB engine to catch real regressions.
#
# Known gap (not reproduced here): the "Behat features" job. This plugin ships
# no tests/behat/features/*.feature files today, so there is nothing for Behat
# to run — moodle-plugin-ci behat would just report zero scenarios after paying
# the cost of standing up Selenium/Chrome. The day a .feature file is added,
# this script must grow a `behat` step (moodle-docker already has
# selenium.chrome.yml to build on) — until then it stays out to keep the gate
# fast and honest about what it checks.
#
# Usage:
#   scripts/ci.sh                  # full gate (installs Moodle+deps on first run, ~5-10 min)
#   MOODLE_CI_CLEAN=1 scripts/ci.sh   # wipe the cached Moodle install and reinstall
#   scripts/ci.sh stop             # stop the Postgres container
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
PLUGIN="$ROOT"
export COMPOSE_PROJECT_NAME="${COMPOSE_PROJECT_NAME:-softsysvideo-moodle-ci}"
COMPOSE=(docker compose -p "$COMPOSE_PROJECT_NAME" -f "$ROOT/docker-compose.moodle-ci.yml")
PG_PORT="${MOODLE_CI_PG_PORT:-5433}"

export DB="${DB:-pgsql}"
export DB_USER="${DB_USER:-postgres}"
export DB_PASS="${DB_PASS:-}"
export DB_HOST="${DB_HOST:-127.0.0.1}"
export DB_PORT="${DB_PORT:-$PG_PORT}"
export MOODLE_BRANCH="${MOODLE_BRANCH:-MOODLE_405_STABLE}"
export NVM_DIR="${NVM_DIR:-$HOME/.nvm}"

# Writable dir where Moodle core + moodle-plugin-ci's own tooling get cloned.
# Reused across runs; MOODLE_CI_CLEAN=1 wipes it for a clean reinstall (needed
# after bumping MOODLE_BRANCH or when the cache gets into a bad state).
MOODLE_WORKDIR="${MOODLE_CI_WORKDIR:-${XDG_CACHE_HOME:-$HOME/.cache}/softsysvideo-moodle-ci-work}"
PLUGIN_CI_HOME="${MOODLE_PLUGIN_CI_HOME:-${XDG_CACHE_HOME:-$HOME/.cache}/softsysvideo-moodle-plugin-ci}"
LOCAL_BIN="${XDG_BIN_HOME:-$HOME/.local/bin}"

step() { printf '\n== %s\n' "$1"; }
fail() { printf 'FAIL  %s\n' "$1" >&2; exit 1; }
warn() { printf 'WARN  %s (non-blocking, matches upstream continue-on-error)\n' "$1" >&2; }

usage() {
  cat <<EOF
Usage: scripts/ci.sh [command]

  full (default)   Start Postgres (Docker), install Moodle + this plugin if
                    needed, then run the moodle-plugin-ci gate.
  stop              Stop the Postgres container only.

Environment (optional):
  MOODLE_BRANCH       Default: MOODLE_405_STABLE (also valid: MOODLE_501_STABLE,
                       MOODLE_404_STABLE, MOODLE_401_STABLE — see version.php
                       for the floor this plugin declares support for)
  MOODLE_CI_WORKDIR    Where Moodle is cloned (default: ~/.cache/softsysvideo-moodle-ci-work)
  MOODLE_CI_CLEAN=1    Remove the workdir before install (full reinstall)
  MOODLE_CI_PG_PORT    Host port for Postgres (default: 5433)

Requires: docker, docker compose v2, php (8.1-8.3), composer. On Linux the gate
also needs \`en_AU.UTF-8\` generated (moodle-plugin-ci's tests expect it).

This script REFUSES to run (not skip) any step whose tool is missing — a green
run means every listed check actually executed.
EOF
}

ensure_toolchain() {
  step "Toolchain"
  command -v docker >/dev/null 2>&1 || fail "docker not found on PATH"
  docker compose version >/dev/null 2>&1 || fail "docker compose v2 not found (docker compose version failed)"
  command -v php >/dev/null 2>&1 || fail "php not found on PATH"

  # moodle-plugin-ci shells out to `psql` on the HOST to create the DB, even
  # though Postgres itself runs in Docker. Homebrew's libpq is keg-only and
  # doesn't put psql on PATH by default.
  if ! command -v psql >/dev/null 2>&1; then
    if [[ -x /opt/homebrew/opt/libpq/bin/psql ]]; then
      export PATH="/opt/homebrew/opt/libpq/bin:$PATH"
    elif [[ -x /usr/local/opt/libpq/bin/psql ]]; then
      export PATH="/usr/local/opt/libpq/bin:$PATH"
    fi
  fi
  command -v psql >/dev/null 2>&1 || fail "psql not found (brew install libpq, then add its bin dir to PATH)"
  php_version="$(php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;' 2>/dev/null || true)"
  case "$php_version" in
    8.1|8.2|8.3) ;;
    *) fail "php 8.1-8.3 required (found: ${php_version:-unknown}); moodle-plugin-ci's Moodle branches in this matrix don't support anything else" ;;
  esac

  if ! command -v composer >/dev/null 2>&1; then
    echo "composer not found. Bootstrapping a local composer binary into $LOCAL_BIN..."
    mkdir -p "$LOCAL_BIN"
    installer="$LOCAL_BIN/composer-setup.php"
    php -r "copy('https://getcomposer.org/installer', '$installer');" >/dev/null 2>&1 \
      || fail "could not download the Composer installer (no network?)"
    php "$installer" --install-dir="$LOCAL_BIN" --filename="composer" >/dev/null \
      || { rm -f "$installer"; fail "Composer installer failed"; }
    rm -f "$installer"
    export PATH="$LOCAL_BIN:$PATH"
    command -v composer >/dev/null 2>&1 || fail "composer still not found after installing it"
  fi

  echo "  ok   docker $(docker --version 2>/dev/null | head -1), php $php_version, composer $(composer --version 2>/dev/null | head -1)"
}

ensure_plugin_ci() {
  if [[ -x "$PLUGIN_CI_HOME/bin/moodle-plugin-ci" ]]; then
    export PATH="$PLUGIN_CI_HOME/bin:$PLUGIN_CI_HOME/vendor/bin:$PATH"
    return 0
  fi
  step "Installing moodle-plugin-ci (one-time, cached in $PLUGIN_CI_HOME)"
  mkdir -p "$(dirname "$PLUGIN_CI_HOME")"
  composer create-project -n --no-dev --prefer-dist moodlehq/moodle-plugin-ci "$PLUGIN_CI_HOME" ^4
  export PATH="$PLUGIN_CI_HOME/bin:$PLUGIN_CI_HOME/vendor/bin:$PATH"
}

wait_for_pg() {
  local retries=60
  until "${COMPOSE[@]}" exec -T postgres pg_isready -U postgres >/dev/null 2>&1; do
    retries=$((retries - 1))
    if [[ $retries -le 0 ]]; then
      fail "Postgres did not become ready on port $PG_PORT"
    fi
    sleep 1
  done
}

run_full() {
  ensure_toolchain

  if command -v locale-gen >/dev/null 2>&1; then
    sudo locale-gen en_AU.UTF-8 2>/dev/null || true
  fi

  step "docker: Postgres on host port $PG_PORT ($MOODLE_BRANCH)"
  "${COMPOSE[@]}" up -d
  wait_for_pg

  ensure_plugin_ci

  if [[ "${MOODLE_CI_CLEAN:-0}" == "1" ]]; then
    step "MOODLE_CI_CLEAN=1: removing $MOODLE_WORKDIR"
    rm -rf "$MOODLE_WORKDIR"
  fi
  mkdir -p "$MOODLE_WORKDIR"

  if [[ ! -f "$MOODLE_WORKDIR/moodle/config.php" ]]; then
    step "moodle-plugin-ci install ($MOODLE_BRANCH, first run can take several minutes)"
    (cd "$MOODLE_WORKDIR" && moodle-plugin-ci install --plugin "$PLUGIN" --db-host="$DB_HOST")
  else
    step "Reusing existing Moodle in $MOODLE_WORKDIR (MOODLE_CI_CLEAN=1 to reinstall)"
  fi

  step "PHP Lint"
  (cd "$MOODLE_WORKDIR" && moodle-plugin-ci phplint)

  step "PHP Mess Detector"
  (cd "$MOODLE_WORKDIR" && moodle-plugin-ci phpmd) || warn "phpmd"

  step "Moodle Code Checker (phpcs --max-warnings 0)"
  (cd "$MOODLE_WORKDIR" && moodle-plugin-ci phpcs --max-warnings 0)

  step "Moodle PHPDoc Checker (--max-warnings 0)"
  (cd "$MOODLE_WORKDIR" && moodle-plugin-ci phpdoc --max-warnings 0)

  step "Validating"
  (cd "$MOODLE_WORKDIR" && moodle-plugin-ci validate)

  step "Check upgrade savepoints"
  (cd "$MOODLE_WORKDIR" && moodle-plugin-ci savepoints)

  step "Mustache Lint"
  (cd "$MOODLE_WORKDIR" && moodle-plugin-ci mustache)

  step "Grunt"
  (cd "$MOODLE_WORKDIR" && moodle-plugin-ci grunt --max-lint-warnings 0) || warn "grunt"

  step "PHPUnit tests (--fail-on-warning)"
  (cd "$MOODLE_WORKDIR" && moodle-plugin-ci phpunit --fail-on-warning)

  printf '\nCI PASS (%s, %s, %s) — Behat NOT run, see header comment.\n' \
    "$(git -C "$ROOT" rev-parse --short HEAD 2>/dev/null || echo unknown)" "$MOODLE_BRANCH" "$DB"
}

case "${1:-full}" in
  -h|--help|help)
    usage
    ;;
  stop)
    "${COMPOSE[@]}" down
    ;;
  full|"")
    run_full
    ;;
  *)
    echo "Unknown command: $1" >&2
    usage >&2
    exit 1
    ;;
esac
