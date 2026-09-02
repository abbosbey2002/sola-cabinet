# Page heading: icon + title placement

- **Date:** 2026-08-31 19:53 +05
- **Mode:** SaaS + Multi-market
- **Screens/components delivered:**
  - `resources/views/components/page-heading.blade.php` — shared identity strip
  - `resources/views/layouts/{app,admin,guest,admin-guest}.blade.php` — heading row + document icons
  - `resources/views/partials/document-icons.blade.php` — favicon + apple-touch-icon
  - Auth/admin login heads (`login`, `verify`, `select_account`, `admin/login`) aligned to the same grid
  - Services lead and top-up subline moved under the title (not a second heading)
- **Decisions:**
  - CSS grid: icon shares the **title row only**; lead sits under the words, never under the chip. Same nav sprite per route so the mark you tap is the mark you land under.
  - 48px rounded-2xl chip (`--c-action-soft` / `--c-action`) on the page ground; on `u-card-hero` it lifts to `--c-surface` so it does not vanish into the wash.
  - No new copy: existing intros reused as `@section('lead')`. Toolbar (period form) stays `items-end`.
  - Print hides the decorative chip and collapses the grid so the title does not leave a gutter.
- **Quality floor result:** AuthTest + navigation + TopUp heading assertions pass. Vite build OK. Login HTML on :8080 shows badge → icon+title → lead. One pre-existing CabinetTest allow-list 403→302 failure, unrelated.
- **Left for later:** Inner card `h2`s (devices/tariff) still text-only; empty `/favicon.ico` Laravel stub.
