# Sola subscriber cabinet

Laravel 13 / PHP 8.3+ Blade app for `lk.sola.uz`. ROLE=BLADE: server-rendered pages, small vanilla JS modules, no SPA, no Laravel user guard.

Every subscriber number on screen comes from the SOLA billing API in real time. SQLite is only for `admins`, `enabled_tariffs`, and local Telescope. Do not invent billing fields. Do not derive a “helpful” date or amount when the API omitted it — hide the block.

Authoritative API notes: `docs/api/SOLA_API.md` (observed traffic) and `docs/api/SOLA_API_REFERENCE.md` (gateway source). `docs/task/BAJARILMAGAN_TASKLAR.md` and older task notes **lag** — prefer `AbonentProfile` comments and live code when they disagree (e.g. contract number is `/identify`.`login`; next charge is `charge_date`).

Acceptance: `docs/task/QA_CHECKLIST.md`. Spec: `docs/task/tz_v1.docx`.

## Layout

| Path | Role |
|---|---|
| `app/Services/Sola/SolaClient.php` | Stateless transport. HTTP Basic + `X-Access-Token` = `md5("<username> <secret> <exact JSON bytes>")`. Encodes the body once. |
| `app/Services/Sola/SolaResponse.php` | HTTP status + body. Business errors are HTTP 400 + `{code, errMsg}`, not exceptions. |
| `app/Support/` | Mapping and session. This is where billing quirks live. |
| `app/Http/Controllers/` | Thin: SOLA + Support → view. Shared `SolaClient` / `AbonentSession` / `ViewFactory` on `Controller`. |
| `app/Http/Requests/` | All validation. Money-changing actions are POST + CSRF. |
| `resources/views/cabinet/` | Home, tariffs, devices, services, top-up. |
| `resources/views/trafic/` | Traffic pages. **Folder name is misspelled** (`trafic` not `traffic`). Views: `trafic.index`, `trafic.result`. Do not rename casually. |
| `resources/views/payment/` | Finance history. |
| `resources/js/modules/` | Delegation-based; no-op when markup is absent. Boot from `app.js`. |
| `lang/{ru,uz,en}/` | UI + `errors.php` (SOLA codes). Default locale `ru`. |
| `config/sola.php`, `config/iwon.php` | Billing and card top-up. Runtime: `config()`, never `env()`. |

Admin catalog account for `/admin/tariffs`: `1252453` (override `?acc_id=`). Tariff ids (`SRV_ID`) are shared across accounts.

## Auth (not Laravel guards)

Subscriber state is encrypted cookies via `AbonentSession`. Names are a **deployed contract** — renaming one logs everyone out.

| Cookie | Meaning |
|---|---|
| `verify` | SMS passed |
| `login` | Phone typed at sign-in (OTP is checked against this) |
| `phone` | Same phone as **int** for `/identify` with `sendsms: 0` |
| `account` | Selected `accId` |
| `billing_login` | `/identify` per-account `login` — this **is** the contract number shown in the UI |
| `full_name` | From `/identify` `abonName`, else that row’s `login`. **Never** `/abonent/info`.`name` (can be a type label like `"Разовый абонент"`) |
| `data` | JSON `{"type": <abonType>}` |
| `lang` | Locale; whitelist in `SetLocale` (path-traversal if unsanitised) |
| `admin` | Separate admin cookie (`AdminSession`, 8h). Never reuse subscriber cookies. |

Middleware: `abonent.verified` / `abonent.guest`, `admin.auth` / `admin.guest`. Login POST `throttle:5,1`; verify POST `throttle:10,1`. `/identify` with SMS and `/verify` are **not retried** (SMS cost / one-time code).

`switchAccount`: the URL `accId` must be in this phone’s `/identify` list, else 403.

`isPermanent()` is `abonType >= 2` (legal entity vs individual split). Never `=== 2`. Temporary `0` / one-off `1` are blocked by billing codes 121/122; the UI gate is a courtesy — controllers re-check before spending.

Legal entity (`AbonentProfile::isLegalEntity()`): `/abonent/info`.`legal` is `"Физическое лицо"` or numeric `0` → individual; anything else → legal entity. Missing field → not restricted. Legal entities get **403** on tariff page and connect; topbar hides the link.

## Money, dates, units (easy to get wrong)

Almost every numeric field arrives as a **string**. `cost` and `amount` are ints.

| Field | Endpoint | Unit | Code |
|---|---|---|---|
| `saldo` | `/abonent/info` | **soʻm** (can have kopecks, e.g. `896451.61`) | `AbonentProfile::balance()` — do **not** divide by 100 |
| `saldo` | `/acct/balance` | **tiyin** | unused in the cabinet; do not mix with `/abonent/info` |
| `tariff_price` | `/abonent/info` | **soʻm** | `currentTariffCost()` — confirmed live; do **not** `/100` |
| Guess-list costs (`curr_tariff_cost`, …) | `/abonent/info` | tiyin | `/100` only if `tariff_price` absent |
| `cost` | `/tariff/available` | tiyin | `/100` for display |
| `amount` | `/acct/payments` | tiyin | `/100` |
| `connect_cost` | `/device/list` | tiyin; `"-1"` means hide the price | |
| Traffic | `/traffic/detail` | **bytes** | `/1024/1024` → MiB |
| iWon `amount` query | hosted form | tiyin | `(int) round($som * 100)` — never truncate float |

Dates:

