---
name: frontend-design
description: >-
  Designs and implements distinctive, production UI for the SOLA subscriber
  cabinet (Laravel Blade, Tailwind 4, vanilla JS). Use whenever the user asks
  to design, redesign, restyle, animate, add loading/wait states, polish UX,
  or make anything user-facing look better — including Uzbek/Russian phrasing
  such as dizayn, chiroyli, animatsiya, loading, spinner, skeleton, sahifa,
  kabinet, modal, tugma. Covers tokens, motion, wait-state scenarios, empty
  and error screens, and trilingual copy. Do not use for backend-only Blade
  variable/route changes, FormRequest validation, or JS runtime bugs with no
  visual change.
---

# Cabinet frontend design

This app is a **SaaS billing cabinet** (lk.sola.uz) + **multi-market** (uz/ru/en) + **mobile-first consumer** physics (pensioners on mid-range Android, metro dead zones). Distinctiveness lives in the **system** (logo-green tokens, live-signal motion, wait states), not in one-off page heroics.

Do not invent a new palette, type scale, or component family. Extend `resources/css/app.css` tokens and existing `u-*` classes.

## Before any pixels

1. Name the **subject**, **audience**, and the screen's **one job**. Default: a subscriber checking whether their internet stays on.
2. State the mode mix out loud: SaaS + multi-market (+ consumer wait physics).
3. Sketch a compact plan: color (existing tokens), type (Inter body / Manrope figures), layout one-liner, **one signature**.
4. Anti-default: if the plan looks like cream+serif, black+acid-green, or newspaper hairlines, revise it. This product's signature is the **logo-green live signal**, not a template look.

Then read only what you need:

- Tokens and `u-*` vocabulary → [references/tokens.md](references/tokens.md)
- Loading, offline, timeout, OTP, 503 → [references/wait-states.md](references/wait-states.md)

## Stack (do not fight it)

- Blade + Tailwind 4 + small modules in `resources/js/modules/`, boot from `app.js`. Delegation; no-op if markup is missing. No React, no Livewire, no Alpine unless already there.
- Layouts: `layouts/app.blade.php` (cabinet), `layouts/guest.blade.php` (auth). Reuse `x-*` and `partials/topbar`. Traffic views stay under **`trafic/`**.
- Copy: `lang/{uz,ru,en}/app.php` **together**. Sentence case, no ALL-CAPS Cyrillic. A control names the outcome (`Balansni to'ldirish`, not `Yuborish`).
- Vite on the **host** (`npm run build`). Docker has no Node. `public/build` is gitignored.
- Hide promo / loyalty / chat / top-up until the matching `config()` flag is on. Legal entities: hide tariff nav (controller 403 is the real gate).

## Motion

One orchestrated moment per screen beats scattered easing.

- Animate only `transform` and `opacity`.
- Micro 120–200ms, entrances 300–500ms. Over 700ms needs a reason you can state.
- Honor `prefers-reduced-motion: reduce` (the global rule in `app.css` already zeroes durations — do not fight it with new infinite loops).
- Signature on Home: balance / days-left `data-count-up` + cycle `u-draw`. Signature in-flight: the 3px `--c-signal` progress strip (page nav and AJAX region).
- Button `.is-loading` must hide **text nodes**, not only `> *`. Set `color: transparent` on the button; the spinner uses explicit border colors, not `currentColor`. Icons still get `visibility: hidden`.
- Ambient motion (orbs, icon pulse) only on **waiting** screens (auth SMS). A finished dashboard does not loop.

## Wait states (only when work is actually in flight)

Loaders on server-rendered content are a bug. Allowed waits:

| Wait | UI |
|---|---|
| Full-page navigation / POST | `.u-progress` + submit `.is-loading` |
| Period filter AJAX | dim region + 3px bar; **12s abort → in-region retry**, keep old table |
| Browser offline | `data-offline-banner` bottom strip; no spinner |
| SMS code | `data-otp` paste + auto-submit at 4 digits |
| Billing down | `errors/503.blade.php` + refresh CTA |

Do **not**: SMS resend (identify is not retried — SMS cost), infinite spinners, skeleton rows on Blade HTML, toast-only timeout with no retry control.

## Quality floor (fix before showing the user; do not announce it)

- Network-dead after first paint: readable, no broken icon boxes, no eternal spinner
- WCAG AA on every text/background pair (tokens already are; new colors must trace to `--c-*`)
- Keyboard: `:focus-visible`, 48px hit areas (44px floor)
- 320px → 1440px; Russian strings are the width budget
- Real payment logos in a white chip, never recolored, never hand-drawn
- `npm run build` after CSS/JS changes so Docker/nginx serves the new hash

Chanel: remove one accessory before you stop.

<!--  -->
## Session note

Write `docs/forge-frontend-design/<YYYY-MM-DD_HH-MM>_<task>.md` from `date +%Y-%m-%d_%H-%M` before ending the turn:

```markdown
# <Task>
- **Date:**
- **Mode:** SaaS + Multi-market
- **Screens/components delivered**
- **Decisions:** why this signature, what you refused
- **Quality floor result**
- **Left for later**
```
