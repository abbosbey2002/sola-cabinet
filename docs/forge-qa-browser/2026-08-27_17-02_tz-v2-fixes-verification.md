# TZ v2 fixes verification — local Docker, live billing

- **Date:** 2026-08-27 17:02
- **Environment:** local Docker (`http://127.0.0.1:8080`), `SOLA_FAKE=false` — real live SOLA billing (`API_IP=172.19.1.201:808`), confirmed reachable (HTTP 403 on a bare unauthenticated GET, expected). Account: "TEST PAYMENTS", Лицевой счёт `1336708`, already-authenticated session in the CEO's own Chrome (logged in by the CEO — SMS login is not something this session can do itself).
- **Spec used:** `Доработка личного кабинета ТЗ_v2.docx` (screenshot-annotated review, 7 numbered items, extracted and cross-referenced earlier this session) plus the in-session verbal corrections (manager-not-channel framing). `docs/task/BAJARILMAGAN_TASKLAR.md` read as the known-blocked list. Prior QA note `docs/forge-qa-browser/2026-08-24_16-43_full-sweep.md` read — its F-1 (payments failing against live billing after the pay_begin/pay_end migration) was the main carry-forward item to re-check.
- **Trigger:** verifying the 4-item TZ v2 fix batch (commits `db8f440`, `f5e5043`) plus the earlier tariff-modal/traffic-range work from this same session, all merged into `main`, against the real running app.

## Coverage table

| Item | Requirement | RU | UZ | EN | Dark theme | Note |
|---|---|---|---|---|---|---|
| 1 | `/statistics` default period = last 1 month | PASS | — | — | — | 07/27/2026–08/27/2026 shown, matches `Period::lastMonth()` |
| 2 | Home tariff card hidden when no tariff | PASS (regression only) | — | — | — | This account has a tariff, card shows correctly; true "hidden" case NOT TESTED (no no-tariff account available) |
| 3 | Tariff timing modal: "Сейчас" note + next-charge date | PASS | PASS | PASS | PASS | Exact text incl. "Следующее списание — 24.09.2026." |
| 3 | Tariff timing modal: "next billing period" label + computed date | PASS | PASS | PASS | PASS | "Со следующего расчётного периода" / "Начнёт действовать с 01.09.2026." |
| 3b | "Смена тарифа не запланирована" | PASS | (not re-checked) | (not re-checked) | — | Exact match, no tariff name |
| 4 | Add-device modal copy | PASS | PASS | PASS | — | "Стоимость подключения дополнительного устройства — 25 000 сум." |
| 5 | Negative payment → "Списание" badge, neutral tone | PASS | PASS | PASS | PASS | 5/5 negative rows correct, 4/4 positive rows still "оплачено"/green |
| 5 (carried forward) | F-1 from 2026-08-24 (payments failing against live billing) | PASS (resolved) | — | — | — | 9 payments loaded correctly, 250 000 сум total, no error banner |
| 6 | "Менеджер в Telegram" card, correct copy/icon/link | PASS | PASS | PASS | PASS | href=`https://t.me/sola_911`, target=`_blank`, person icon (not chat bubble) |
| — | Mobile viewport (390px) | NOT TESTED | — | — | — | Same tooling limitation as 2026-08-24: `resize_window` succeeds but `window.innerWidth` stays at the host's 1745px. Not worked around. |
| — | Console/network errors | PASS | — | — | — | Zero app-origin errors on `/`, `/finance`, `/statistics`, `/tariffs`, `/devices`, `/services`. Two `[EXCEPTION]` console entries are generic Chrome-extension messaging noise (`chrome-extension://...`), unrelated to the app. |

## Findings

None. All 4 TZ v2 items plus the two carried-over checks (F-1, tariff modal copy) pass in RU/UZ/EN and in both themes where checked.

## A tooling note, not an app defect

`computer` `left_click` with pixel coordinates taken from a screenshot **did not reliably hit the intended element** in this session — clicking "Сменить тариф" by coordinate silently did nothing (button state unchanged, modal never opened) while the identical action via `element.click()` in JS, or via a `find`-resolved `ref`, worked immediately. Root cause: the screenshot tool returned 1424–1568px-wide images while `window.innerWidth` reports 1745px — a scale mismatch between screenshot pixel space and actual page CSS pixels in this environment. Mitigation used for the rest of this pass: ref-based clicks and direct JS dispatch instead of raw coordinates. Flagging this so a future QA pass in this same environment doesn't lose time on it, and doesn't misreport a coordinate-click failure as an app bug (it very nearly did here — the accessibility tree tools (`find`/`read_page`) report on modal content **regardless of its actual `display`/`hidden` state**, which briefly looked like "the modal opened" when it hadn't; ground truth came from `getComputedStyle(...).display` via JS, not from `find`).

## State changes made

None. Tariff-switch flow was driven to the timing-modal confirmation step only (never submitted — no `POST /tariffs/connect` fired). Add-device flow was driven to the confirm-modal step only, then dismissed with "Нет"/"No" (never submitted — no `POST /devices/add` fired). No payment, no tariff change, no device added.

## Not tested, and why

- **Mobile viewport (390px)** — tooling limitation (see above), same as the 2026-08-24 pass. Needs either a differently-configured browser session or a manual phone/DevTools check by the CEO.
- **Home page tariff card truly hidden (no-tariff account)** — only one account was available this session ("TEST PAYMENTS", has a tariff). The hide-logic itself is already covered by an automated test (`tests/Feature/CabinetTest.php::the_dashboard_tariff_card_is_hidden_when_there_is_no_tariff`), but not observed live against real billing.
- **Item 3b copy in UZ/EN** — confirmed correct in RU only; low risk (pure string swap, same pattern as the other 5 items which were checked in all 3 locales and all passed), not re-verified live.
- **Speed test, full nav/language-switch sweep of every other page untouched by this session's changes** — out of scope for this pass, which targeted only the TZ v2 diff.
- **Item 2 from the docx (tariff list: Smart 100 missing / Smart 150 appearing / Smart 75 closing Sept 1)** — confirmed non-code (admin/`TariffVisibility` data task) earlier in this session with the CEO's sign-off; not re-litigated here.
- **Item 5 from the docx ("couldn't verify anything" on Statistics)** — reconfirmed as an honest empty state, not a bug: this account genuinely has 0 traffic in the current default period.

## What the next pass should re-check

- Mobile viewport, once the browser tool can actually resize (or via a manual pass) — this is now the only open item from BOTH the 2026-08-24 and this pass.
- If a no-tariff test account becomes available, confirm the home page tariff card is actually absent (not just correct when a tariff exists).
