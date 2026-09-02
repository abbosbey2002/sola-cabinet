# Top-up: compact iWon row

- **Date:** 2026-09-02 14:22
- **Mode:** SaaS + Multi-market
- **Screens/components delivered:** `partials/topup-form.blade.php`, `.u-topup-iwon*`, `lang/{uz,ru,en}` topup copy
- **Decisions:**
  - Dropped the purple inner card, Click/Payme PNGs, and the card glyph. One row: official `iwon-logo.svg` at 24px + “iWon orqali to‘lash” + grey “Uzcard, Humo va xalqaro kartalar…”. Tiny white pad on the mark so it stays readable in dark theme (brand chip, not a second card).
  - Attention stays on amount → submit. Footnote is one line: details are not stored.
  - Presets are numbers only (soʻm stays on the field). 2×2 under 22rem, 4-up when the form is wide enough; 10px + nowrap so `1 000 000` does not wrap in the modal.
- **Quality floor result:**
  - Copy in uz/ru/en. `php artisan test --filter=TopUp` 13 passed (before the container-query CSS fix). Vite rebuilt after restoring `.u-topup-hero`.
  - Live modal not clicked (`SOLA_FAKE=false`).
- **Left for later:** unused Click/Payme PNGs still in `public/img/logos/`.
