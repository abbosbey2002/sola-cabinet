# Top-up: drop iWon logo chip

- **Date:** 2026-09-02 14:56
- **Mode:** SaaS + Multi-market
- **Screens/components delivered:** `.u-topup-iwon-logo-wrap` — no white chip, no padding
- **Decisions:** User asked to remove the background behind the iWon mark. The white pad was only for dark-theme contrast; dropped it rather than keep a second surface.
- **Quality floor result:** Vite rebuild required after CSS change.
- **Left for later:** black wordmark on dark theme may need a light SVG if contrast becomes an issue.
