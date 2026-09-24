---
phase: 02-painel-do-dono
plan: 01
subsystem: auth

tags: [php, pdo, session, csrf, rate-limit, admin-panel]

# Dependency graph
requires:
  - phase: 01-landing-captura-de-leads
    provides: "app/bootstrap.php, lib/{db,session,security,leads}.php, users/leads/rate_limits schema, tests/e2e.sh seeding dono@example.com + Ana Souza/Bruno Lima"
provides:
  - "Secure owner login/logout at /admin/ (password_verify, session_regenerate_id, timing-safe unknown-user path, rate-limited 5/900s)"
  - "app/lib/auth.php: admin_headers, auth_login, auth_user, auth_require, auth_logout"
  - "app/lib/admin.php: admin_kpis, admin_radar (product-aggregation or recent-messages fallback), admin_leads (parameterized LIKE search), format_phone"
  - "Panel view (KPIs, Radar de demanda, Fila de trabalho with search) matching painel-admin-ref.png"
  - "tests/e2e-admin.sh proving the full login -> panel -> search -> logout -> rate-limit slice"
affects: [02-02-painel-do-dono]

# Tech tracking
tech-stack:
  added: []
  patterns:
    - "admin_headers() wraps send_security_headers() + X-Robots-Tag noindex + Cache-Control no-store for every /admin/* response"
    - "Radar de demanda has two modes: aggregate by site.php products when present, otherwise fall back to the 5 most recent non-empty lead messages (site.php currently ships products: [])"
    - "Session-based auth: uid + last_seen in $_SESSION, 2h idle timeout (AUTH_IDLE_SECONDS), session_regenerate_id(true) + fresh csrf_token only on successful login"

key-files:
  created:
    - app/lib/auth.php
    - app/lib/admin.php
    - public/admin/index.php
    - public/admin/logout.php
    - app/views/admin/login.php
    - app/views/admin/panel.php
    - public/assets/css/admin.css
    - tests/e2e-admin.sh
  modified: []

key-decisions:
  - "Radar de demanda works without products: since site.php's products list is empty for this brand, it shows the 5 most recent lead messages (truncated ~120 chars, escaped) with name/date instead of an empty 'sem produtos' state"
  - "Search (?q=) covers name, email and message (not phone/interests) per updated brand guidance, since interests are effectively unused with an empty products list"
  - "Constant-time login: unknown e-mails are checked against a fixed bcrypt hash (AUTH_DUMMY_HASH) so password_verify always runs, avoiding a timing side-channel that reveals valid accounts"

patterns-established:
  - "rate_limit_hit(pdo, 'login', ip, 5, 900) called before csrf_verify, before auth_login, on every POST to /admin/ regardless of outcome"

requirements-completed: [PAIN-01, PAIN-02, PAIN-04, PAIN-05, PAIN-06, PAIN-09]

# Metrics
duration: 45min
completed: 2026-09-24
---

# Phase 2 Plan 1: Login seguro + KPIs/Radar/Fila do painel do dono Summary

**Session-based admin login at `/admin/` (bcrypt + rate-limited + CSRF) rendering real KPIs, a demand radar that gracefully falls back to recent lead messages when no products are configured, and a searchable lead queue — all proven by `tests/e2e-admin.sh`.**

## Performance

- **Duration:** ~45 min
- **Started:** 2026-09-24 (approx, see git commit timestamps)
- **Completed:** 2026-09-24
- **Tasks:** 3 completed
- **Files modified:** 8 (all created)

