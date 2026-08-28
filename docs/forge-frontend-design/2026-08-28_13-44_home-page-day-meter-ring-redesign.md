# Home page day-meter: ring redesign (client TZ point #5)

- **Date:** 2026-08-28 13:44
- **Mode:** SaaS (subscriber dashboard, "kabinet") — existing design system honored throughout (u-card/u-label/u-figure/CSS custom properties), no new tokens invented beyond what the new component needed.
- **Screens/components delivered:**
  - Design proposal artifact (3 candidate directions: Ring / Calendar grid / Minimal) published for client sign-off — https://claude.ai/code/artifact/522c00fe-2e27-4409-b49a-853a8ba6114e
  - `resources/views/components/day-meter.blade.php` — rewritten: SVG radial progress ring (day-count centered inside) + a torn-calendar-page chip (month abbreviation + day) for the charge date, replacing the old scrubber/slider-track component.
  - `resources/css/app.css` — old `.u-meter-track/-fill/-handle/-charge` rules replaced with `.u-ring-row/.u-ring/.u-ring-track/.u-ring-fill/.u-ring-count/.u-cal-chip/.u-cal-chip-month/.u-cal-chip-day`.
  - `lang/{uz,ru,en}/app.php` — added `dash.days_left_unit` (pluralized "kun"/"day"/"день", paired with the ring's number so it never repeats it, unlike the existing `days_left` sentence).
  - `tests/Feature/CabinetTest.php` — rewrote the 2 day-meter tests for the new markup (`the_day_meter_counts_the_days_to_the_next_charge`, `the_ring_is_fully_filled_when_today_is_the_charge_day`); renamed 4 `assertSee('u-meter-track', ...)`/`assertDontSee(...)` checks elsewhere to `u-ring-row`.

- **Decisions:**
  - Client's complaint: the old meter "looks like an input range" (a slider) and asked for something closer to a calendar/deadline ("muddat") look, with "unnecessary components" trimmed. Presented 3 concrete directions with real app tokens/content before building anything; client picked **Ring** — it keeps the at-a-glance cycle-progress cue (a linear/grid alternative loses that or gets visually complex) while reading as a clock/timer rather than a draggable slider.
  - Dropped the old design's separate "charge day" diamond marker entirely: a ring's own 0%/100% endpoints already ARE the cycle boundaries, so a second marker would repeat information the shape itself already gives — no special-casing needed for `isChargeDay()`/`isOverdue()` in the component, they just resolve to a fully-filled ring (verified sane by the code-reviewer for all three locales).
  - Reused the existing `app.months.*` translations (already used by `MonthList` for month pickers) for the chip's month abbreviation instead of adding a second month-name list — truncated to 4 characters, not the more typical 3, because Uzbek "Iyun"/"Iyul" (June/July) only tell apart at the 4th letter.
  - Kept the existing text sentence below the meter (`app.dash.days_left`/`charge_today`/`charge_passed`, unchanged) as the real information source — the ring/chip stay `aria-hidden`, a visual echo, not a second source of truth. This is why only ONE new translation key (`days_left_unit`) was needed instead of duplicating the existing sentence logic with icon states for edge cases.
  - Found and fixed a real layout bug during manual browser verification (not caught by tests, since tests don't check horizontal centering): the dark plate left a large empty gutter next to the ring+chip on wide viewports. Fixed with `justify-content: center` on `.u-ring-row`.

- **Quality floor result:**
  - Manually verified in a live browser (`SOLA_FAKE=true php artisan serve`, real login flow) in both light and dark theme at desktop width — renders correctly, matches the approved mockup's direction.
  - Did NOT verify at mobile/narrow viewport in the browser (resize-window tooling was unreliable this session); code-reviewer did a manual width calculation instead (ring 84px + chip 58px + gap + padding ≈ 200px, well under the app's 360px baseline) and found nothing fragile.
  - Full PHPUnit suite: 143 passed / 510 assertions. `npm run build` clean.
  - `forge-code-reviewer`: **APPROVE**, no blockers. Two cosmetic nitpicks noted, not fixed (see below).
  - RISK=false (pure decorative/visual change) — no security audit run, per routing rules.

- **Left for later:**
  - Nitpick (not blocking): `.u-cal-chip-month` reuses `--c-danger` for a decorative red calendar-tab strip, not an actual danger/error state — works visually but a future dev touching danger-color semantics should know this component borrows it purely for color, not meaning.
  - A real mobile-viewport (360-390px) screenshot check in-browser would still be worth doing when browser-resize tooling is reliable again, even though the static math checks out.

- **User must do:** Nothing — no migrations, env vars, or deploy steps. Note: `npm run build` output was regenerated locally during verification (gitignored, not committed).
