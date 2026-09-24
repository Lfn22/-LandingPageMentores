---
phase: 01-landing-captura-de-leads
plan: 01
subsystem: api

tags: [php, pdo, mysql, docker, apache, csrf, rate-limit, lgpd]

# Dependency graph
requires: []
provides:
  - "Docker local stack (php:8.2-apache + mariadb:10.11) and tests/e2e.sh proving the vertical slice"
  - "Shared bootstrap layer: app/bootstrap.php, lib/db.php, lib/session.php, lib/security.php reused by the future admin panel"
  - "Canonical schema (leads, users, rate_limits) in app/database/schema.sql"
  - "public/setup.php one-time install flow (tables + owner user + lock)"
  - "Lead capture endpoint (public/api/lead.php) with CSRF, honeypot, min-fill-time and per-IP rate limit"
  - "app/config/site.php brand/content contract consumed by the landing view"
affects: [01-02, phase-2-painel]

# Tech tracking
tech-stack:
  added: [php:8.2-apache, mariadb:10.11, pdo_mysql]
  patterns:
    - "Config-driven brand content: app/config/site.php is the single source the view and lib code read from"
    - "app/ lives outside the public docroot; public/_bootstrap.php resolves APP_PATH and requires app/bootstrap.php"
    - "Security check order in public/api/lead.php: method -> rate_limit -> csrf -> honeypot -> min_fill_seconds -> validation -> insert"
    - "Dual response mode in the lead endpoint: JSON when Accept has application/json, otherwise 303 + flash_set/flash_take for the no-JS path"

key-files:
  created:
    - docker-compose.yml
    - docker/Dockerfile
    - docker/config.docker.php
    - tests/e2e.sh
    - app/bootstrap.php
    - app/lib/db.php
    - app/lib/session.php
    - app/lib/security.php
    - app/lib/leads.php
    - app/config/config.example.php
    - app/config/site.php
    - app/database/schema.sql
    - app/views/lead-form.php
    - public/_bootstrap.php
    - public/.htaccess
    - public/setup.php
    - public/api/lead.php
    - public/index.php
  modified:
    - .gitignore

key-decisions:
  - "tests/e2e.sh uses a relative tests/.e2e-tmp/ directory for cookie jars and discard files instead of mktemp -d, because MSYS_NO_PATHCONV=1 (required for docker compose calls on Git Bash/Windows) also disables POSIX-to-Windows path translation for curl, which silently breaks -c/-b/-D/-o with absolute /tmp paths"
  - "Test POST bodies avoid accented characters in --data-urlencode literals: Git Bash on Windows transcodes multi-byte UTF-8 argv through the console codepage before invoking the native curl.exe, corrupting them into invalid UTF-8 for MySQL's utf8mb4 columns"

patterns-established:
  - "rate_limit_hit(pdo, action, ip, max, window) is the single per-IP throttle primitive reused for both lead submissions now and login in Phase 2"
  - "form_ts_issue()/form_ts_age() HMAC-signed timestamp as the minimum-fill-time anti-bot check"

requirements-completed: [INST-01, INST-02, LEAD-01, LEAD-02, LEAD-03, LEAD-04, LEAD-05, LEAD-06, LAND-02]

duration: 30min
completed: 2026-09-24
---

# Phase 1 Plan 1: Docker Stack + Instalação + Captura de Leads Summary

**Docker (PHP 8.2 + MariaDB 10.11) local stack, one-time `setup.php` installer, and a PDO-backed lead capture endpoint with CSRF/honeypot/rate-limit anti-spam, proven by an end-to-end `tests/e2e.sh`.**

## Performance

- **Duration:** ~30 min
- **Started:** 2026-09-24T13:50:00Z (approx.)
- **Completed:** 2026-09-24T14:19:00Z
- **Tasks:** 3 completed
- **Files modified:** 21 (20 created, 1 modified: `.gitignore`)

