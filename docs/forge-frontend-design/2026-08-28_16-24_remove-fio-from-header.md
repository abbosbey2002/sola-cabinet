# Remove Ф.И.О. from the desktop header

- **Date:** 2026-08-28 16:24
- **Mode:** SaaS (kabinet dashboard) — decluttering an existing row, no new tokens/markup shape.
- **Screens/components delivered:**
  - `resources/views/partials/topbar.blade.php` — desktop header id-facts row

## Decisions

User ask: "headerdan FISHni olib tashlang kerak emas" — remove Ф.И.О. from
the header, not needed.

Scoped to the **desktop header's inline id-facts row** (`u-id-facts`,
`xl:flex`, always visible without opening anything) — dropped the
`app.cabinet.fio` entry, kept `app.dash.contract`. Left the **mobile
drawer's** fact list (same file, further down) untouched on purpose:
`x-account-menu`'s `as="list"` variant has its own comment stating it
deliberately omits a name header because "it sits directly under the `dl`
that already states the name" — that `dl` is the mobile drawer's fact
list. Removing Ф.И.О. there too would have silently left mobile
subscribers with no visible name anywhere in the UI, which is a bigger
change than "remove it from the header" asked for. The subscriber's name
is still visible elsewhere on desktop regardless — the account-switcher
chip on the right of the same header row, and its dropdown panel, both
already show it independently of this row.

No component/token change — this is a one-line removal from the existing
`@foreach` fact array in `topbar.blade.php`, no new markup shape
introduced.

## Quality floor result

- Verified in a real Chrome browser (temporary static HTML harness under
  `public/`, deleted after use): header row renders correctly with only
  "Договор" in the facts slot, no leftover gap or misalignment: the row's
  `gap-x-7` and `ml-5` are unaffected by array length.
- `php artisan test` — 145 passed (514 assertions); no test referenced the
  `app.cabinet.fio` label in this row.

## Pipeline results

- `forge-code-reviewer`: not run — RISK=false, single-line removal from an
  existing array literal, verified visually and by the full green suite.
- `forge-security-auditor`: not run — RISK=false, pure display change,
  removes a field from view rather than exposing anything new.

## Left for later

Mobile drawer's Ф.И.О. row was intentionally left in place — see Decisions
above. If the user wants it gone there too, that also requires either
adding a name display to `x-account-menu`'s `as="list"` variant first (so
mobile subscribers don't lose the name entirely) or an explicit
confirmation that dropping it everywhere is fine.

## User must do

Nothing — no migration, no env var, no config change, no asset rebuild
required beyond the normal Vite build already in the deploy pipeline.
