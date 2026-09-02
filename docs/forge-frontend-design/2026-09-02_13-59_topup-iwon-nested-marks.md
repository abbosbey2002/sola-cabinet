# Top-up: payment marks live inside iWon

- **Date:** 2026-09-02 13:59
- **Mode:** SaaS + Multi-market
- **Screens/components delivered:** `partials/topup-form.blade.php` (modal + `/topup`), `u-topup-iwon*` in `app.css`, `lang/{uz,ru,en}` `topup.methods_note`
- **Decisions:**
  - Subject: subscriber topping up; one job is “amount → iWon”, not picking a local PSP here.
  - Dropped the 2×2 bordered method cards. One iWon card now contains the explanation and a 4-up row of marks (no CSS borders). Click keeps a borderless dark pad because that PNG is white-on-black.
  - Visible copy, not sr-only: uz/ru/en now say the methods are chosen **on the iWon page**. Russian is the width budget.
  - Signature: nesting, not a second picker. Refused leaving Payme/Click/Uzcard/Humo as peer “buttons” next to iWon.
- **Quality floor result:**
  - Chrome: rendered vs natural aspect unchanged (Payme 3.15, Click 3.05, Uzcard 1.60, Humo 1.65). Four marks stay one row at 320px (`grid-cols-4` + `max-width: 100%`).
  - Card stays light in both themes so marks are not recolored. Note text `#505b46` on that light card (7.2:1).
  - `php artisan test --filter=TopUp`: 13 passed. Vite rebuilt.
  - Live cabinet modal was not clicked (`SOLA_FAKE=false`); verified shipped CSS + real logo files.
- **Left for later:** home `pay_card_hint` still says only Uzcard/Humo.
