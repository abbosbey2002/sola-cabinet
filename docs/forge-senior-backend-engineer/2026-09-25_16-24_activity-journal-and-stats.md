# Activity journal, statistics, tariff-change history (plan 2026-09-23 v2)

- **Date:** 2026-09-25 16:24
- **Scope:** implements `docs/plans/2026-09-23_activity-log-and-stats.md` in full.
  - Config / DB: `config/activity.php`, `config/database.php` (`activity` pgsql connection), `database/migrations/activity/*` (4 tables + rollups), `database/migrations/2026_09_25_100000_add_role_to_admins_table.php`.
  - Core: `app/Support/Activity/*` (ActivityEvent catalogue, Outcome, TariffChangeResult, DeniedReason, RequestContext, UserAgent, GeoLocator, ActivityRecorder, TariffChangeRecorder, ActivityRollup, ActivityStats, TariffChangeReport, AccountHistory, ReportPeriod, TariffChangeFilter), `app/Support/Admin/*` (AdminRole, AdminAbility, CurrentAdmin, AdminIdentity, PersonalData).
  - HTTP: `RecordActivity`, `AuditAdminAccess`, `EnsureAdminCan`, `EnsureAdminIsAuthenticated` (admin must still exist), `ActivityBeaconController` + request, admin `StatsController` / `TariffChangeController` / `AccountController` + filter requests; annotations in Auth/Device/TopUp/Payment/Traffic/Locale/Tariff controllers.
  - Commands: `activity:migrate`, `activity:rollup`, `activity:prune`, `admin:role`; `admin:create --role`.
  - Copy: `lang/{ru,uz,en}/admin.php`, billing codes 128–132 in `errors.php`.
  - JS: `resources/js/modules/activity.js`, `modal.js` emits `sola:modal-dismiss`.
- **Decisions:**
  - CEO answers: PostgreSQL; every §12 default accepted (ZRU-547 flag-gated, retention 12 m / 3 y, role table as proposed, offline GeoLite2, search text ≤100 chars, admins via artisan).
  - PostgreSQL only for the journal (`activity` connection); `admins` / `enabled_tariffs` stay in SQLite → no production data migration. Activity migrations in their own folder with their own migrations table; `activity:migrate` is a no-op while disabled so `deploy.sh` can always call it.
  - One row per request: middleware maps route → event and writes after the response; controllers only annotate. Keeps controllers one line per action.
  - `occurred_on` date column so every aggregate is the same SQL on PostgreSQL and SQLite (tests).
  - Funnels are cohorts (each step ⊂ previous step) — counted separately they exceeded 100 %.
  - Session id stored only as sha256 fingerprint (plan said "no hashing" for IP/etc.; a raw session id is a credential).
  - Tariff error flash now prefers our translated message (`ErrorMessages::forResponse`) so 129 says "top up and try again".
  - Own role gate (`AdminAbility` + `admin.can:`) instead of Laravel `Gate`: there is no User model / guard.
  - Snapshot and CSV masked for the analyst too — otherwise the raw snapshot bypassed the mask.
- **Bugs found in verification and fixed (each has a red→green regression test):**
  1. `defer()` skips 4xx/5xx by default → denied/throttled/503 events were lost → `->always()`.
  2. Middleware priority put `ThrottleRequests` before `RecordActivity` → 429 never recorded → `prependToPriorityList`.
  3. Scoped recorder / admin state leaked between requests in a long-lived app → `reset()` / `forget()` per request.
  4. `ReportPeriodRequest::segment()` clashed with `Request::segment()` → fatal on /admin/stats → renamed.
  5. Analyst could read unmasked phone/name/email in the snapshot → masked.
  6. Funnel step counts not cohort-based → fixed.
- **Verified:** PostgreSQL 18 (throwaway container) — migrations up/down, real subscriber flows via Playwright (desktop) recorded 20 event kinds + tariff_changes success/denied with aftermath, rollup idempotent, all report queries. Docker image builds with `pdo_pgsql`; compose `postgres` healthy, `activity:migrate` from the php container.
- **Pipeline results:** see "Gate" below (filled after reviewer/auditor).
- **Risks flagged:** personal data under ZRU-547 (flag stays off until lawyer sign-off); server needs PostgreSQL + `pdo_pgsql` + cron; `database/seeders/AdminSeeder.php` was changed outside this task (username `'   '`) — not touched, flagged to the CEO.
- **Left for later:** admin-management UI (artisan for now); real-time dashboard; search-term top list (meta JSON query differs per driver); pre-existing `composer audit` advisory on `league/commonmark`; pre-existing Pint issues in `SolaClient.php` and `docs/task/apipc/*`.
- **User must do:** see `docs/task/ACTIVITY_JOURNAL_DEPLOY.uz.md` — PostgreSQL + `php-pgsql`, `.env` ACTIVITY_*, cron `schedule:run`, optional GeoLite2 file, pg_dump backup, set roles for admins.
