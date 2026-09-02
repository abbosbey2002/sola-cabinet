# Topbar balance chip

- **Date:** 2026-09-02 14:06
- **Mode:** SaaS + Multi-market + consumer (phone)
- **Screens/components delivered:** `x-nav-balance` in `partials/topbar`, before view-settings and lang-switch, no `hidden` breakpoint
- **Decisions:** Compact wallet + amount + unit, same soʻm figure as `/abonent/info.saldo`. Links to home. Negative uses `--c-danger`. Did not put it in the Home/Tariff link row — “before lang and settings” is the right-hand chrome. Call centre stays first on sm+.
- **Quality floor result:** 48px-class hit area (`min-h-[3rem]`). Feature test on `/devices` (not only home): marker present, not hidden, source order before settings.
- **Left for later:** Drawer does not repeat the amount (already on the sticky header).
