# Home hero card premium refinement + account-switcher login fallback

- **Date:** 2026-08-28 14:51
- **Mode:** SaaS (kabinet dashboard) — elevate within the existing token system, not a new one.
- **Screens/components delivered:**
  - `resources/views/cabinet/index.blade.php` — the Home page hero/balance card
  - `resources/views/components/account-menu.blade.php` — the desktop account-switcher's `data-disclosure-panel` per-account list

## Decisions

User feedback in Uzbek, unpacked into two independent changes:

1. **"designni yaxshilang va premium muhit bering. Personal account kerak emas."**
   — improve the hero card's design for a premium feel; drop the account-id
   ("Personal account") row a previous turn had added there (reverted:
   `CabinetController::index()` no longer passes `accountId` to this view,
   and the login block is single-column again). Read `app.css` in full
   before touching anything — this is NOT a from-scratch redesign, the
   brief is to elevate one card within the system already documented there
   (one hue off the logo green, Inter/Manrope self-hosted, `u-card`/`u-label`/
   `u-figure` vocabulary, mobile-first clamp() type scale, AAA secondary
   text for a pensioner audience). Per the SaaS mode reference
   (`forge-frontend-design/references/mode-saas.md`): distinctiveness lives
   in the system, not a one-off hero — so no new colors, no gradients, no
   new icon set, and explicitly no reintroduction of the day-meter/ring
   visualization removed earlier the same day at the user's own request
   (`2026-08-28_14-28_home-card-remove-calendar-add-login.md`).

   Changes made, all composed from existing tokens:
   - `p-6 sm:p-8` on the hero `<section>` (vs. `u-card`'s own `p-5 sm:p-6`)
     — more breathing room for the one card the whole visit is about.
   - `border-t border-line pt-5` before the login block — a rule, not just
     a gap, so it reads as an account-metadata sub-section rather than a
     cramped afterthought (this was the user's earlier specific complaint,
     "u uxda kichkina bo'lib qolibdi").
   - The balance-state note banner's bare icon became a `bg-surface`
     rounded-lg tile — the exact "chip on a tinted ground" pattern
     `x-pay-card` already uses elsewhere on this page, not a new pattern.
     Wrapper changed `<p>` → `<div>` since it now nests a block-level
     `<span>` chip.

2. **"agar data-disclosure-panel da name bo'lmasa login chiqsin"** — in the
   account-switcher dropdown specifically (the `data-disclosure-panel`
   element — the mobile-drawer `as='list'` variant was deliberately left
   untouched, per the user naming that exact attribute), when billing's
   `abonName` is blank (documented in `docs/api/SOLA_API.md` as frequently
   blank in real traffic) the fallback used to jump straight to a generic
   account-type badge, which can't tell two blank-named accounts apart.
   `login` (same `accs[]` row, reliably populated per the same doc) now
   stands in first: `abonName ?: login ?: account-type-badge`.

## Quality floor result

- Verified WCAG-safe: every color is a token already vetted in `app.css`'s
  own comments (contrast ratios documented there); no new hex values
  introduced anywhere in this diff.
- Verified in a real Chrome browser: built the actual compiled Tailwind
  CSS (`npm run build`) and rendered the exact hero-card markup/tokens in a
  temporary static HTML harness (deleted after use, not part of the repo),
  checked all three balance states (ok/low/negative) × light/dark theme ×
  a ~328px mobile width. Confirmed: clear hierarchy, legible icon tiles in
  both themes, correct flex-wrap on mobile, divider+login block correctly
  absent when `billingLogin` is empty.
- `prefers-reduced-motion`: unaffected — no new animation.
- Responsive: reused the existing `flex-wrap`/`sm:` pattern already proven
  on this card; the only new utility is padding.
- No new brand logos, no new fonts, no CDN dependency.
- `php artisan view:cache` compiles clean; `php artisan test` — 144 passed.

## Pipeline results

- `forge-code-reviewer`: **APPROVE**. One nitpick fixed (a comment
  overclaimed which other components share the bg-surface tile pattern —
  narrowed to cite `x-pay-card` only, the actual match). One nitpick left
  as-is (icon vertical-centering on a hypothetical 2-line wrapped note —
  verified fine at the checked widths, not worth pre-empting).
- `forge-security-auditor`: not run — RISK=false, pure display/markup,
  `login` fallback reads a field already present in data this component
  already receives, no new data source or write path.

## Left for later

Same two items already logged in the prior same-day note (unreferenced
day-meter translation keys, `ChargeCycle`'s now-partially-unused public
API) — untouched by this pass.

## User must do

Nothing — no migration, no env var, no config change, no asset rebuild
required (the `npm run build` used for the visual check was a local,
already-cleaned-up verification step, not a deploy requirement — Vite
compiles this the same way it already does in the normal build pipeline).
