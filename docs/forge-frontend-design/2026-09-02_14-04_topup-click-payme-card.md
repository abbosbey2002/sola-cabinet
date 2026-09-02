# Top-up marks: Click, Payme, card

- **Date:** 2026-09-02 14:04
- **Mode:** SaaS + Multi-market
- **Screens/components delivered:** iWon marks row in `partials/topup-form.blade.php`; `x-icon` `card` glyph; `lang/{uz,ru,en}` `topup.methods_note`
- **Decisions:**
  - Order is Click → Payme → one bank-card mark. Uzcard and Humo brand PNGs are gone from this row (do not ship fake combined logos).
  - Generic stroke `card` in the existing sprite (`currentColor`, same 1.9 stroke as neighbours). Accessible name reuses `app.topup.card_methods` (“Uzcard · Humo”).
  - Copy follows the row: Click, Payme, or a card — on the iWon page.
  - Three marks, flex, not a 4-col grid with an empty cell.
- **Quality floor result:**
  - Screenshot at 28rem and 320px: order and card glyph visible; Russian note wraps, marks stay one row.
  - Card icon is `#141d09` on the always-light iWon chip (17:1). No new motion, no CDN.
  - `php artisan test --filter=TopUp`: 13 passed. Vite rebuilt.
- **Left for later:** home `pay_card_hint` still names Uzcard/Humo only.
