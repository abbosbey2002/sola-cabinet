# Home page active-device count matches /devices instead of billing's stale counter

- **Date:** 2026-08-28 12:39
- **Scope:**
  - `app/Http/Controllers/CabinetController.php` — modified
  - `resources/views/cabinet/index.blade.php` — modified (2 lines)
  - `app/Support/AbonentProfile.php` — modified (dead code removed)
  - `tests/Feature/CabinetTest.php` — modified (new test)

## Decisions

User reported the Home page's devices card showing "0/1 active" and asked why.
Root cause: `AbonentProfile::activeDeviceCount()`/`deviceCount()` read
`device_active_count`/`device_count` straight from `/abonent/info` — a counter
billing maintains independently of a device's actual IP lease (the field the
`/devices` page reads via `/device/list`'s `ip`). A real captured response
(`docs/api/SOLA_API.md:79-93`) shows exactly `device_count=1,
device_active_count=0` — this is documented production behavior, not
hypothetical.

Checked whether the component was even in scope before touching it: TZ
(`docs/task/tz_v1.docx` §3.1, §4) requires an active-device count on the home
page, and `docs/task/QA_CHECKLIST.md` §C.6 explicitly requires it to **match**
`/devices` — a check that was apparently never signed off. User confirmed:
compute it the same way `/devices` does, accepting the extra API round trip
this requires (`CabinetController` previously deliberately avoided calling
`/device/list` on this page — that trade-off is now reversed per this
decision).

Implementation: `CabinetController::index()` now also calls
`$this->sola->devices($accountId)` (identical call to `DeviceController`) and
counts active/total the same way `devices.blade.php` does — active iff the
permit's `ip` is filled — passing `activeDevices`/`totalDevices` to the view.
`AbonentProfile::deviceCount()`/`activeDeviceCount()` were removed as dead code
(grepped repo-wide first; no other callers). Did not touch
`FakeSolaServer`'s `device_count`/`device_active_count` fields on
`/abonent/info` — those describe billing's real response shape and are correct
fixture data even though this page no longer reads them.

## Pipeline results

- `forge-code-reviewer`: **APPROVE**. Two non-blocking nitpicks: `filled()`
  vs. `devices.blade.php`'s plain truthy check on `ip` aren't byte-identical
  predicates (no practical difference — `ip` is always a dotted-quad string,
  never `"0"`); and the `/device/list` count has no defensive type-check
  against a malformed response, consistent with `devices.blade.php`'s
  existing lack of guards there (not a new regression).
- `forge-security-auditor`: not run — RISK=false, read-only display fix, no
  auth/money/write path, same authenticated account-scoped call
  `DeviceController` already makes.

## Risks flagged

None new. The extra `/device/list` round trip on every Home page load was a
deliberate trade-off `CabinetController` previously avoided (see the removed
comment at the old lines 53-55) — now accepted per the QA_CHECKLIST §C.6
consistency requirement and the user's explicit confirmation.

## Left for later

None.

## User must do

Nothing beyond deploying the changed files — no migration, no env var, no
config change.
