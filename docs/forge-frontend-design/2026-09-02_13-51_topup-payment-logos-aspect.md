# Top-up payment logos: stop squashing

- **Date:** 2026-09-02 13:51
- **Mode:** SaaS + Multi-market (uz/ru/en cabinet) + mobile-first consumer
- **Screens/components delivered:** top-up methods strip in `partials/topup-form.blade.php` (modal + `/topup` page share it)
- **Decisions:**
  - Subject: subscriber topping up; the four marks are “accepted methods”, iWon is the actual checkout.
  - Viewport `sm:grid-cols-4` packed four named chips into a 28rem modal (~90px each). Switched to a 2×2 grid always; names sit under the mark so they never ellipsize.
  - White chips, `object-contain`, `width/height: auto` with `max-height: 1.25rem`. Dropped the 32×32 well, Click `object-cover`, and Uzcard/Humo `max-w-[1.875rem]` — those three were stretching the PNGs.
  - Chip text is `#141d09` (not `--c-ink`) because the chip stays white in dark theme.
  - Refused a 4-across logo-only strip: pensioners still get the word under each mark; the wordmarks in the PNGs are not a substitute.
- **Quality floor result:**
  - Chrome headless at 320px: rendered vs natural aspect — Payme 3.15/3.15, Click 3.05/3.06, Uzcard 1.60/1.59, Humo 1.65/1.64. Names fully visible.
  - Same CSS at modal 28rem. Compiled bundle has no `sm:grid-cols-4` on this grid.
  - Did not log into the live cabinet (`SOLA_FAKE=false`); verified the shipped CSS + real `public/img/logos/*` files, not the authenticated modal click-path.
  - WCAG: names on white chips stay dark. Keyboard: chips are informational `<div>`s, unchanged. `prefers-reduced-motion`: no new motion. Real brand PNGs, not recolored.
- **Left for later:** `.u-topup-methods` ground is still hardcoded `#fafbf8` in dark theme (pre-existing).
