# Dashboard v3 — coded implementation

- **Date:** 2026-08-31 17:15 +05
- **Mode:** SaaS + Multi-market
- **Screens/components delivered:**
  - `resources/views/cabinet/index.blade.php` — 2-column hero, Web3 panel, 4 metric cards
  - `resources/views/partials/topbar.blade.php` — network badge, wallet pill slot
  - `resources/views/components/{dash-metric-card,web3-panel,wallet-connect}.blade.php`
  - `resources/views/components/account-menu.blade.php` — ID + contract labels
  - `config/web3.php`, `resources/css/app.css` tokens/utilities
  - `lang/{uz,ru,en}/app.php` — dash + web3 strings
- **Decisions:**
  - Web3 UI gated by `WEB3_ACTIVE` (default false); USDT equivalent only when `WEB3_USDT_RATE` set — no invented billing fields.
  - Contract number shown once in dashboard body (Card A); header dropdown shows ID + contract separately.
  - Tariff empty state hides cycle/next charge; active tariff shows arc + billing in Card B.
  - Primary CTA: `Balansni to'ldirish` / iWon modal; secondary: `Tarifni tanlash` outline.
- **Quality floor result:** Vite build OK; CabinetTest + TopUpTest 78/79 (1 pre-existing local DB allow-list failure).
- **Left for later:** Wallet connect JS, on-chain settlement, SOLA Token/NFT data from API.
