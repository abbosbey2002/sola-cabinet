# Full sweep: local, fake billing, headless Playwright (2026-09-23)

## Environment

- App: `http://localhost:8080` (docker `nginx` + `php`), `APP_ENV=local`
- Billing: **`SOLA_FAKE=true`** (`FakeSolaServer`), so nothing reached live billing. `SOLA_FAKE_LOGIN=false`, `IWON_ACTIVE=true`
- Subscriber: fake phone `998901234567`. Account **1001** "Alisher Karimov" (permanent, `abonType=2`); account **1002** "Nodira Yusupova" (one-off, `abonType=1`) was used for A.12
- Assets: rebuilt with `npm run build` before the pass (the previous bundle predated the last `resources/` commit)
- Browser: headless Chromium via Python Playwright 1.63. The Claude-in-Chrome extension was connected only to a macOS browser, which cannot reach this machine's localhost. Viewports are real Playwright viewports (1440×900, 390×844 `is_mobile`), not emulated screenshots.
- Theme is set through `localStorage['sola-theme']` and language through `/change/lang/{ru,uz,en}`. `reduced_motion='reduce'` is on for the visual pass, because without it screenshots catch the count-up animation mid-flight (balance 116 419 → 125 000, days 6 → 8)
- Scripts: `evidence-2026-09-23/{sweep,flows,throttle}.py`

Spec: `docs/task/QA_CHECKLIST.md`. Where the checklist lags the code (C.3, C.5, B.4, F.5), the rows below follow `AGENTS.md` and live code.

## Matrix (automatic checks)

7 pages (`/`, `/tariffs`, `/devices`, `/statistics`, `/finance`, `/services`, `/topup`) × ru/uz/en × light/dark × 1440/390 = **84 cells**.
Every cell passed these checks: HTTP 200, no console errors or warnings, no page errors, no 4xx/5xx sub-requests, `scrollWidth === clientWidth` (no horizontal scroll at 390), no hyphen-minus money, no `-1` on screen, no raw translation keys, `<html lang>` matching the locale, and `data-theme` matching the requested theme.

## Coverage

| # | Requirement | Verdict | Note |
|---|---|---|---|
| A.1 | Login field | PASS | `type=tel`, autofocus, `name=login` |
| A.2 | Unknown number | PASS | toast "Абонент не найден", no code screen |
| A.3 | Valid number → code | PASS | code step renders on `/auth/login` (POST response) |
| A.4 | `throttle:5,1` login | PASS | 5×200 then 429 |
| A.5 | Wrong code | PASS | stays on code; toast shown. Copy: F-2 |
| A.6 | `throttle:10,1` verify | FAIL | 429 after 9, not 10. See F-3 |
| A.7 | Account list | PASS | 2 accounts, rendered at `/auth/verify` |
| A.8 | Select account | PASS | `/`, name and l/s in topbar |
| A.9 | Guest → `/finance` | PASS | → `/auth/login` |
| A.10 | Verified → `/auth/login` | PASS | → `/` |
| A.11 | Logout | PASS | `/finance` → login afterwards |
| A.12 | Switch to 1002 | PASS | no 1001 values left; balance `−18 500` with a true minus |
| A.12c | Foreign `accId` | PASS | `/select/account/9999` → 403 |
| B.* | Topbar/nav | PASS | visual, all 12 theme/lang/viewport cells |
| C.1 | Balance with unit | PASS | `125 000 сум`; topbar matches card once settled |
| C.5 | Next charge | PASS | `charge_date` from API (01.10.2026), 8 days left, correct for 2026-09-23 |
| C.6 / E.1 | Device count | PASS | home 3 = `/devices` 3 |
| C (gauge) | Days-left ring | FAIL | F-4 (ru label overlaps ring) |
| D.1 | Tariff list with price+unit | PASS | 2 of 5 fake tariffs shown (allow-list) |
| D.3 | Confirmation shows name **and price** | FAIL | F-5: timing dialog shows name only |
| D.4 | Cancel | PASS | (first run; the later FAIL was a leftover from my own earlier submit) |
| D.5 | Switch "from next period" | PASS | toast "Тариф успешно подключен!" |
| D.6 | Pending tariff shown | PASS | "Со следующего месяца: Paket 30 kun."; home next payment → 12 000 сум |
| D.7 | GET 405 / no-CSRF 419 | PASS | |
| E.3 | `connect_cost` | PASS | "15 000 сум", no `-1` |
| E.4 | Add → custom dialog | PASS | no native `confirm()` |
| E.5 | Add device | PASS | 3 → 4, home 4 |
| E.7 | Delete with cancel/confirm | PASS | cancel = no change; confirm 4 → 3 |
| F.2 | Period filter AJAX | PASS | no page reload |
| F.5 | 12-month cap | N/A | cap removed on purpose in `604b960`; 33-month range loads (100 pages) |
| F.7 | end < start | QUESTION | F-6: silently swapped, no message |
| F.10 | `/traffic/detail` redirect | PASS | 302 → `/statistics` |
| G.1 | Finance columns | PASS | |
| G.2 | `дд.мм.гггг` | PASS | all 3 locales |
| G.5 | Paid = green | PASS | `u-pill-ok` |
| G.9 | Sort by amount (`data-value`) | PASS | `aria-sort=ascending` |
| G.10 | Search | PASS | "Payme" → only Payme rows |
| G.15 | `/payment/history` redirect | PASS | 302 → `/finance` |
| TU.1 | Amount < 1000 | PASS | 302 → `/topup` |
| TU.2 | Valid amount | PASS | 302 → `business-frame.iwon.uz?amount=1000000…` (10 000 soʻm → tiyin). Not followed |
| I.5 | 390px no h-scroll | PASS | all 42 mobile cells |
| — | English statistics heading | FAIL | F-1 |

