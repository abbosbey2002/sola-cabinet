# Tariff timing modal: move the date from "now" to "next billing period"

- **Date:** 2026-08-28 10:44
- **Scope:**
  - `app/Http/Controllers/TariffController.php` — `index()`, `connect()`, new `connectionDate()`, doc updates
  - `resources/views/cabinet/tariff.blade.php` — removed the "now" hint's date logic
  - `lang/en/app.php`, `lang/ru/app.php`, `lang/uz/app.php` — removed unused `modal.next_charge_note`
  - `tests/Feature/CabinetTest.php` — updated 2 tests, added 2 new tests

- **Decisions:**
  - The "now" option's hint no longer shows any date (previously showed the subscriber's already-known next charge date as an FYI note via `next_charge_note`).
  - The "from next billing period" option now shows, and `connect()` actually bills against, the subscriber's real known next-charge date (`AbonentProfile::nextChargeDate() ?? ConnectedTariff::nextChargeDate()`) instead of a naive computed "1st of next month" (`nextPeriodStart()`). Falls back to `nextPeriodStart()` only when billing has told us neither date. This keeps the modal's displayed date and the actual billing date from ever diverging (user's explicit requirement).
  - `connect()` now also calls `$this->sola->connectedTariffs($accountId)` for the "month" timing branch (previously only `index()` did), to derive the same `ConnectedTariff` fallback — an accepted extra round trip, matching the existing pattern/comment in `index()`.
  - Added defensively: `connectionDate()` floors the resolved date at today (`->max(now()->startOfDay())`). `AbonentProfile::nextChargeDate()` reads billing's `charge_date` raw with no guarantee it is `>= today` (unlike `ConnectedTariff::nextChargeDate()`, which walks forward until it is) — this was safe while only used for display, but is now the literal date POSTed to `connectTariff()`, so a stale past date from billing must never reach billing. Both the code-reviewer and security-auditor agents independently flagged this; fixed rather than deferred.
  - Removed the now-unused `next_charge_note` translation key from all three locales rather than leaving dead code.

- **Pipeline results:**
  - `forge-code-reviewer`: **APPROVE**. One "should fix" warning (the stale-date guard above) — fixed.
  - `forge-security-auditor`: **SHIP**. One Medium finding (same stale-date issue) — fixed.
  - Full test suite: 140 passed / 505 assertions (was 139 before this task; net +1 test after the fix — 4 tests touched/added total in `CabinetTest.php`, minus 0 removed).

- **Risks flagged:** None outstanding — the one real risk found (past `charge_date` reaching billing) was fixed and covered by a regression test (`a_stale_past_charge_date_never_reaches_billing`).

- **Left for later:** None.

- **User must do:** Nothing — no migrations, no env vars, no deploy steps beyond the normal deploy.
