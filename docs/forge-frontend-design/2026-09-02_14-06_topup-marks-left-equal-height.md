# Top-up marks: left pack, equal height

- **Date:** 2026-09-02 14:06
- **Mode:** SaaS + Multi-market
- **Screens/components delivered:** `.u-topup-iwon-marks` row; card glyph inset so it fills the 28px box
- **Decisions:**
  - `justify-start` + `gap-2` (8px) so Click / Payme / card sit left under the iWon copy, not centered with large gaps.
  - One height: marks, PNGs, and card icon are all `1.75rem` (28px). Width stays `auto` + `object-contain` so Click 3.06 and Payme 3.15 are not squashed.
- **Quality floor result:**
  - Chrome: all three marks 28px tall; Click 86×28, Payme 88×28 (native ratios); row 15px from the card’s left (padding).
  - Vite rebuilt. No copy change.
- **Left for later:** none on this row.