## Findings

```
F-1 · LOW · /statistics · en · all themes
Seen:      heading "The tariff statisticst" (lang/en/app.php:177). Visually hidden, but read by screen readers and in the outline
Expected:  "Traffic statistics" (ru: "Статистика трафика")
Owner:     forge-frontend-design (copy)
```

```
F-2 · MEDIUM · login/verify and every SOLA error toast · uz, en
Seen:      lang/uz/errors.php and lang/en/errors.php hold the Russian text for every code (0, 100–127).
           An uz/en subscriber with a wrong SMS code sees "Не верный код СМС или пароль".
           The ru text also has a typo: "Не верный" → "Неверный" (and "объязательный" → "обязательный")
Expected:  AGENTS.md "copy in ru, uz and en together"; carries forward prior F-1 (2026-09-01, codes 110/126)
Evidence:  evidence-2026-09-23/A5-wrong-code-ru.png
Owner:     forge-frontend-design (copy)
```

```
F-3 · MEDIUM · POST /auth/login + POST /auth/verify · guest
Seen:      login and verify share one rate-limit bucket. After 1 login POST, verify returned 429 on the 10th attempt, not the 11th.
           Plain `throttle:N,1` keys guests by domain+IP, not by route (routes/web.php:85,89).
           So a subscriber who mistypes the code 4 times can no longer request a new SMS for a minute,
           and everyone behind one carrier NAT IP shares the 5/min login budget.
Expected:  QA A.4/A.6: separate 5/min and 10/min limits
Owner:     forge-debugger → forge-senior-backend-engineer (RISK=true: auth)
```

```
F-4 · LOW · / · ru · light+dark · 1440
Seen:      "дней осталось" under the days-left number is wider than the ring's inner circle and overlaps the arc stroke on both sides
           uz "kun qoldi" fits
Evidence:  evidence-2026-09-23/gauge-ru-overlap.png vs gauge-uz-ok.png
Owner:     forge-frontend-design
```

```
F-5 · MEDIUM · /tariffs · ru · permanent subscriber
Seen:      the only confirmation before a paid tariff switch is the "Когда подключить" dialog. It names the tariff (Paket 30 kun) but not its price;
           choosing an option submits right away
Expected:  QA D.3 / TZ §13: the dialog names the tariff and its price
Evidence:  evidence-2026-09-23/D3-timing-dialog.png
Owner:     forge-frontend-design
```

```
F-6 · QUESTION · /statistics, /finance · end < start
Seen:      20.09 → 01.09 is silently swapped by Period::between() (deliberate, documented in code); data for 01–20.09 is shown
           while the inputs still read 20.09 / 01.09. No message
Expected:  QA F.7 asks for a validation error. Decide: keep the swap (then re-order the inputs) or reject
Evidence:  evidence-2026-09-23/F7-reversed-range.png
Owner:     CEO decision
```

```
F-7 · LOW · all pages with .u-rise cards · prefers-reduced-motion: reduce
Seen:      the reduced-motion rule (resources/css/app.css:1269) zeroes animation-duration but not animation-delay;
           `.u-rise` uses `both` + `--i × 55ms`, so reduced-motion users still see cards pop in one after another
Owner:     forge-frontend-design
```

Not defects (checked):
- Status pill `active` in ru/uz: raw `/abonent/info.status` from the fake. Real billing's value is unknown, so it needs checking against live data.
- `to'langan` on ru/en `/finance`: the fake always returns the Uzbek status. It is data, not a translation bug.
- Native date inputs show `mm/dd/yyyy` because headless Chromium runs in the en-US locale.
- "Нет сети" banner text is in the DOM with `hidden`, so it is not visible.

## State changes made (fake billing cache only)

| Action | Before | After |
|---|---|---|
| Device add + delete (×3 runs) | 1001: 3 devices | 3 devices (net zero) |
| Tariff connect 9 "Paket 30 kun", `timing=month` (twice, once from a crashed run) | 1001: no pending change | pending "Paket 30 kun" from 01.10.2026 |
| POST `/topup` 10 000 soʻm (not followed) | — | log `iwon.topup.initiated` `additional_id=17901441944984597`; no iWon page opened |
| `.env` | `SOLA_FAKE=false` | `SOLA_FAKE=true` (user-approved) |

## Not tested, and why

- Real billing / real SMS: this pass used the fake by request
- Paying on the iWon form: external payment
- Account 1002 flows beyond A.12: tariff connect for a one-off subscriber (`now` forced), and the device 403
- Legal-entity 403: the fake has no `legal` field
- F.6 (one month failing → incomplete note), E.6 (device limit refusal): need fake failure injection
- G.11 last page, G.12/G.13 print output, F.9 no-JS fallback
- Text size lg/xl, and the offline banner with the network actually dropped
- Light/dark "system" (empty) preference
- Chrome at a real phone DPI and Safari/iOS

## Re-check next pass

F-1…F-5 after fixes; F-6 after the CEO decides; the `active` pill against live billing; A.12 on the live API.
