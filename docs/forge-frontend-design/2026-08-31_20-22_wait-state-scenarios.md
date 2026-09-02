# Wait-state scenarios (offline, timeout retry, OTP, 503)

- **Date:** 2026-08-31 20:22 +05
- **Mode:** SaaS + Multi-market (consumer wait physics)
- **Screens/components delivered**
  - Sticky-bottom offline strip (`data-offline-banner`) on all layouts
  - AJAX period-filter timeout → in-region retry bar, previous table kept
  - Verify: `data-otp` paste + auto-submit at 4 digits
  - `errors/503.blade.php` — billing-down page with refresh CTA
- **Decisions:**
  - Kept only waits that are real: radio drop, slow filter, SMS arriving, SOLA unreachable.
  - Did **not** add SMS resend (identify is not retried — SMS cost) or skeletons on server-rendered pages.
  - Timeout is a retry control on the data, not a toast that disappears.
- **Quality floor result:**
  - Copy in uz/ru/en; `prefers-reduced-motion` inherited; no CDN; retry is a button with the same verb as the toast used to use.
- **Left for later:**
  - Browser pass for offline (DevTools network off) and OTP paste.
