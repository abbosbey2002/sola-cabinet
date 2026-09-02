# One-time dashboard duplicate cards

- **Date:** 2026-09-02 13:17
- **Mode:** SaaS + Multi-market
- **Screens/components delivered:** `cabinet/dashboard/one_time.blade.php`, `components/dashboard/one-time-aside.blade.php`, `lang/{en,ru,uz}` `dash.account_state`
- **Decisions:** Keep the 2-column hero (balance | jump list) so the layout still rhymes with permanent/legal. Drop the metrics row — it repeated devices and last payment. Aside title no longer says “next charge” (one-off has no tariff). Help/offers moved into the aside so the services entry is not lost. Refused a full-width 3-card strip: that would leave the balance card even emptier on desktop.
- **Quality floor result:** Contrast/tokens unchanged. Hit areas already 48px-class rows. Copy in ru/uz/en together.
- **Left for later:** Top-up CTA still hidden when `iwon.active` is off (same gate as every other kind — not a one-off layout bug). Prod still needs the pending 503-deploy fix so `npm run build` runs.
