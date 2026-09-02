# Dashboard layout/UX — browser pass

- **Date:** 2026-08-31 20:06 +05
- **Trigger:** user asked to open the running app in the browser and check layout/UX defects on Home (`/`), after a code-review of the same screen.
- **Environment:** Docker `http://localhost:8080` (nginx :8080). `SOLA_FAKE=false` (live billing `172.19.1.201:808`, HTTP 403 without app credentials — reachable). Assets `public/build/manifest.json` 2026-08-31 19:18. `IWON_ACTIVE=true`. `WEB3_ACTIVE` unset/off. `SOLA_FAKE_LOGIN` flipped `false → true` for this pass only (documented SMS bypass into acc **1336708**), then reverted to `false` and confirmed `config('sola.fake_login')=OFF`.
- **Account:** live billing accId `1336708`, display name from fake identify `Test account 1336708`; devices page FIO `TEST PAYMENTS`. Same Smart 50 / 0 so'm / 2 devices / 24.09.2026 charge as the user's earlier screenshot. Contract number **not rendered** this pass — FakeLoginServer `/identify` omits `login`, so `billing_login` cookie is empty (user's SMS session showed `TTP19000036_93`).
- **Browser:** Google Chrome 152 headless via Playwright (`channel` = system `/usr/bin/google-chrome`), new profile, viewport 1440×900 and 390×844. Console/network captured on the page.
- **Spec:** `docs/task/QA_CHECKLIST.md` §C, §I, §A (login only); known-blocked `docs/task/BAJARILMAGAN_TASKLAR.md`. Prior QA: `2026-08-29_00-58_home-page-quality-check.md`. Architecture (`AGENTS.md`) treats `/abonent/info.charge_date` as the next charge — checklist C.5/K "invented date = HIGH" is **stale**; a live `charge_date` of 24.09.2026 is PASS, not FAIL.
- **Money:** top-up modal opened, **not submitted**. No tariff connect, no device add/delete.

## Checklist (before click)

C.1–C.9 home summary · I.1–I.3/I.5 dates money overflow · layout UX from prior review (duplicate tariff, hero padding, ring colour, 0 so'm tone, empty payment copy, 390px) · login A.1/A.3 copy · top-up modal chrome only.

## Coverage table

| § | Requirement | Verdict | Note |
|---|---|---|---|
| 0.1–0.5 | Env | PASS | 8080 up; live billing; build 19:18; acc 1336708; console empty |
| A.1 | Login form | PASS | `name=login` tel, +998 |
| A.3 | Code screen after phone | FAIL F-1 | verify copy leaked markdown + truncated number |
| C.1 | Balance + so'm | PASS | `0 сум` / `0 so'm` / `0 sum`; minus N/A |
| C.2 | Current tariff | PASS | Smart 50 (billing name, not translated) |
| C.3 | Tariff price | PASS | 125 000 so'm from live `tariff_price` — checklist BLOCKED is stale |
| C.4 | Next tariff | N/A | New dashboard IA dropped the "Не выбран" row |
| C.5 | Next charge date | PASS | 24.09.2026 from billing `charge_date`, not invented. Days-left count is wrong — F-3 |
| C.6 | Device count | PASS | Home `2` matches `/devices` two rows |
| C.8 | Entry cards | PASS | `/tariffs` `/devices` `/finance` `/services` hrefs present |
| C.9 | No-tariff empty | NOT TESTED | This account has Smart 50 |
| I.1 | Date `dd.mm.yyyy` | PASS | 24.09.2026, 13.08.2026 in uz/ru/en |
| I.3 | Currency spelling | FAIL F-4 | EN warning mixes `so'm` into a `sum` page |
| I.5 | 390px no h-scroll | PASS | `scrollWidth === clientWidth === 390` on `/` |
| I.6 | 390px cards stack | PASS | heroes stack; metrics one column |
| Layout | Duplicate tariff | FAIL F-5 | Smart 50 + 125 000 on hero and metric |
| Layout | Hero equal height | FAIL F-6 | 225×24px pad vs 292×32px pad |
| Layout | Ring colour | FAIL F-7 | `--c-warn` gold with 23 days left because balance is 0 |
| Layout | `0` figure tone | FAIL F-8 | figure `rgb(229,237,221)` same as body ink |
| UX | Empty last payment | FAIL F-9 | accusatory year-empty copy under "Last payment" |
| UX | Last connected wording | FAIL F-10 | `/devices` connect dates 13.05 and 13.08; home says "last connected" |
| Top-up | Modal chrome | FAIL F-11 | `1 000 000` wraps; iWon chip is white on dark; no 125 000 preset |
| Theme | Light | NOT TESTED | script localStorage did not stick; radio in Display menu not clicked |
| `/tariffs` `/finance` `/statistics` `/services` | pages | NOT TESTED | out of scope |
| Contract copy `u-icon-btn` | | NOT TESTED | contract block absent under fake identify; still missing CSS in repo |

## Findings

```
F-1 · HIGH · /auth/login (POST → verify view) · ru · dark · 1440px
Seen:      lead reads "Отправили код на **34567. Введите его в поле ниже."
Expected:  A.3 / copy — masked phone, no markdown. Blade uses substr(session phone, 7, 12);
           lang `app.auth.send_phone` still has `**:phone` in uz/ru/en.
Evidence:  evidence/00-verify.png, evidence/verify-copy.json
Owner:     forge-frontend-design (copy) / forge-debugger (mask)
```

```
F-3 · MEDIUM · / · uz+ru+en · dark · 1440px and 390px
Seen:      ring caption "23" days left; next charge row "24.09.2026". Container clock
           2026-08-31 15:06 UTC. 31.08 → 24.09 is 24 whole days.
Expected:  C.5 + ChargeCycle daysLeft matches the date shown beside it.
Evidence:  evidence/facts-uz-dark-390.json (23 kun qoldi), evidence/01-dash-uz-dark-1440.png
Owner:     forge-debugger
```

```
F-4 · MEDIUM · / · en · dark · 1440px
Seen:      warning "Add 125 000 so'm by 24.09.2026" while the rest of the EN page uses "sum".
Expected:  I.3 — one currency word per locale.
Evidence:  evidence/06-dash-en-dark-1440.png, evidence/facts-en-dark-1440.json
Owner:     forge-frontend-design
```

```
F-5 · HIGH · / · uz+ru+en · dark · 1440px and 390px
Seen:      Smart 50 and 125 000 appear on the tariff hero and again on the first metric card.
           24.09.2026 appears in the balance warning and in "next payment".
Expected:  TZ §3.1 one summary; home answers "will my balance last?"
Evidence:  evidence/01-dash-uz-dark-1440.png, evidence/07-dash-uz-dark-390.png
Owner:     forge-frontend-design
```

```
F-6 · MEDIUM · / · ru+en · dark · 1440px
Seen:      balance hero height 225 padding 24px; tariff hero height 292 padding 32px.
           Grid is items-start — bottoms do not line up.
Expected:  two-column hero of equal visual weight.
Evidence:  evidence/facts-uz-dark-1440.json heroes[], evidence/01-dash-uz-dark-1440.png
Owner:     forge-frontend-design
```

```
F-7 · MEDIUM · / · uz+ru+en · dark · 1440/390
Seen:      arc stroke var(--c-warn) → rgb(240, 194, 74) while 23 days remain.
           Colour is bound to low-balance tone, not to days remaining.
Expected:  warn colour for urgency of time or a separate money alert, not both.
Evidence:  evidence/facts-uz-dark-390.json ringStroke, evidence/07-dash-uz-dark-390.png
Owner:     forge-frontend-design
```

```
F-8 · LOW · / · uz+ru+en · dark · 1440/390
Seen:      "0 so'm" / "0 сум" figure color rgb(229, 237, 221) — same as body ink.
Expected:  low/empty balance readable as a problem before the brown note.
Evidence:  evidence/facts-en-dark-1440.json figureColor
Owner:     forge-frontend-design
```

```
F-9 · MEDIUM · / · uz+ru+en · dark · 1440/390
Seen:      Last-payment tile value is empty; hint is "Oxirgi 12 oyda to'lov qilmagansiz" /
           "За последние 12 месяцев платежей не было" / "No payments in the last 12 months"
           on an account with an active Smart 50 and a next charge.
Expected:  honest empty state, not an accusation; 12-month /acct/payments window is by design.
Evidence:  evidence/01-dash-uz-dark-1440.png, evidence/06-dash-en-dark-1440.png
Owner:     forge-frontend-design
```

```
F-10 · LOW · / vs /devices · uz · dark · 390px
Seen:      home "Oxirgi ulangan: 13.08.2026". /devices rows connect 13.05.2026 and 13.08.2026.
Expected:  copy that this is permit connect_date, not last online session.
Evidence:  evidence/07-dash-uz-dark-390.png, evidence/devices-text.txt
Owner:     forge-frontend-design
```

```
F-11 · MEDIUM · / (top-up modal) · ru · dark · 1440px
Seen:      preset "1 000 000" wraps to two lines; iWon selected chip is a white slab on dark UI;
           quick amounts 100k/250k/500k/1m — needed top-up is 125 000, not in the chips.
           Modal opened; pay was not clicked.
Expected:  dark-theme tokens (no hardcoded white); presets readable; a chip matching the deficit is a product question.
Evidence:  evidence/02-topup-modal-uz-dark.png
Owner:     forge-frontend-design
```

## State changes made

none. Login used `SOLA_FAKE_LOGIN` identify/verify stubs (no SMS). Top-up modal opened and dismissed. `SOLA_FAKE_LOGIN` restored to `false`.

## Not tested, and why

- Light theme and Display radio — localStorage injection did not change `data-theme`; need a click on the real control.
- Contract number + copy button — fake identify has no `login`; user's SMS screenshot still the only live evidence of `TTP19000036_93`.
- No-tariff / negative-balance / legal-entity / one-time layouts.
- `/tariffs`, `/finance`, `/statistics`, `/services` full pages (devices list only to match C.6).
- Submitting iWon, connecting a tariff, adding/removing a device.
- Text-size 145% / print stylesheet.

## What the next pass should re-check

- F-1 verify copy after the `**:phone` strings and substr mask are fixed.
- F-3 daysLeft vs 24.09.2026 with frozen `Carbon::setTestNow('2026-08-31')`.
- F-5 duplicate tariff metric after IA change.
- Light theme via the Display control.
- Contract copy target with a real `/identify.login`.
- 390px overflow on `/finance` and `/tariffs`.
