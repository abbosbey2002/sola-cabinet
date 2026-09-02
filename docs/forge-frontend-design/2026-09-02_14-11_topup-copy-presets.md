# Top-up copy and preset chips

- **Date:** 2026-09-02 14:11
- **Mode:** SaaS + Multi-market
- **Screens/components delivered:** `lang/{uz,ru,en}` topup strings; preset chips in `topup-form.blade.php` + `.u-topup-presets`
- **Decisions:**
  - Uzbek follows the requested ideal: complete subline (no hanging dash), iWon note names Payme/Click/Uzcard/Humo on that page, submit = title (`Hisobni to'ldirish`), footnote drops «faqat».
  - ru/en match the same jobs, sentence case; submit names the outcome (`Пополнить баланс` / `Top up balance`).
  - Preset chips show `app.ye` like the amount field. 2×2 grid always + `whitespace-nowrap` so `1 000 000 so'm` stays one line inside the 28rem modal (viewport `sm:grid-cols-4` was the wrap).
- **Quality floor result:**
  - Chrome: million chip `nowrap`, no overflow at 320px. Copy on the mock matches the three strings.
  - `php artisan test --filter=TopUp`: 13 passed. Vite rebuilt.
- **Left for later:** none from this brief.
