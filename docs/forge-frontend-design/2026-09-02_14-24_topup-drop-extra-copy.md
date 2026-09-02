# Top-up: drop subline and methods note

- **Date:** 2026-09-02 14:24
- **Mode:** SaaS + Multi-market
- **Screens/components delivered:** modal header, `/topup` lead, iWon row; `lang/{uz,ru,en}` keys removed
- **Decisions:** User asked both sentences gone. Modal is title-only; iWon row is logo + “iWon orqali to‘lash”. Keys `subline` / `methods_note` deleted in all three locales so they cannot reappear via leftover `@lang`.
- **Quality floor result:** `php artisan test --filter=TopUp` 13 passed. Vite rebuilt.
- **Left for later:** none.
