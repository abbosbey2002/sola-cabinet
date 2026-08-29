# Copy button restyle + account-id visibility

- **Date:** 2026-08-28 17:00
- **Mode:** SaaS (kabinet dashboard) — reuses an existing icon-button pattern already in the header, no new component.
- **Screens/components delivered:**
  - `resources/views/components/pay-card.blade.php`
  - `resources/views/cabinet/index.blade.php` — Home hero card, Login row
  - `resources/views/components/account-menu.blade.php`
  - `resources/views/partials/topbar.blade.php` — mobile drawer facts

## Decisions

Checked the icon-only copy buttons from a prior pass against the real
running instance (`http://127.0.0.1:8080`, the user's own logged-in
session). User feedback: "copy qilish tugmasini ko'ring yaxshi chiqmagan
mos emas" — the button didn't look right.

Diagnosed by zooming into the actual rendered button (dark theme,
account-menu's "Лицевой счет" panel and the Home hero card's Login row):
`u-btn-ghost`'s 2px border, built for a text+icon pill, read as a heavy
ring around a single small icon once the label was dropped — especially
next to a 13px muted id string, where the 44px bordered circle visually
outweighed the text it sat next to.

Fix: dropped `u-btn-ghost` for the **same borderless icon-button pattern
the header already uses** for its view-settings and menu-toggle buttons
(`grid size-11 place-items-center rounded-full text-muted
hover:bg-surface-2 hover:text-ink` — no border, no fixed padding, just a
fixed circle that highlights on hover). Reuse over invention: this is an
existing, already-vetted pattern in the same file family, not a new one.
Touch target stayed at the accessibility floor (44px, matching every
other icon-only control on this page) — the fix was the border and the
content-hugging shape, not the size. Standardized the copy/check icon size
to `size-4` everywhere (previously `size-3.5` in the tighter contexts),
since removing the border-driven padding freed up room.

Mid-fix, a second piece of feedback arrived: "acc_id bold va ko'zga
ko'rinarli bo'lsin. hech narsa ustini yopib qo'ymasin" — the account id in
that same panel (`account-menu.blade.php`'s "Лицевой счет" header, e.g.
"1000033") was `text-sm text-muted`, visually secondary next to the bold
phone number above it. Bumped it to `text-base font-semibold text-ink` —
matching the weight/color every other "value" line on this page already
uses (the Login row, the mobile drawer's Договор/Личный кабинет facts) —
and confirmed directly on the live panel that the new borderless button
sits beside it with a clean gap, nothing overlapping.

## Quality floor result

- Verified against the real running instance the user is testing against
  (`http://127.0.0.1:8080`), not a synthetic harness — reused the same
  browser session already logged in, zoomed into the exact panels in
  question before and after. Confirmed: no border ring, compact icon,
  proportionate to its row; the account id is now bold/ink and fully
  clear of the button.
- `php artisan test` — 147 passed (524 assertions); no test asserts on
  button class names, so no test changes were needed.
- Touch target unchanged: still 44px (`size-11`), the same floor every
  icon-only control on this page already meets.
- `prefers-reduced-motion`: unaffected.

## Pipeline results

- `forge-code-reviewer`: not run — RISK=false, a class-attribute swap to
  an already-existing pattern plus a text-style bump, verified directly
  against the live app and the full green suite.
- `forge-security-auditor`: not run — RISK=false, pure display.

## Left for later

The account-switcher's *collapsed trigger* chip (top-right pill, before
the panel opens) still shows the account id in small muted text
(`text-xs text-muted`) — intentionally left alone this pass since the
reported issue and the live check were both about the *expanded panel*.
If the same visibility concern applies to the collapsed chip, that is a
separate, smaller follow-up (a compact header chip keeping a secondary
line muted is also a common, defensible pattern — worth confirming with
the user rather than assuming).

## User must do

Nothing — no migration, no env var, no config change. `npm run build` was
run locally and verified against the running instance directly.
