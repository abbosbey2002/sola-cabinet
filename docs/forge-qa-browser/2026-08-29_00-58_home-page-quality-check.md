# Home page ("Главная") — quality check for production readiness

- **Date:** 2026-08-29 00:58
- **Trigger:** user asked whether the home page is good enough to show in production ("productionga ko'rsatishga yaxshilash kerakmasmi").
- **Environment:** local Docker (`http://localhost:8080`), `SOLA_FAKE` temporarily flipped `false → true` for this pass only (no VPN/live-billing access in this session), then reverted to `false` before ending. Fixture accounts used: 1001 "Alisher Karimov" (positive balance, permanent) and 1002 "Nodira Yusupova" (negative balance, one-time/temporary) — both reachable via phone `+998901234567`, SMS code `1234` (fake accepts any 4 digits except `0000`).
- **Spec used:** `docs/task/BAJARILMAGAN_TASKLAR.md` (known-blocked list, 2026-08-07) — `docs/task/tz.md` is empty, the real spec (`tz_v1.docx`) was not re-extracted this pass, coverage below is against the known-blocked list and the already-documented critique/fix history (`docs/forge-frontend-design/2026-08-28_18-30_home-page-uiux-critique.md`, `docs/forge-debugger/2026-08-28_15-05_...md`). Prior QA notes read: `2026-08-27_17-02_tz-v2-fixes-verification.md`, `2026-08-24_16-43_full-sweep.md`.

## Coverage table

| Item | RU | UZ | EN | Light | Dark | Note |
|---|---|---|---|---|---|---|
| Balance figure + sign | PASS | — | — | PASS | PASS | Positive: plain black. Negative: red with true minus (`−18 500`), not a hyphen |
| Next-charge row | PASS | PASS | PASS | PASS | PASS | Amount + `dd.mm.yyyy` date, both accounts |
| Balance-state alert (ok/negative) | PASS | PASS | PASS | PASS | PASS | Colour+icon+word on both; negative tone reads correctly in both themes |
| Pay-card (contract + copy + top-up) | PASS | — | — | PASS | PASS | `iwon.active` is ON in this env — card renders contract, copy button, "Top up balance" button, same danger tone as the alert above it (no stacked-alert issue — confirms the 2026-08-28 fix) |
| Login row | N/A | — | — | — | — | Fixture accounts carry no `billing_login` — row correctly absent, no blank/dash shown |
| Tariff / Devices / Last payment tiles | PASS | PASS | PASS | PASS | PASS | Devices tile shows count only (no online/offline breakdown) on both accounts; singular pluralization correct in all 3 locales ("Одно устройство" / "Bitta qurilma" / "One device") |
| Services banner | PASS | PASS | PASS | — | — | |
| Text scaling (Largest) | PASS | — | — | — | — | Header wraps to two rows gracefully, no overlap, no horizontal scroll at 1410px |
| Console errors | PASS | — | — | — | — | Zero app-origin errors across load + both accounts + theme/language switches |
| Network requests | PASS | — | — | — | — | All app assets 200; two `chrome-extension://` entries are this browser profile's own extensions, not app requests |
| No-tariff CTA banner | NOT TESTED | — | — | — | — | Both fixture accounts have a tariff; the hide/show logic itself has an automated test but wasn't observed live (same gap noted in the 2026-08-27 QA pass) |
| Mobile viewport (390px) | NOT TESTED | — | — | — | — | Same tooling limitation as the 2026-08-24 and 2026-08-27 passes: `resize_window` succeeds but `window.innerWidth` stays at the host's width (1505px here). Not worked around (skill forbids emulating via screenshot shrink) |
| `nextTariffCost()` on a real queued-switch account | NOT TESTED | — | — | — | — | Requires live billing + an account with a tariff switch actually queued; out of reach without VPN. Flagged as open in the 2026-08-28 debugger note itself |

## Findings

None CRITICAL or HIGH. One LOW:

```
F-1 · LOW · / · ru/uz/en · light+dark · 1440px
Seen:      the balance-ok/negative sentence inside the alert box shows the charge date as "01.09" (day.month only), while the "Следующее списание" row two lines above shows "01.09.2026" (full dd.mm.yyyy) for the same date.
Expected:  BAJARILMAGAN_TASKLAR.md item A / TZ §11 states dates render dd.mm.yyyy consistently.
Evidence:  screenshots taken this session (see conversation) — RU: "списание 25 000 сум 01.09 будет покрыто." vs "01.09.2026" above it.
Owner:     forge-frontend-design (copy string `app.dash.balance_ok`/`balance_negative` — cosmetic, one-line fix)
```

## State changes made

None. No form was submitted that mutates account state (no top-up, no tariff change, no device add/delete). The only state change was the deliberate, reverted `SOLA_FAKE` env flip.

## Not tested, and why

- **Mobile viewport (390px)** — tooling limitation, third consecutive pass to hit this (see 2026-08-24, 2026-08-27). Needs a differently configured browser session or a manual phone/DevTools check.
- **No-tariff account state** (the CTA banner added in the 2026-08-28 critique fix) — no such fixture account available; covered by an automated test only.
- **`nextTariffCost()` on a real account with a queued tariff switch** — needs VPN + live billing, not available to this session. This is the one item the 2026-08-28 debugger note itself left open.
- **Statistics/Tariff/Devices/Payments/Services pages** — out of scope for this pass, which targeted only the Home page per the user's question.
- **Print stylesheet** — not exercised.

## Verdict

The Home page holds up well under a live-data sweep across 3 languages, both themes, both a healthy and a negative-balance account, and enlarged text — zero console/network errors, zero HIGH/CRITICAL findings, one cosmetic LOW. Consistent with its documented critique-and-fix history. Genuinely presentable for a demo today. Not yet a final "ship" sign-off because two items remain unverified through no fault of the code itself (mobile viewport — tooling gap; queued-tariff-switch cost — needs live billing access) and the current fix set (index.blade.php, pay-card.blade.php, topbar.blade.php) is still uncommitted in the working tree.

## What the next pass should re-check

- Mobile viewport, via a session/tool that can actually resize, or a manual check.
- A no-tariff fixture account, once one exists, to see the CTA banner live.
- F-1 (date format inconsistency) after it's fixed.
