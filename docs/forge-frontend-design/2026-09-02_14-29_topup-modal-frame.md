# Top-up modal: 24px title and 40/28 frame

- **Date:** 2026-09-02 14:29
- **Mode:** SaaS + Multi-market
- **Screens/components delivered:** top-up modal panel/header/body; title `text-2xl`
- **Decisions:**
  - Title `text-2xl` (1.5rem = 24px at 16px root), still Manrope via `.u-display`.
  - Frame: `p-10` (40px), `gap-7` (28px), `rounded-[1.75rem]` (28px), `bg-surface` (#fff in light), spec shadow on ink `20,29,9`. Header/body extra padding removed so 40px is the only inset.
  - Same 28px gap on `.u-topup` / `.u-topup-form` so iWon, amount, submit stack to the frame.
- **Quality floor result:** Vite rebuilt. Shadow not clipped (`overflow-visible` on the topup panel).
- **Left for later:** none.