## Accomplishments
- Reproducible local environment: `docker-compose.yml` + `docker/Dockerfile` (php:8.2-apache with pdo_mysql, mariadb:10.11), matching the Hostinger shared-hosting layout (docroot = `public/`, `app/` outside it).
- `public/setup.php` installs a fresh copy: runs `app/database/schema.sql` (leads, users, rate_limits — all `IF NOT EXISTS`), creates the owner user with `password_hash`, writes `app/storage/setup.lock`, and then blocks itself with 403.
- Visitors submit the contact form at `/` (with or without JavaScript) and the lead is stored with consent (`consent_at`, `consent_ip`, `consent_text` from the server, not the client), IP, user agent, optional interests (validated against `app/config/site.php` products, stored as JSON), and `status = 'novo'`.
- Anti-abuse layered in `public/api/lead.php`, in this order: method check (405) → `rate_limit_hit` per IP/action (429) → CSRF (403) → honeypot (fake 200, no insert) → minimum fill time via HMAC-signed `form_ts` (422 `too_fast`) → server-side validation (422 `validation`) → insert (500 on unexpected DB error).
- Post-submit confirmation renders a `https://wa.me/` link with the lead's first name pre-filled into the message.
- `app/config/site.php` carries a realistic fictional mentor ("Marina Costa" / "Rota Clara") with all the brand/content keys the interface contract requires (colors, fonts, products, testimonials, FAQ, WhatsApp, LGPD texts, social).
- `tests/e2e.sh` exercises the whole slice against the live Docker stack (curl + SQL assertions, no `jq`) and prints `E2E OK`.

## Task Commits

Each task was committed atomically:

1. **Task 1: Docker environment and failing e2e test** - `760f904` (feat) — confirmed RED (script failed as expected, no app code existed yet)
2. **Task 2: Install flow — shared layer, schema, setup.php** - `dd270f9` (feat)
3. **Task 3: Lead capture — brand config, form, API, confirmation** - `e9ee690` (feat) — confirmed GREEN (`tests/e2e.sh` prints `E2E OK`, exit code 0)

**Plan metadata:** (this commit, docs)

## Files Created/Modified
- `docker-compose.yml`, `docker/Dockerfile`, `docker/config.docker.php` - local PHP+MariaDB stack matching the Hostinger docroot layout
- `tests/e2e.sh` - end-to-end proof: setup, lead capture (JSON + no-JS), honeypot, CSRF, min-fill-time, validation, rate limit
- `app/bootstrap.php` - loads `APP_CONFIG_FILE`, exposes `app_config()`/`site_config()`, wires the shared libs and starts the session
- `app/lib/db.php` - `db()` PDO singleton (prepared statements only, `EMULATE_PREPARES=false`), `now_db()`
- `app/lib/session.php` - `session_start_secure()`, `flash_set()`/`flash_take()`
- `app/lib/security.php` - `e()`, CSRF, `form_ts_issue()`/`form_ts_age()`, `client_ip()`, `rate_limit_hit()`, CSP nonce + security headers, `valid_hex_color()`
- `app/lib/leads.php` - `lead_validate()`, `lead_insert()`, `whatsapp_url()`
- `app/config/config.example.php` - config template (db, app_secret, setup_token, rate limits)
- `app/config/site.php` - mentor brand/content (LAND-02 source of truth)
- `app/database/schema.sql` - canonical `leads`/`users`/`rate_limits` schema
- `app/views/lead-form.php` - form partial + post-submit WhatsApp confirmation
- `public/_bootstrap.php`, `public/.htaccess`, `app/.htaccess` - entrypoint + protection of dotfiles/sql/config
- `public/setup.php` - one-time installer
- `public/api/lead.php` - lead capture endpoint (JSON and no-JS/flash+303 modes)
- `public/index.php` - minimal landing page wiring brand + form (expanded by plan 01-02)
- `.gitignore` - ignores `app/config/config.php`, `app/storage/*`, `tests/.e2e-tmp/`

## Decisions Made
- Kept every file minimal and direct per explicit user guidance mid-execution ("sem muita complicação... mas não abra mão da segurança") — no extra abstractions beyond what the plan's interface contract required, while keeping prepared statements, `e()` escaping, CSRF, `password_hash`, honeypot and rate limiting intact.
- `tests/.e2e-tmp/` (relative path) instead of `mktemp -d` for all curl cookie jars/headers/discard files — see deviation below.
- Test POST literals kept ASCII-only where they cross into `curl --data-urlencode` — see deviation below.

## Deviations from Plan

### Auto-fixed Issues

**1. [Rule 3 - Blocking] `tests/e2e.sh` cookie jars silently failed under Windows Git Bash**
- **Found during:** Task 2 verification (manual setup.php POST reproduction)
- **Issue:** The plan's own guidance requires `export MSYS_NO_PATHCONV=1` in `tests/e2e.sh` for `docker compose` calls, but this environment variable disables MSYS's POSIX→Windows path translation for *every* subprocess, including `curl`. With paths from `mktemp -d` (absolute, `/tmp/...`-style), curl's native Windows binary could not resolve `-c`/`-b`/`-D`/`-o` targets, so cookies were never persisted and CSRF tokens read back as empty — the script died silently on the very next command substitution.
- **Fix:** Switched `tests/e2e.sh` to a relative `tests/.e2e-tmp/` directory (created explicitly, cleaned via `trap ... EXIT`) for every cookie jar, header dump and discard file. Relative paths aren't subject to MSYS's path-conversion logic, so they resolve identically with or without `MSYS_NO_PATHCONV`.
- **Files modified:** `tests/e2e.sh`, `.gitignore` (added `tests/.e2e-tmp/`)
- **Verification:** Re-ran the setup POST manually — cookie persisted, CSRF round-tripped correctly.
- **Committed in:** `dd270f9` (Task 2 commit)

