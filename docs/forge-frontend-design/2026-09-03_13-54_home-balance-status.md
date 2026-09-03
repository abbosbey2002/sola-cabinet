# Home balance card: billing status pill

- **Date:** 2026-09-03 13:54
- **Mode:** SaaS + Multi-market
- **Screens/components delivered:** `x-dashboard.balance-card` on home (permanent / one-off / legal)
- **Decisions:** Billing `status` is OffReasonName free text (`"Активен"` live), not a code — shown as existing `u-pill-neutral` on the Balance label row, escaped as-is, no colour/icon mapping (locale-dependent strings). Hidden when the API omits it. No new tokens, no lang keys (sr-only uses `app.cabinet.status`). Refused a second labelled “Holat” row and a topbar chip — the figure stays the hero.
- **Quality floor result:** WCAG via existing pill tokens; wraps at 320px (`max-w-full`, no nowrap); sr-only status name for the billing string; `prefers-reduced-motion` untouched (no new motion). No asset rebuild (Blade + existing CSS).
- **Left for later:** Tone (ok/warn/off) once billing publishes the value list.
