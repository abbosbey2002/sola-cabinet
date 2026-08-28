# Home page hero card: remove day-meter calendar, add billing login field

- **Date:** 2026-08-28 14:28
- **Scope:**
  - `app/Support/AbonentSession.php` — modified (new field + flush() entry)
  - `app/Http/Controllers/Auth/AuthController.php` — modified (capture point)
  - `app/Http/Controllers/CabinetController.php` — modified (pass-through + stale comment fix)
  - `app/Support/ChargeCycle.php` — modified (docblock only, no behavior change)
  - `resources/views/cabinet/index.blade.php` — modified (removed meter, added login row)
  - `resources/views/components/day-meter.blade.php` — deleted (orphaned)
  - `resources/css/app.css` — modified (removed meter-only CSS + custom properties)
  - `tests/Feature/CabinetTest.php` — modified (retired/updated meter tests, new login tests)
  - `tests/Feature/AuthTest.php` — modified (billing_login capture + logout coverage)

## Decisions

User asked to remove the calendar/ring day-meter from the Home page hero card
("On your account") and show balance, next-charge date (both already there,
unaffected) and a "login" field. Clarified with user which "login": they
specified billing's own `/identify` response field (`accs[].login`, confirmed
via `docs/api/SOLA_API.md` and the reverse-engineered gateway source
`docs/task/apipc/main.php:152-160`, sourced from Oracle column `LOGIN`) — not
`AbonentSession::login()` (which is the phone typed at sign-in, used for OTP
verification, `AuthController::verify()` line 61) and not
`AbonentProfile::phone()` (billing's contact-phone field).

This meant a new session field was required (`AbonentSession::billingLogin()`
/ `setBillingLogin()`, cookie `billing_login`), captured at the single choke
point that sets the active account (`AuthController::selectAccount()`,
reached from both `login()` and `switchAccount()`), and added to `flush()`'s
cleared-cookie list to avoid leaking to the next subscriber on a shared
browser. RISK=true because this touches the auth/session cookie-writing flow,
even though the actual change is additive and low-risk (same encrypted-cookie
mechanism as every existing field, auto-escaped Blade output).

Removed `<x-day-meter>` and its caption row (cycle start date / days-left
status / cycle end date) entirely, per explicit user confirmation ("hammasi
olib tashlansin"). Deleted the now-orphaned `day-meter.blade.php` and its
dedicated CSS/custom properties after repo-wide grep confirmed no other
callers. Left `ChargeCycle`'s now-unused public API (`start`, `totalDays`,
`currentDay`, `daysLeft`, `isOverdue()`, `isChargeDay()`) in place rather than
trimming it — it's a correct, independently-tested value object and trimming
it is a separate refactor decision beyond this task's scope, not something to
do as a side effect of one caller no longer reading it. Fixed the docblocks on
`ChargeCycle` and `CabinetController::index()` that referenced the meter,
since a reviewer flagged them as now factually misleading.

Deliberately did NOT clean up the now-unreferenced translation keys
(`app.dash.days_left`, `days_left_unit`, `charge_today`, `charge_passed`) —
low-risk to leave, out of scope for this task, flagged below for later.

## Pipeline results

- `forge-code-reviewer`: **APPROVE**. Two warnings, both addressed except the
  lang-key one (see Left for later): stale `ChargeCycle`/`CabinetController`
  comments referencing the removed meter — fixed; unreferenced day-meter lang
  keys — left, logged below.
- `forge-security-auditor`: **SHIP**. No critical/high/medium findings.
  Confirmed: `billing_login` uses the same encrypted+signed cookie mechanism
  as every existing session field (no new attack surface), Blade
  auto-escapes the output (no XSS), `selectAccount()` is only reached after
  `switchAccount()`'s existing ownership check, `flush()` genuinely clears
  the new cookie on logout (verified against the actual `logout()` code
  path, not just the comment). One low/hardening note (non-blocking): the
  `(string) ($account['login'] ?? '')` cast would print the literal string
  "Array" if billing ever sent `login` as a nested structure instead of a
  scalar — cosmetic only, not exploitable, not fixed.

## Risks flagged

None beyond what's already in the audit above. The `(string)` cast edge case
is worth knowing about but not worth a defensive `is_string()` check for a
cosmetic-only failure mode.

## Left for later

- Unreferenced translation keys `app.dash.days_left`, `days_left_unit`,
  `charge_today`, `charge_passed` in `lang/{uz,ru,en}/app.php` — orphaned by
  this change, low cost to leave, worth a cleanup pass later.
- `ChargeCycle`'s `start`/`totalDays`/`currentDay`/`daysLeft`/`isOverdue()`/
  `isChargeDay()` are unused outside its own unit tests now that the meter is
  gone — kept intentionally (see Decisions); revisit if a future feature
  doesn't pick them back up.

## User must do

Nothing beyond deploying the changed files — no migration, no env var, no
config change. Existing signed-in sessions will show no login row until they
next log in or switch accounts (the cookie is empty for sessions that predate
this field — handled gracefully, row simply doesn't render).
