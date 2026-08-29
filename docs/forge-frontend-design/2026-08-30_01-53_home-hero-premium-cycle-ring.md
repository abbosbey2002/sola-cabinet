# Home hero: premium redesign with a billing-cycle ring

- **Date:** 2026-08-30 01:53
- **Mode:** SaaS (kabinet dashboard) — elevate within the existing accessibility-first token system, not a new one.
- **Screens/components delivered:**
  - `resources/views/cabinet/index.blade.php` — Home page hero card + the three info-cards below it
  - `resources/css/app.css` — `.u-card-hero`, `--shadow-card-lg`
  - Figma `Cabinet desing` file (https://www.figma.com/design/UGKZLrFgWsGks08HgeJkNS) — `Dashboard - Desktop` (2:2) and `Dashboard — Mobile` (2:3) frames updated to match

## Brief

User asked (in Uzbek) to redesign the cabinet with a "premium" feel, researched against fintech/crypto apps, but explicitly kept scope to the Home/dashboard page only and asked for it verified live in a browser.

## Research summary (informed the direction, not copied wholesale)

Fintech/crypto dashboards converge on: answer "how much / what happened / is anything wrong" in under 3 seconds, balance dominating the hierarchy, tabular-numeral money, restrained motion, elevated hero cards. Crypto wallet UIs lean minimalist (fewer functions, icon+text). The dense, small-text, dark-first "crypto app" aesthetic was explicitly rejected — it conflicts with this project's documented accessibility requirements for elderly/varied-condition users (`design/HANDOFF.md`, `design/assets/tokens.js`: AAA-ish contrast, ≥1rem text, ≥48px touch targets, no uppercase micro-labels). The brief was to combine the two, not pick one.

## Decisions

1. **Cycle ring, not a generic gauge import.** The project already ships an on-brand 240°-arc component (`x-arc`, echoes the `)))` shape in the SOLA logo), previously only used for traffic-split stats. Reused it on Home as a "days left in the billing cycle" ring — `ChargeCycle` (a value object explicitly left with its full API "for a future dashboard element" per its own doc comment after a day-meter widget was removed 2026-08-28) already carried every number needed (`daysLeft`, `totalDays`, `isChargeDay()`, `isOverdue()`), and `lang/{en,ru,uz}/app.php` already had unused, fully-built plural strings (`dash.days_left`, `dash.days_left_unit`) clearly scaffolded for exactly this and never wired up. Completed that existing intent rather than inventing a new pattern.
2. **Balance promoted back to sole hero status.** The prior "boxed rows" pass (see `2026-08-28_15-54_hero-card-boxed-rows.md`) deliberately stepped the balance figure down from `text-4xl` to `text-3xl` because it was "no longer the lone hero element." Adding the ring restores that condition, so the balance reverts to `text-4xl`, sits in its own top zone with the ring beside it (`flex-col` → `sm:flex-row`), and next-charge/login demote to a secondary strip below a `border-line` divider.
3. **Ring colour = `$tone['fg']`.** The same amber/red that colours the balance figure and the alert strip also fills the ring, so balance trouble and time pressure read as one verdict, not two unrelated widgets. Falls back to `var(--c-action)` when `$state` is null (no tariff cost to judge against, but a charge date can still exist).
4. **`.u-card-hero`**: a corner gradient blended only from two already-contrast-vetted tokens (`--c-action-soft` → `--c-surface` — the same pair already proven under `--c-ink` text in `.u-pill-ok`), plus a deeper `--shadow-card-lg` (light/dark/explicit-dark, mirroring `--shadow-card`'s existing three-block pattern). No new colours to audit.
5. **Icon tiles on the three info-cards** (`tag`/`router`/`receipt`, matching the icons already used in the nav and in the Figma file) — reuses the exact tile pattern `pay-card.blade.php` and the Services entry card already established (`size-* rounded-xl` on `--c-action-soft`/`--c-action`), so the vocabulary is consistent across the whole page instead of only some cards having icons.
6. **Figma updated to match**, not left to drift: detached the `Balance Row` instance to restyle it freely, added a matching `Cycle Ring` frame (native Figma `arcData` ellipses, not a redrawn approximation), reflowed the auto-layout hero card (`layoutPositioning='AUTO'` + reordering, not manual x/y — the first attempt fought the frame's own `VERTICAL` auto-layout before this was corrected), and cascaded the resulting height delta through every sibling below it (Info Cards, Services Banner, Main, Footer, the outer Dashboard frame) for both the Desktop and Mobile frames. Applied on both since the ring only exists once the ring is added consistently.

## Quality floor result

- **Verified in a real Chrome browser** against the local Docker app (`SOLA_FAKE=true` toggled in `.env` for this session — gitignored, not a committed change; revert to `false` to test against the real VPN-backed billing API): two accounts (positive/"ok" state and negative/"danger" state), light and dark theme, English and Russian locale. Russian 3-form pluralization confirmed correct ("3 дня"). Balance/ring/alert-strip colour-matching confirmed for both states.
- **True mobile-viewport screenshot not obtainable this session** — `resize_window` did not affect the actual browser viewport in this sandboxed environment (confirmed via `window.innerWidth` staying at 1920 after multiple resize attempts, including a fresh tab). Verified the responsive Tailwind classes by code review instead, and caught a real bug this way: the ring wrapper's `mx-auto` would have had no effect once its flex-column parent stretched it to full width (fixed to `flex justify-center`, which centers correctly in both stacked and row layouts). Mobile responsiveness should still get a real-device/DevTools check before shipping.
- `php artisan test --filter=Cabinet`: 62 passed, 1 failed (`a_tariff_never_enabled_by_the_admin_is_refused...`) — confirmed via `git stash` that this same test fails identically on HEAD before any of this session's changes; unrelated (admin tariff-whitelist seeding), not a regression.
- `prefers-reduced-motion`: unaffected, no new animation added (`u-rise`/`u-draw` reused as-is).
- No new brand logos, no new fonts, no CDN dependency, no new hex colours outside the two new shadow tokens (which are pure shadow recipes, not text/background colours).
- `forge-code-reviewer`: **APPROVE**, no blockers. One warning fixed: `ChargeCycle`'s docblock said the day-meter reading `daysLeft()`/`totalDays()`/`isOverdue()`/`isChargeDay()` "was removed 2026-08-28" — now stale since this diff brings that exact usage back via the cycle ring; updated to say so. Two nitpicks also fixed: dropped a dead-code `totalDays > 0` guard (`ChargeCycle::endingAt()` always clamps it to ≥1) and corrected the `.u-card-hero` CSS comment, which cited the wrong text/background pairing (`--c-ink`, not `--c-action`, is what actually sits on the gradient) even though the rendered contrast itself was already fine.

## Left for later

- Mobile info-cards in Figma (tariff/devices/last-payment) don't have icon tiles the way the Desktop ones already did before this session — out of scope for this pass (hero card was the ask), noted for whoever next touches that frame.
- A real mobile-viewport/device browser check (this session's sandbox could not resize the browser window).
- Scope was explicitly Home page only — Tariff/Devices/Statistics/Payments/Services pages were not touched, including their own Figma frames, even though the same premium direction could extend there later.

## User must do

- Nothing required. `SOLA_FAKE=true` in local `.env` is a gitignored, local-only convenience left on from this session's testing — flip back to `false` when real VPN-backed billing data is needed.
