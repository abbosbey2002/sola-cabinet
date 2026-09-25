# Activity journal tests

- **Date:** 2026-09-25 16:24
- **Coverage summary:**
  - Feature `tests/Feature/Activity/TariffChangeHistoryTest.php` (10): success row with som prices + aftermath, 129 → insufficient_funds + translated flash, other code → billing_error, unreachable billing → unavailable + 503 (never pending), four denial reasons, broken activity DB never blocks the switch, flag off writes nothing.
  - Feature `tests/Feature/Activity/ActivityJournalTest.php` (16): menu pages with account/device/screen, hits vs uniques (per account), billing failure code, 403 denied, validation invalid, filter meta, throttled sign-in → rate_limited, SMS code/session never stored, first pick vs switch, broken DB never breaks a page, beacon (accepted, catalogue-only 422, guest redirect, throttle 429).
  - Feature `tests/Feature/Activity/ActivityCommandsTest.php` (5): rollup uniques per bucket + idempotent, bad date, prune retention per table, stale pending → unknown, cohort sign-in funnel.
  - Feature `tests/Feature/Admin/ActivityAdminTest.php` (42 incl. 30-case access matrix): role screens, unknown role fails closed, deleted admin loses access, role landing page, analyst masking (list, detail, snapshot), sales full data but no technical detail, CSV masking/raw, CSV formula guard, admin_audit incl. 403s, filters, bad filter no redirect loop, journal-off notice, account history.
  - Unit `tests/Unit/Activity/*` (UserAgent 10, PersonalData 4, ReportPeriod + result mapping 5).
- **Failure scenarios included:** invalid input (beacon, filters, dates), auth (roles, guest, deleted admin), external failure (billing 400 codes, connection drop → 503), partial failure (activity DB missing tables), rate limiting, privacy (secrets not stored, masking).
- **Red→green proof** (fix reverted → test fails, restored → passes): defer `always()`, priority before ThrottleRequests, per-request recorder reset, snapshot masking, cohort funnel, CSV formula guard — 6/6.
- **Deliberately NOT tested & why:** CSRF 419 on the beacon — Laravel skips CSRF verification when running unit tests, so it cannot fail here (covered by the route being in the `web` group). GeoIP lookup with a real .mmdb — no licensed file in the repo. PostgreSQL-specific SQL — verified manually against PostgreSQL 18; the suite runs on in-memory SQLite.
- **Known gaps / next tests:** browser JS of `activity.js` (verified with Playwright manually, not in CI).
- **Suite result:** `php artisan test` → 289 passed (1131 assertions), was 197.