- HTML date inputs: `Y-m-d` (`Period::startInput()`).
- `/acct/payments`: `pay_begin` / `pay_end` as `d.m.Y` (4-digit year). `d.m.y` was a production bug.
- `/traffic/detail`: `detail_begin` / `detail_end` as `d.m.Y`.
- `/tariff/connect`: `tariff_conndate` as `Y-m-d`. Body has **no** `lang` — do not add it (signature is byte-exact).
- Billing `"0000-00-00"` parses in Carbon to year -1 — treat year `< 2000` as null.
- App timezone is **UTC** (month windows). Do not change without billing.

`charge_date` **is** the next payment date already (client, 2026-08-18). Do not add a month. Fallback only: `ConnectedTariff::nextChargeDate()` from `/tariff/connected`.`date_begin` (anchor day of month, `addMonthNoOverflow`, first charge is a month after start). Never derive from `contract_date`.

Match tariffs by **`tariff_id`**, never name (trailing spaces). Trim `tariff_name` for display. Strip `" - 125 000 сум"` suffix only when it matches the trusted cost (`currentTariffDisplayName()`).

Home device count: `count(/device/list)`, never `/abonent/info`.`device_count`.

Payment status is free text in the request `lang`. Tone via `BillingHistory::paymentTone()` (fold Uzbek apostrophes). Credit: `note` contains `кредита` (`isCreditNote`). Last payment on home: `lastRealPayment()` pairs later negative amounts as reversals.

Default periods: finance = current calendar month; traffic = last calendar month through today.

## Routes that spend money or attach hardware

POST + CSRF + FormRequest. Never GET.

- `tariff.connect` — also: not legal entity; permanent **or** no current tariff; id in **this** account’s `/tariff/available` **and** `TariffVisibility` allow-list. Timing `now` vs `month`; non-permanent forced to `now`.
- `devices.add` / `devices.delete` — `isPermanent()` or 403. Permit ids are account-scoped at the API.
- `topup.store` — `config('iwon.active')` or 404. Amount 1_000…50_000_000 soʻm. Validation failures always redirect to `route('topup')` (form also lives in a home/payments modal).

iWon is a **unsigned browser GET**, no callback. After payment iWon sends the subscriber to cabinet home (`returnUrl`); this app does not confirm the credit — billing updates saldo on its own. Log `additional_id` on initiate.

`/tariffs` and `connect()` must use the same `TariffVisibility::filter()` — opt-in SQLite `enabled_tariffs`. A hidden id must not connect via a crafted POST.

## SOLA client behaviour

Success = HTTP 200. Failure = 400 + `code`/`errMsg` → `SolaResponse::failed()`; caller flashes `ErrorMessages::for($code)` or `errMsg`. Unknown codes → `errors.unknown`, never a fake “Success”.

Transport down → `SolaUnavailableException` → global 503 (`bootstrap/app.php`). Do not try/catch every call.

Retries: connection drops on **idempotent reads only**. Mutating posts: `idempotent: false`. Laravel `retry(throw: false)` so a 400 business error is not a 500.

Production TLS terminates at SOLA’s gateway; trust `X-Forwarded-*` so generated URLs stay HTTPS.

## Commands

```bash
docker compose up -d --build
docker compose exec -u www-data php composer install   # always -u www-data
npm ci && npm run build   # host; Docker has no Node; public/build gitignored

php artisan test
./vendor/bin/pint --test
```

Local off-VPN: `SOLA_FAKE=true` (`APP_ENV=local` only) — `FakeSolaServer` on the HTTP factory, client still signs. SMS skip vs live API: `SOLA_FAKE_LOGIN=true`. Unknown fake phone `998900000000`; wrong SMS `0000`.

Docker network **must** stay `10.123.0.0/24` so the container does not ARP-swallow `172.19.x` billing.

CI: `.github/workflows/deploy.yml` — install, Vite build, sqlite migrate, `php artisan test`, SSH `scripts/deploy.sh` from `main`.

## Tests

PHPUnit 12, `#[Test]`. Feature tests: `Http::preventStrayRequests()` + `Http::fake()` with `*/abonent/info` style keys. Verified user = cookies (`verify`, `account`, `login`, `phone`, `data`) — no User model. Copy `CabinetTest::fakeSola()` / `verifiedSubscriber()`. DB writes to allow-list/admins: `DatabaseTransactions`. Freeze time with `Carbon::setTestNow()` and clear in `tearDown`.

## Frontend

Layout `layouts/app.blade.php`. CSRF meta required (`ajax.js` period filters). View composer injects `abonentType` and `isPermanent`. Copy in **ru, uz, and en** together. Promo/loyalty/chat/top-up cards stay hidden until config is set. Tailwind 4 + Vite on the host.

## Do not

- Invent next tariff / contract number / charge date. Missing → hide or honest empty, not `—` unless the template already does that for that field.
- Mix soʻm and tiyin. `/abonent/info.saldo` and `tariff_price` are soʻm.
- Use `/abonent/info.name` as the header FIO.
- Compare `abonType === 2` or match tariffs by name.
- Add `lang` to `/tariff/connect`.
- Change money routes back to GET. Enable `IWON_ACTIVE` without a live service id / `acc_id`.
- Commit `.env`, `database/database.sqlite`, `public/build`.
- `docker compose exec` as root. Move Docker subnet into `172.16.0.0/12`.
- Change UTC timezone or cookie names without an explicit migration plan.
