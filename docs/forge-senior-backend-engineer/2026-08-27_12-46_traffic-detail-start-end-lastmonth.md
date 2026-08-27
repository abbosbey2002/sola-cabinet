# Traffic detail_begin/detail_end range + last-month default

- **Date:** 2026-08-27 12:46
- **Scope:**
  - `app/Support/Period.php` — added `lastMonth()` (rolling trailing month) and `detailBegin()`/`detailEnd()` (`d.m.Y`, mirrors `paymentsStart()`/`paymentsEnd()`); updated class docblock.
  - `app/Services/Sola/SolaClient.php` — `trafficDetail()` signature changed from `(accountId, month)` to `(accountId, begin, end)`; sends `detail_begin`/`detail_end` instead of `detail_month`.
  - `app/Services/Sola/FakeSolaServer.php` — local dev stub: renamed `fromPayDate()` → `fromDmyDate()` (now shared by payments and traffic), `trafficDetail()` reads `detail_begin`/`detail_end`, removed now-unused `daysOf()`.
  - `app/Support/BillingHistory.php` — `traffic()` switched from a `Period::months()` loop (one SOLA call per month, partial-failure-tolerant) to one direct range call that fails closed to `incomplete: true`, mirroring `payments()`. Class docblock updated.
  - `app/Http/Controllers/TrafficController.php` — `index()` default changed from `Period::currentMonth()` to `Period::lastMonth()`. `PaymentController`/`CabinetController` untouched, still `currentMonth()`.
  - Tests: `tests/Unit/SolaClientTest.php`, `tests/Unit/FakeSolaServerTest.php`, new `tests/Unit/BillingHistoryTrafficTest.php` (mirrors `BillingHistoryPaymentsTest.php`), new `tests/Unit/PeriodTest.php`, `tests/Feature/CabinetTest.php`.
  - Docs: `docs/api/SOLA_API.md`, `docs/api/SOLA_API_REFERENCE.md` — updated the `/traffic/detail` contract description; kept the old measured "date range ignored" fact as historical record but noted it applied to the old `detail_month`-only contract and hasn't been independently re-measured against `detail_begin`/`detail_end`.

- **Decisions:**
  - Followed the `pay_begin`/`pay_end` migration (commit 5819d9b) as the template end-to-end: same field naming convention, `d.m.Y` date format, fail-closed-on-error semantics, defensive `contains()` trim kept even though billing now answers the exact range.
  - User confirmed with billing/SOLA that `detail_begin`/`detail_end` works upstream (analogous to the earlier `pay_begin`/`pay_end` confirmation) — proceeded without further confirmation. This directly contradicts a previously *measured* fact in `docs/api/SOLA_API_REFERENCE.md` (`date_from`/`date_to` silently ignored) — that measurement was against the old `detail_month`-only contract with different field names, so it doesn't necessarily contradict the new one, but neither the date format (`d.m.Y`, assumed from the payments precedent) nor the new field names have been independently verified against the live server in this session.
  - `lastMonth()` default = rolling `now()->subMonth()` → `now()->endOfDay()` (not previous-calendar-month), scoped to the traffic page only, per user's explicit answers.
  - Kept `Period::months()` even though nothing in production calls it anymore (mirrors the same call made for `payments()` in the prior migration — it's still used as a test-fixture convenience).

- **Pipeline results:**
  - `forge-code-reviewer`: 1 blocker — `lastMonth()` built its start from `subMonth()` without `startOfDay()`, so `Period::contains()` would non-deterministically drop early-in-the-day rows on the boundary date depending on what time of day the subscriber loaded the page (the outbound SOLA request itself was unaffected, since `detailBegin()` formats date-only — only the client-side `contains()` trim was wrong). Fixed: `$now->subMonth()->startOfDay()`. Added `tests/Unit/PeriodTest.php` pinning the boundary. Re-verified via full test suite; did not re-run the reviewer since the fix was a one-line, low-risk correction matching an existing pattern (`between()` already uses `startOfDay()`).
  - `forge-security-auditor`: SHIP, no critical/high findings. Two low/hardening notes, both accepted as-is:
    1. `traffic()` failing closed (returns `incomplete: true`, no partial rows) on a SOLA error is an intentional behavior change from the old per-month loop (which could return partial data if only some months failed) — this mirrors `payments()`'s existing behavior and was a deliberate part of the migration, not an oversight.
    2. Minor Period.php doc/consistency nitpick, no action needed.

- **Risks flagged:**
  > ⚠️ RISKS:
  > - The `detail_begin`/`detail_end` field names and `d.m.Y` date format sent to SOLA are based on the payments-migration precedent, not an independently verified live-server response in this session — confirm the first real traffic request against production billing returns the expected `detail[]` rows and doesn't silently ignore the range (the way it used to for `date_from`/`date_to`).
  > - `traffic()` now fails closed on any SOLA error for the whole requested range (previously: partial results if only some months in a multi-month request failed). Expected/intended per the payments precedent, but worth knowing if a wide-range traffic page suddenly shows nothing instead of a partial table during a billing hiccup.

- **Left for later:** `Period::months()` is dead in production code (no caller after this migration) but kept for now, matching the prior payments migration's posture — flag for a future cleanup pass if it stays unused.

- **User must do:** Nothing (no migrations/env vars). Recommend watching the first live `/statistics` page loads after deploy to confirm SOLA actually honors `detail_begin`/`detail_end` given the unverified-contract risk above.
