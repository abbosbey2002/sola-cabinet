# Admin tariff panel — tests

- **Date:** 2026-08-18 13:52
- **Coverage summary:**
  - `tests/Feature/Admin/AdminAuthTest.php` (6 tests) — guard redirect, wrong password, unknown username, correct password, guest-redirect-when-already-signed-in, logout expires the cookie.
  - `tests/Unit/TariffVisibilityTest.php` (6 tests) — default-enabled, disable/enable round trip, double-disable is a no-op, disabledIds() with multiple rows, filter() against string vs int tariff_id (the real type-juggling risk at that seam), filter() is a no-op when nothing is disabled.
  - `tests/Feature/CabinetTest.php` (+1 test) — `a_tariff_the_admin_disabled_is_refused_even_though_billing_offers_it`: seeds a real `disabled_tariffs` row and proves `TariffController::connect()` 403s, sibling to the existing "never offered to this account" test. This is the actual security property — hiding a tariff from the page is cosmetic unless posting its id directly is also blocked.
- **Failure scenarios included:** wrong credentials, unknown username (enumeration-safe — same generic error both ways), unauthenticated access, already-authenticated guest redirect, idempotent toggle (double-disable), type mismatch (string vs int tariff_id) on the filter boundary, and the direct-POST bypass attempt on a hidden tariff.
- **Deliberately NOT tested:**
  - The `throttle:5,1` on `POST /admin/login` — the existing subscriber login throttle isn't tested anywhere in this codebase either (checked `tests/Feature/AuthTest.php`), so no new precedent was invented for the admin side. Framework behavior, not app logic.
  - Live SOLA API behavior in `AdminTariffController::index()` (empty state, real 400/110 handling) — covered manually in `docs/forge-debugger/2026-08-18_13-45_admin-tariff-panel-verification.md`, not re-covered here because it would need `Http::fake()` for `/tariff/available` and the interesting part (an admin picking a different `?acc_id=`) has no business-logic branch worth a dedicated test beyond what `TariffVisibility` already covers.
- **Known gaps / next tests:** none identified. If a second admin-only screen is added later, `AdminAuthTest`'s guard-redirect test is the pattern to extend rather than re-derive.
- **Suite result:** `php artisan test` — 89 → **102 passed** (435 → 436... final run 436 assertions), 0 failed. `./vendor/bin/pint --test` on the three touched/added files — passed. `disabled_tariffs` and `admins` row counts confirmed unchanged (0 and 1 — the one real seeded admin account — respectively) after the full suite, via `DatabaseTransactions` on all three test classes.
