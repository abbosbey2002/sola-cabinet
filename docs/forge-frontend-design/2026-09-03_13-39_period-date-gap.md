# Period date inputs gap

- **Date:** 2026-09-03_13-39
- **Mode:** SaaS + Multi-market
- **Screens/components delivered:** `x-period-form` (finance + statistics), `.u-page-head__toolbar`
- **Decisions:** Signature stays logo-green primary + existing `u-field`. Refused a new control family. Used a 2-col grid with `gap-3` and `min-w-0` so native locale date strings cannot overflow into the gap; toolbar `w-full min-w-0 sm:w-auto` (reviewer note) so phones get the split and lead+toolbar can still sit side by side on sm+.
- **Quality floor result:** 320px-safe equal columns, 48px field height unchanged, AA tokens untouched, keyboard/focus unchanged; `npm run build` after CSS. Code review: APPROVE.
- **Left for later:** Visual check on a real Android Chrome with Russian locale (intrinsic date-input width).
