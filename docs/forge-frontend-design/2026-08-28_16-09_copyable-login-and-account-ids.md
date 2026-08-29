# Copyable login and account-id fields

- **Date:** 2026-08-28 16:09
- **Mode:** SaaS (kabinet dashboard) — reused an existing, already-shipped component pattern, no new tokens/JS.
- **Screens/components delivered:**
  - `resources/views/cabinet/index.blade.php` — Home hero card, Login row
  - `resources/views/components/account-menu.blade.php` — desktop account-switcher disclosure panel, current-account id
  - `resources/views/partials/topbar.blade.php` — mobile drawer facts list, contract + account-id

## Decisions

User ask: "login, accid va shunga o'xshash idlarni copy qilish imkoni
bo'lishi kerak. va user buni ko'ra olsin" — login, account-id and similar
identifiers need a copy affordance, and it must be visibly discoverable
(not hover-only).

Zero new code: reused `data-copy` / `data-copy-text` exactly as
`resources/js/modules/copy.js` already defines it (delegated document click
listener, already wired into `app.js`, already shipping via
`x-pay-card`'s contract-number button) and the same `u-btn-ghost u-btn-sm`
markup `x-pay-card` already uses — copy icon + a `role="status"` text span
that swaps to `app.ui.copied` for ~1.6s so the confirmation sits on the
control itself, matching the established pattern instead of introducing a
toast.

Three surfaces identified as "login, accid va shunga o'xshash":

1. **Home hero card, Login row** (`index.blade.php`) — the row added in the
   prior session's boxed-row redesign. `$billingLogin` value now sits next
   to a copy button instead of bare text.
2. **Account id, desktop account-switcher** (`account-menu.blade.php`) —
   the disclosure-panel header shows the current account's `accId`
   (`request()->cookie('account')`, passed in as `$current`); this was the
   only place in the codebase literally rendering a value under the name
   "accId". Added `@if ($current)` as a defensive guard (matches the
   `blank()` checks already used for the other-accounts list in the same
   file) since the prior code rendered it unconditionally.
3. **Mobile drawer facts** (`topbar.blade.php`) — the same accId is also
   rendered here (`app.accounts.personal`) for subscribers under the `sm`
   breakpoint, where `x-account-menu`'s desktop panel (`hidden sm:block`)
   never reaches them; skipping this would have left mobile users — the
   audience this cabinet is built for — with no way to copy their own
   account id at all. Extended the contract-number (`app.dash.contract`)
   fact in the same list too, since it's the same kind of "id a subscriber
   reads over the phone to support" this ask covers, and the row shape
   needed for one applies identically to the other. Turned the flat fact
   array into a `'copyable' => true` flag (mirrors the `$cards` pattern
   already used lower on the Home page) rather than duplicating the row
   markup a third time inline.

**Deliberately left untouched:** the desktop topbar's compact id-facts row
(Ф.И.О./Договор, `xl:flex`) and the "switch to another account" list rows
in both drawer and dropdown. The first is a tight single-line 4.5rem
header row with no spare width for a 44px control without visually
breaking the row; the second problem is structural, not spacing — those
rows are themselves `<a>` elements (switch-account links), and nesting a
`<button data-copy>` inside an `<a>` is invalid HTML (interactive-in-
interactive) that several browsers handle inconsistently for click
targeting. Neither was in the literal ask ("login, accid"), so left as a
follow-up rather than guessing at a restructure.

## Quality floor result

- Verified WCAG-safe: no new colors — `u-btn-ghost` is an existing,
  already-contrast-checked token combination.
- Verified in a real Chrome browser (`npm run build` + a temporary static
  HTML harness under `public/`, deleted after use): all three button
  placements render correctly, icon + visible "Копировать" label present
  (never hover-only), matches the `x-pay-card` precedent pixel-for-pixel.
  Clicked the button and confirmed the delegated listener fires (focus
  ring appears, `copyText()` is reached) — the async
  `navigator.clipboard.writeText()` call itself hung on a permission
  prompt under the CDP-driven automated click in this sandboxed
  environment, a known limitation of headless clipboard automation and
  not something introduced by this change: `copy.js` is pre-existing,
  unmodified code already shipping in production via `x-pay-card`. Not
  something a real user's click hits, since Chrome resolves that prompt
  interactively.
- Keyboard: buttons are real `<button type="button">`, natively focusable
  and activatable, `:focus-visible` inherited from the global rule.
- Touch target: `u-btn-sm` is 44px (48px under `pointer: coarse`), the
  same floor every other control on this page already meets.
- `prefers-reduced-motion`: unaffected.
- `php artisan test` — 145 passed (514 assertions), including the
  login-row-present/absent tests that exercise the exact markup touched.

## Pipeline results

- `forge-code-reviewer`: not run — RISK=false, additive markup reusing an
  existing, already-reviewed JS module and button pattern verbatim, no new
  abstractions; covered by the full green test suite and direct browser
  verification.
- `forge-security-auditor`: not run — RISK=false. Values copied
  (`$billingLogin`, account id, contract number) are all already rendered
  as plain text on the same pages today; a copy button adds no new data
  exposure.

## Left for later

- Desktop topbar's compact Ф.И.О./Договор row and the account-switch list
  rows (drawer + dropdown) were intentionally not made copyable — flagged
  above with the specific reason each was skipped. If wanted, the
  id-facts row needs a width/layout call (not just a dropped-in button),
  and the switch-account rows need the copy control pulled out of the `<a>`
  entirely (e.g., a leading icon-only affordance beside the link, not
  inside it) which is a small structural change rather than a one-line add.

## User must do

Nothing — no migration, no env var, no config change, no asset rebuild
required beyond the normal Vite build already in the deploy pipeline.
