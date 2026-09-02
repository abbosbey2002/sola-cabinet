# One-time: drop aside block

- **Date:** 2026-09-02 13:44
- **Mode:** SaaS + Multi-market
- **Screens/components delivered:** `one_time` dashboard is balance + metrics only; deleted `one-time-aside`
- **Decisions:** The aside titled “Balance and next charge” duplicated last payment (and had no next charge). Metrics row already has devices, last payment, help. One-off has no tariff, so no second column.
- **Quality floor result:** Feature test asserts the aside title is gone and last payment appears once.
- **Left for later:** Unused `dash.account_state` copy in lang files.
