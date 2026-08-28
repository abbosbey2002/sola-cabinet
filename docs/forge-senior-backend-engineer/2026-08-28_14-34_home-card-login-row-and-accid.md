# Home page hero card: bigger login row + account id

- **Date:** 2026-08-28 14:34
- **Scope:**
  - `app/Http/Controllers/CabinetController.php` — modified (pass `accountId` to the view)
  - `resources/views/cabinet/index.blade.php` — modified (login row layout + account id column)
  - `tests/Feature/CabinetTest.php` — modified (renamed/extended two tests)

## Decisions

Follow-up to the same-day `billing_login` feature (see
`2026-08-28_14-28_home-card-remove-calendar-add-login.md`). User feedback: the
login row rendered too small next to the rest of the card, and asked for the
account id (accId) to also show.

Restructured the row from a single small inline `label: value` line into a
two-column block matching the existing balance/next-charge visual pattern
already used higher in the same card (`u-label` above, `text-lg
font-semibold` value below) — no new CSS, reused what the card already does.
For the account id, reused `app.accounts.personal` verbatim rather than
inventing a new label: `partials/topbar.blade.php`'s mobile drawer already
shows the exact same `account` session cookie under that label, so this is
the existing analog, not a new naming decision. `$accountId` was already
computed in `CabinetController::index()` (`$this->accountId()`, used
elsewhere in the same method) — no new data source, just threaded one more
value through to the view. RISK=false: no new session/auth writes, purely
reads an already-established, always-populated session value into one more
view.

## Pipeline results

- `forge-code-reviewer`: **APPROVE**. One non-blocking nitpick: the outer
  `@if ($billingLogin !== '' || $accountId !== '')` guard is realistically
  always-true since `accountId()` is required session state for any request
  reaching this page — harmless, not changed, noted so it isn't copied
  elsewhere as if `accountId` were genuinely optional.
- `forge-security-auditor`: not run — RISK=false, no auth/session write path
  touched, reused an already-audited session value.

## Risks flagged

None.

## Left for later

Same two items as the prior note (unreferenced day-meter translation keys,
`ChargeCycle`'s now-unused public API) — untouched by this follow-up.

## User must do

Nothing — no migration, no env var, no config change.