## Accomplishments
- The owner logs in at `/admin/` with e-mail + password; wrong credentials always show the generic "E-mail ou senha inválidos", and the 6th attempt within 15 minutes gets HTTP 429 (`rate_limit_hit('login', 5, 900)`).
- Successful login calls `session_regenerate_id(true)`, drops the pre-login CSRF token, records `last_login_at`, and starts a 2-hour idle-timeout session (`AUTH_IDLE_SECONDS`).
- Logged in, the owner sees 3 KPIs (Inscritos no total / Novos nos últimos 7 dias / Inscritos hoje), a "Radar de demanda" (falls back to the 5 most recent lead messages since `site.php`'s `products` list is empty), and a "Fila de trabalho" table (Nome, Contato with tel:/mailto:/WhatsApp links, Demandas, Status) ordered most-recent-first.
- `?q=` searches name, email and message via a parameterized `LIKE` (percent/underscore escaped) — verified to isolate "Bruno Lima" without touching "Ana Souza".
- "Sair" is a POST form with CSRF that calls `auth_logout()` (session wipe + cookie expiry + `session_destroy()`); the same browser session then only sees the login form again.
- Visual follows `.planning/references/painel-admin-ref.png`: dark ardósia (#263039) page background, white rounded card (16px), pill button, bordered KPI cards, thin-border table, centered LGPD footer notice, Archivo/Inter fonts.
- Every `/admin/*` response sends `X-Robots-Tag: noindex, nofollow` and `Cache-Control: no-store` via `admin_headers()`.

## Task Commits

Each task was committed atomically:

1. **Task 1: e2e do painel (RED)** - `d96ae8c` (test) — confirmed RED: script failed with 404 (public/admin/ did not exist yet)
2. **Task 2: auth + login/logout** - `5666d5e` (feat)
3. **Task 3: painel (KPIs, radar, fila com busca) no visual da referência** - `a7e9728` (feat) — confirmed GREEN (`tests/e2e-admin.sh` prints `E2E ADMIN OK`, exit code 0)

**Plan metadata:** (this commit, docs)

## Files Created/Modified
- `app/lib/auth.php` - `admin_headers()`, `auth_login()` (constant-time via `AUTH_DUMMY_HASH`, `session_regenerate_id(true)`, `last_login_at` update), `auth_user()` (2h idle timeout), `auth_require()`, `auth_logout()`
- `app/lib/admin.php` - `admin_kpis()`, `admin_radar()` (product mode or recent-messages fallback), `admin_leads()` (parameterized `LIKE` search on name/email/message), `format_phone()`
- `public/admin/index.php` - GET renders login or panel depending on `auth_user()`; POST runs rate_limit → CSRF → `auth_login()` in that order
- `public/admin/logout.php` - POST-only, `auth_require()` + CSRF, then `auth_logout()` and 303 redirect
- `app/views/admin/login.php` - login form (e-mail/senha/CSRF), noindex meta
- `app/views/admin/panel.php` - KPIs, Radar de demanda, Fila de trabalho with search form and table, Copiar CSV/Baixar CSV/Sair actions, LGPD footer
- `public/assets/css/admin.css` - dark background, white card, KPI grid, table, search input, responsive breakpoint at 640px
- `tests/e2e-admin.sh` - runs `tests/e2e.sh` first, then covers (a) unauthenticated `/admin/`, (b) wrong password, (c) successful login + panel content + `last_login_at`, (d) search isolation, (e) logout, (f) rate limit 429 on the 6th attempt

## Decisions Made
- Radar de demanda's "no products" fallback (5 most recent non-empty messages) was added per updated brand guidance (site.php `products: []` for Conceição Melo) rather than the original plan's product-only aggregation, since with an empty product list the radar would otherwise always show the empty state even with real demand data.
- Search field list narrowed to name/email/message (dropped phone/interests) to match the same updated guidance — interests are effectively unused while `products` stays empty.
- "Copiar CSV" and "Baixar CSV" are rendered as inert markup (`data-copy-csv`, link to `/admin/export.php`) with no wiring yet — CSV export is explicitly out of scope for this plan (deferred to 02-02 per the phase CONTEXT.md file list).

## Deviations from Plan

### Auto-fixed Issues

**1. [Rule 1 - Bug] Test script's search assertion incorrectly flagged the Radar de demanda as part of the search results**
- **Found during:** Task 3 (`tests/e2e-admin.sh` run after implementing the panel)
- **Issue:** My own e2e-admin.sh step (d) asserted the full `/admin/?q=bruno` HTML must not contain "Ana Souza" anywhere on the page. But "Radar de demanda" (recent-messages fallback) intentionally always shows the 5 most recent messages regardless of the `q` search — it is a separate, unfiltered widget, not part of the search results. The assertion was too broad and failed against correct app behavior.
- **Fix:** Scoped the exclusion assertion to only the HTML after the "Fila de trabalho" heading (`${SEARCH#*Fila de trabalho}`), so it verifies the search table itself, not the always-visible radar above it.
- **Files modified:** `tests/e2e-admin.sh`
- **Verification:** Re-ran `tests/e2e-admin.sh`; step (d) and the full suite now pass, ending in `E2E ADMIN OK`.
- **Committed in:** `a7e9728` (Task 3 commit)

---

**Total deviations:** 1 auto-fixed (test-only bug, no product code affected)
**Impact on plan:** No scope creep — the fix corrected a false assumption baked into the test itself, not app behavior.

## Issues Encountered
- None beyond the test-assertion bug documented above.

## User Setup Required

None - no external service configuration required. Verified locally against the Docker stack on `http://localhost:8091` (owner credentials from `tests/e2e.sh`: `dono@example.com` / `SenhaForte123!`).

## Next Phase Readiness
- `app/lib/auth.php` and `app/lib/admin.php` are ready to be extended by plan 02-02 (status updates via `leads.status`, CSV export at `/admin/export.php`, "Copiar CSV" wiring) — the panel already renders the `data-copy-csv` button and the `/admin/export.php` link as inert placeholders for that plan to complete.
- No blockers. Docker stack left running (`docker compose up -d`, `LPM_PORT=8091`) so the user can open `http://localhost:8091/admin/` directly.

---
*Phase: 02-painel-do-dono*
*Completed: 2026-09-24*

## Self-Check: PASSED

All 8 files created in this plan verified present on disk; all 3 task commits (`d96ae8c`, `5666d5e`, `a7e9728`) verified in `git log`.
