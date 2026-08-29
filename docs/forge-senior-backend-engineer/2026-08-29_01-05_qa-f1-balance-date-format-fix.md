# QA finding F-1 — balance note date format inconsistency

- **Date:** 2026-08-29 01:05
- **Scope:**
  - `resources/views/cabinet/index.blade.php` — 2-line fix + comment
  - `tests/Feature/CabinetTest.php` — 2 existing assertions updated to match

## Decisions

Source: `docs/forge-qa-browser/2026-08-29_00-58_home-page-quality-check.md`, finding F-1.

The home page's balance-state sentence (`app.dash.balance_ok` / `app.dash.balance_low`)
rendered the next-charge date as `d.m` ("01.09") while the "Следующее списание"
row two lines above it in the same hero card rendered the identical date as
full `d.m.Y` ("01.09.2026") — same date, two formats, in one card.

Fix: both `$cycle?->end->format('d.m')` calls (in the `'ok'` and `'low'`
arms of the `$note = match ($state) { ... }` block) changed to
`format('d.m.Y')`. No wording change needed in `app.dash.balance_ok` /
`balance_low` — read all three locales' phrasings (ru/uz/en) before the fix
and confirmed none of them assumed a short date; the code reviewer
independently re-verified the same three strings.

Grepped the repo for other `format('d.m')` call sites to check nothing else
needed the same fix: one other hit, `resources/views/payment/result.blade.php`
(`app.payment.total_between`, a date *range* for payment history) — different
context, doesn't restate a date shown elsewhere on that page, correctly out
of scope.

Updated the two existing tests that pinned the old short-date string
(`the_next_charge_amount_prefers_the_queued_tariffs_price_over_the_current_ones`,
`a_next_tariff_cost_with_no_queued_tariff_name_is_ignored`) — both fixtures
use `charge_date => '2026-08-15'`, assertions now expect `'15.08.2026'`.

No new test added for the `'low'` branch specifically: that branch has no
dedicated test at all (pre-existing gap, unrelated to this fix — noted below,
not fixed here to keep this change to the reported finding).

## Quality floor result

- Verified live in a real browser (SOLA_FAKE fixture account 1001, "ok"
  balance state): sentence now reads "Your balance covers it — the 25 000
  sum charge on **01.09.2026** is funded.", matching the "Next charge" row's
  "01.09.2026" above it.
- `php -l` clean on both changed files.
- `php artisan test` — 178 passed, 1 pre-existing failure unrelated to this
  change (see below).

## Pipeline results

- `forge-debugger`-style verify: done inline above (live browser + full
  suite), no separate pass needed for a change this size.
- `forge-code-reviewer`: **APPROVE**, no blockers/warnings/nitpicks.
- `forge-security-auditor`: not run — RISK=false, pure date-format display
  fix, no money/auth/data-logic change.

## Risks flagged

None.

## Left for later

- **Pre-existing, unrelated test failure**: `a_tariff_never_enabled_by_the_admin_is_refused_even_though_billing_offers_it`
  expects 403, gets 302. Confirmed via `git stash` that it fails identically
  on the tree without this fix — an admin tariff-whitelist/redirect issue,
  not touched by this change. Flagging for whoever owns that area; did not
  investigate further (out of scope for F-1).
- **No dedicated test for the `'low'` balance state** — the branch itself
  (not just its date format) has zero test coverage. Pre-existing gap,
  surfaced while fixing F-1, not fixed here.

## User must do

Nothing beyond the normal deploy of this and the other already-uncommitted
home page changes — no migration, no env var, no config change. `SOLA_FAKE`
was temporarily flipped `true` twice during this session (once for the
original QA pass, once to visually re-verify this fix) and reverted to
`false` both times before ending.