**2. [Rule 1 - Bug] `grep`-based field extraction aborted the script under `set -e`/`pipefail` when a field was absent**
- **Found during:** Full `tests/e2e.sh` run after Task 3 (script died silently right after Step 3, no `FALHOU:` message)
- **Issue:** `get_form()` extracts `form_ts` via a `grep | head | sed` pipeline. `/setup.php`'s form has no `form_ts` field, so `grep` found no match and returned exit 1; with `pipefail`, the whole pipeline's exit code was 1, and under `set -e` the `FORM_TS=$(...)` assignment aborted the entire script with no diagnostic.
- **Fix:** Appended `|| true` to the `CSRF`, `FORM_TS` and `FIRST_INTEREST` extraction pipelines in `get_form()`, so a missing field yields an empty string instead of killing the script; the script's own `[ -n "$CSRF" ] || fail ...` guards then produce a proper diagnostic if something is genuinely missing.
- **Files modified:** `tests/e2e.sh`
- **Verification:** Re-ran the full suite; it now proceeds past Step 3 as expected.
- **Committed in:** `e9ee690` (Task 3 commit)

**3. [Rule 1 - Bug] Accented `--data-urlencode` literal broke the no-JS lead test (MySQL rejected the bytes)**
- **Found during:** Full `tests/e2e.sh` run, Step 5 ("lead feliz sem JavaScript")
- **Issue:** The literal `"message=Quero mais informações"` passed as a `curl --data-urlencode` argument got its accented bytes corrupted in transit (Git Bash → native `curl.exe` argv, likely via the console codepage), arriving as invalid UTF-8 (`\xE7\xF5es`) at the PDO `utf8mb4` connection. `lead_insert()` raised `SQLSTATE[22007] Incorrect string value`, was caught, and returned 500 — so the endpoint never issued the expected `303 Location: ...enviado=1` redirect, breaking the assertion.
- **Fix:** Replaced the literal with the ASCII equivalent `"Quero mais informacoes"`. This is test-only data with no bearing on the product's real UTF-8 handling (the app itself correctly declares `utf8mb4`/`ENT_QUOTES, UTF-8` throughout); it only avoids a Windows/Git-Bash argv encoding quirk in the harness.
- **Files modified:** `tests/e2e.sh`
- **Verification:** Full `tests/e2e.sh` run completed with exit code 0 and printed `E2E OK`.
- **Committed in:** `e9ee690` (Task 3 commit)

---

**Total deviations:** 3 auto-fixed (1 blocking test-harness bug, 2 bugs in the test script itself — none in the shipped `app/`/`public/` product code)
**Impact on plan:** All three fixes were necessary to get a truthful, working `tests/e2e.sh` on this Windows/Git-Bash/Docker environment. No scope creep — no product code changed as a result of these fixes.

## Issues Encountered
- Port 8080 (the plan's default `LPM_PORT`) was already bound by an unrelated project's container on this machine; verification runs used `LPM_PORT=8091` instead. Production/Hostinger deployment is unaffected since `LPM_PORT` only matters for local Docker; the plan's default remains 8080 in `docker-compose.yml`.

## User Setup Required

None - no external service configuration required. Local verification used Docker Desktop with already-pulled `php:8.2-apache` and `mariadb:10.11` images.

## Next Phase Readiness
- Shared layer (`app/bootstrap.php`, `lib/db.php`, `lib/session.php`, `lib/security.php`) and the `users`/`rate_limits` schema are ready for the Phase 2 admin panel (login can reuse `rate_limit_hit(..., 'login', ...)` and `session_start_secure()` as-is).
- Plan 01-02 can now expand `public/index.php` into the full landing page (hero, about, products, testimonials, FAQ) — `app/config/site.php` already carries all that content, and `app/views/lead-form.php` is ready to be dropped into a richer page. No CSS/JS assets were created in this plan (out of scope here); the honeypot field currently only has an inline style for visual hiding, expected to be picked up by 01-02's stylesheet.
- No blockers.

---
*Phase: 01-landing-captura-de-leads*
*Completed: 2026-09-24*

## Self-Check: PASSED

All 19 files created/modified in this plan verified present on disk; all 3 task commits (`760f904`, `dd270f9`, `e9ee690`) verified in `git log`.
