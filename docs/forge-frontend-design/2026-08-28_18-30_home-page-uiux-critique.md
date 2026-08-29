# Home page ("Главная") — UI/UX critique

- **Date:** 2026-08-28 18:30
- **Mode:** SaaS (kabinet dashboard) + Multi-market (uz/ru/en)
- **Deliverable:** written critique only, no code changes — user asked to
  hear the shortcomings ("kamchiliklarini... boricha ayamasdan"), not for a
  redesign.
- **Method:** live-inspected the real production container (`cabinet_php`/
  `cabinet_nginx`, port 8080) as the real test account 1336708 ("TEST
  PAYMENTS") in both themes, plus two `SOLA_FAKE=true` fixture accounts
  (1002 negative-balance-with-contract, and an empty/no-tariff account) to
  compare against. Cross-checked every finding against the live
  `resources/views/cabinet/index.blade.php` and
  `resources/views/components/pay-card.blade.php` source (both had changed
  under a parallel session earlier the same day — re-read before citing any
  line number).

## Findings reported

1. Duplicate tariff price ("Smart 50 - 125 000 сум" name + "125 000 сум"
   hint) — confirmed as billing's now-standard naming convention via a real
   `/tariff/connected` capture (Smart 50/75/100/300, all "Name - Price сум"),
   not a one-off. `index.blade.php:163-165`.
2. Two same-tint alert blocks stacked directly on top of each other
   (in-hero note banner + `<x-pay-card>`, both `--c-warn-soft`/
   `--c-danger-soft`) when there's no contract number — reads as a broken
   repeat. `index.blade.php:126-134` + `:145-146`, `pay-card.blade.php:21-58`.
3. The resulting pay-card, with its contract half hidden, is a lone button
   in an oversized tinted card with no icon/context.
4. "Login" row given equal visual weight/typography to Balance and Next
   charge, with no explanation of what it's for. `index.blade.php:105-118`.
5. No CTA/guidance for a genuinely empty (no tariff/balance/devices/
   payments) account — confirmed via a fixture account; the page is honest
   but offers no next step.
6. Billing's raw internal naming convention shown verbatim as primary
   customer-facing copy, duplicating the separately-shown price.

Also flagged what's working (recessed-panel hero row treatment, the "no
invented verdict" honesty on a null-cost account, the negative-balance
account 1002's fully-populated pay-card, which renders as one clean card).

## Follow-up: all 5 fixed (same day)

User asked to fix everything ("hammasini tuzatib bering"). Implemented:

1/6. **Duplicate price** — `AbonentProfile` gained
   `currentTariffDisplayName()`/`nextTariffDisplayName()`, which strip a
   trailing "- <price> <word>" suffix only when the price segment exactly
   matches the already-known `currentTariffCost()`/`nextTariffCost()` — a
   name that doesn't end in the known price is returned completely
   unchanged, never guessed at. The raw `currentTariff()`/`nextTariff()`
   accessors are untouched and still used for `tariff.blade.php`'s
   `$find()` matching against `/tariff/available`'s own raw names — only
   the two literal display sites (Home page tariff card,
   `tariff.blade.php`'s current+next headings) switched to the new
   methods.
2/3. **Empty pay-card / stacked alert boxes** — `pay-card.blade.php` gained
   an `@elseif (config('iwon.active'))` branch: no contract number now
   shows an icon + title + hint (new keys `app.topup.pay_card_title`/
   `pay_card_hint`) at the same visual weight as the contract-number half,
   so the card always reads as one deliberate piece instead of a lone
   button in an empty tinted box.
4. **Login row weight** — value text de-emphasized from
   `text-base font-semibold text-ink` to `text-sm font-medium text-muted`,
   reading as reference info rather than competing with the money figures.
5. **No CTA for a tariff-less account** — a new banner (reusing the
   existing `app.cabinet.no_tariff_hint` copy + a new "choose a tariff"
   button, `app.dash.choose_tariff`) shows when `$note === null` and there's
   no tariff at all, gated off for legal entities the same way
   `TariffController` already 403s them.

Manually verified `currentTariffDisplayName()` directly against the real
production account (1336708) via `php artisan tinker` inside the live
container — confirmed "Smart 50 - 125 000 сум" → "Smart 50".

`forge-code-reviewer`: **APPROVE**, one Warning fixed: the price-suffix
regex matched a literal ASCII space as the thousands separator, but
Cyrillic billing exports are also known to use a non-breaking space
(U+00A0) — a name using that separator would have silently failed to
strip, with the duplicate-price bug reappearing with no error signal.
Fixed: the number is now matched digit-group by digit-group with a
`[\s\x{00A0}]` separator class instead of one hardcoded byte; locked in
with a new test (`the_price_suffix_is_matched_even_with_a_non_breaking_space_separator`).

New tests: 7 in `tests/Unit/AbonentProfileTest.php` (price-suffix
stripping: exact match, NBSP separator, no-match-left-alone, a
coincidentally-similar-but-wrong trailing number left alone, null cases,
next-tariff variant), 4 in `tests/Feature/CabinetTest.php` (duplicate-price
on both Home and /tariffs using the real captured data, no-tariff CTA
shown/hidden-for-legal-entity), 2 assertions added to an existing test in
`tests/Feature/TopUpTest.php` (pay-card no-contract content). Full suite:
**179 passed** (was 168 before this follow-up), 597 assertions.

## Note

Mid-investigation, cross-port cookie sharing (browser cookies are scoped by
host only, not port — a `127.0.0.1:8899` fixture session leaked into
`127.0.0.1:8080`, the real container) briefly showed the real container
rendering a `SOLA_FAKE` fixture account. Caught it, logged the real session
out properly (`/auth/logout` on port 8080) before finishing, so the user's
next real login starts clean. Not a product bug — an artifact of testing
two same-host servers in the same browser profile; noted here so it isn't
mistaken for something wrong with the app itself.
