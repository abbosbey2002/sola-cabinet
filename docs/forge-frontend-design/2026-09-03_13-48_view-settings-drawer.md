# View settings in mobile drawer

- **Date:** 2026-09-03_13-48
- **Mode:** SaaS + Multi-market
- **Screens/components delivered**
  - `components/view-settings.blade.php` — `as="panel"` inline mode (theme + text size chips)
  - `partials/topbar.blade.php` — hide disclosure below `sm`; show panel in drawer (`sm:hidden`)
- **Decisions:** Same split as language switch — nested dropdown inside an open drawer is a second hide layer. Duplicate `sola-theme` / `sola-text` radios are fine: `prefs.js` syncs all via `querySelectorAll`. Topbar keeps the disclosure from `sm` up.
- **Quality floor result**
  - Cabinet + ViewSettings tests: 68 passed
- **Left for later**
  - None
