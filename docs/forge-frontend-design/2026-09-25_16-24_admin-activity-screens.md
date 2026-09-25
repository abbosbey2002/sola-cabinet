# Admin screens for the activity journal

- **Date:** 2026-09-25 16:24
- **Mode:** SaaS (admin work tool) + Multi-market (ru/uz/en)
- **Screens/components delivered:**
  - `admin/stats/{index,funnel,segments,errors}` — stat tiles, daily page-view bar chart (inline SVG, per-bar `<title>` tooltip, table view in `<details>`), menu / actions / monthly tables, cohort funnels with bars, segment share bars, top errors.
  - `admin/tariff-changes/{index,show}` — 7-field filter bar, summary tiles, attempts table, top tariffs, "from → to" pairs, CSV link; detail page with verdict card + account/money/old/new/request sections + masked snapshot.
  - `admin/accounts/{index,show}` — search, account header, tariff changes, paginated event timeline.
  - Components `x-admin.{period-form,stat,result-pill,outcome-pill,pager}`, partials `stats-tabs`, `journal-off`; role-aware sidebar + scrolling mobile nav in `layouts/admin`.
- **Decisions:** no new tokens — reuses the cabinet's green palette and `u-*` vocabulary. One status contract for results (colour + icon + label, never colour alone). Chart colour `--c-action` validated with the dataviz validator (light passes; admin is always light theme). Page h1 stays sr-only per the existing `page-heading` rule. Menu/actions stacked full-width: side by side, the 8-column table fell under the 40rem container query and became cards on desktop.
- **Quality floor result:** Playwright at 1440 and 390 for all three roles — 55/55 checks, no JS errors, no horizontal page scroll at 390; keyboard: all controls are native links/buttons/inputs; nowrap fixes for names, masked numbers and pills after the first screenshot pass.
- **Left for later:** a density toggle; chart hover crosshair (native tooltip only).
