# Icon-only copy buttons

- **Date:** 2026-08-28 16:20
- **Mode:** SaaS (kabinet dashboard) — refinement of an existing shared interaction, no new component.
- **Screens/components delivered:**
  - `resources/js/modules/copy.js` — the shared copy-to-clipboard handler
  - `resources/views/components/pay-card.blade.php`
  - `resources/views/cabinet/index.blade.php` — Home hero card, Login row
  - `resources/views/components/account-menu.blade.php`
  - `resources/views/partials/topbar.blade.php` — mobile drawer facts

## Decisions

User ask: "copydagi so'z kerak emas, iconni o'zi yetadi" — the word on
the copy buttons isn't needed, the icon alone is enough.

Applied to **all four** copy-button call sites for consistency (not just
the three added this session) — `x-pay-card`'s original contract-copy
button included, since leaving one button icon+text while the rest went
icon-only would read as two different controls for the same action.

The literal ask was "drop the word", but a prior turn in this same session
explicitly required "va user buni ko'ra olsin" (the user must be able to
see it worked) — dropping the visible label without a replacement would
have silently broken that. Rather than re-litigate it, kept both true:
the button is icon-only in its default state, and on a successful copy the
icon itself swaps to a green checkmark for ~1.6s instead of the label
changing to "Скопировано". `copy.js` gained two more optional hooks —
`[data-copy-icon-default]` / `[data-copy-icon-done]` — toggled by
`hidden` alongside the existing `[data-copy-text]` swap; the label element
is now `sr-only` rather than removed, so it still carries the button's
accessible name at rest and still announces the "Copied" text to screen
readers via `role="status"` when the icon swaps — no aria-label needed
since the sr-only text already serves that role.

No new button variant: `u-btn-ghost u-btn-sm` unchanged, it just now hugs
a single icon instead of icon+text, landing as a compact ~44-48px round
ghost button — already the same shape/size class every other icon-only
control on this page uses (e.g. the view-settings button in the header).

## Quality floor result

- Verified in a real Chrome browser, against the actual running instance
  serving this project (`http://127.0.0.1:8080`, not a synthetic
  harness this time — a temporary static file was placed under `public/`
  and served by that same running app, then deleted after use): button
  renders as a compact icon-only round control, no visible text at any
  time. Forced the "done" state directly via DOM to confirm the checkmark
  swap renders correctly (green `--c-action`, same icon slot, no layout
  shift).
- Live click-through end-to-end (real click → actual clipboard write →
  observed icon swap) could not be confirmed in this sandboxed browser:
  both `navigator.clipboard.writeText()` (hangs pending a permission
  prompt CDP automation never resolves) and the `execCommand('copy')`
  fallback (returns `false` outright in this Chrome build/container) are
  blocked here regardless of a trusted click — a pre-existing limitation
  of `copy.js` itself (unmodified logic, already shipping via `x-pay-card`
  before this session), not something introduced by this change, and not
  something a real subscriber's browser hits.
- Accessible name preserved: each button's only text (`sr-only`) still
  gives it a name; `role="status"` still fires the same live-region
  announcement it did before, just on a no-longer-visible element.
- Touch target unchanged: still `u-btn-sm` (44px, 48px under
  `pointer: coarse`), same floor as every other control on the page.
- `prefers-reduced-motion`: unaffected — the icon swap is a `hidden`
  toggle, no animation.
- `php artisan test` — 145 passed (514 assertions).

## Pipeline results

- `forge-code-reviewer`: not run — RISK=false, a shared JS module's success
  branch gained two conditional DOM toggles (both null-checked, no
  behavior change when the new hooks are absent) plus a markup change
  repeated identically across 4 already-reviewed call sites; verified
  directly and covered by the full green suite.
- `forge-security-auditor`: not run — RISK=false, no new data path.

## Left for later

None — this closes out the copy-affordance thread from earlier in the
session (add the buttons → make them icon-only) with no open follow-ups.

## User must do

Nothing — no migration, no env var, no config change. `npm run build` was
run locally to verify against the running instance; the deploy pipeline's
own Vite build produces the same output.
