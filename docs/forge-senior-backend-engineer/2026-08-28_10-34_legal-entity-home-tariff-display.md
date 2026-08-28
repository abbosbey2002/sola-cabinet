# Legal-entity Home page tariff card — display-only

- **Date:** 2026-08-28 10:34
- **Scope:**
  - `resources/views/cabinet/index.blade.php` — modified
  - `tests/Feature/CabinetTest.php` — modified

## Decisions

The 2026-08-18 decision to hide the tariff section from legal entities
(`docs/forge-senior-backend-engineer/2026-08-18_15-24_legal-entity-tariff-gate.md`)
stays in force for the switch/connect flow: `TariffController::index()`/`connect()`
still `abort_if(isLegalEntity(), 403)`, and `partials/topbar.blade.php` still drops
the nav link. Neither was touched.

New, narrower request confirmed with the user: legal entities should see the
current tariff (name + cost) on the Home page for information, just not be able
to act on it. Confirmed with the user that billing (`/abonent/info`) is trusted
to return meaningful tariff fields for legal-entity accounts too — same fields
(`curr_tariff_name`, tariff cost candidates) already used for individuals, no new
billing integration needed.

Implementation: the Home page's `$cards` array gained a `'clickable'` flag
(`false` for the tariff card when `$profile->isLegalEntity()`, otherwise absent/
true). The `@foreach` that renders the three-card grid now emits a plain `<div>`
(no `href`, no chevron icon, no hover/translate transition, `cursor-default`
added) instead of `<a>` when a card isn't clickable — devices and last-payment
cards are unaffected. Chose a per-card flag over a second `@foreach` branch or a
separate partial: the three cards already share one loop and one visual grid: an
uncommon Blade idiom (dynamic tag `<{{ $clickable ? 'a' : 'div' }}>`) instead
adds one flag with the least code and keeps future cards free to opt out the
same way.

## Pipeline results

- `forge-code-reviewer`: **APPROVE**. Three nitpicks, no blockers/warnings:
  the dynamic-tag Blade idiom (uncommon here but compiles/renders correctly,
  confirmed by `view:cache`), no `cursor-default` on the non-clickable card
  (fixed after review), and confirmed `route('tariff')` is only evaluated
  when clickable (no risk of an unnecessary call).
- `forge-security-auditor`: not run — RISK=false, no money/auth/write path
  touched, purely a read-only display change of data already fetched.

## Risks flagged

None beyond what's already documented in the 2026-08-18 gate note (exact-string
match on `"Физическое лицо"`, `legal` field absent fails open) — unchanged by
this task, since `isLegalEntity()` itself was not modified.

## Left for later

None — task scoped to the Home page card only, as requested.

## User must do

Nothing beyond deploying the changed view/test files — no migration, no env var,
no config change.
