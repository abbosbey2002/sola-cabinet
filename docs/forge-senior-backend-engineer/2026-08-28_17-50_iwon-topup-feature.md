# iWon Business top-up ("Hisobni to'ldirish") — new feature

- **Date:** 2026-08-28 17:50
- **Scope:**
  - `config/iwon.php` (new)
  - `.env` / `.env.example` — new IWON_* vars
  - `app/Support/IwonCheckout.php`, `app/Support/IwonRedirect.php` (new)
  - `app/Http/Requests/TopUpRequest.php` (new)
  - `app/Http/Controllers/TopUpController.php` (new)
  - `routes/web.php` — 3 new routes (`topup`, `topup.store`, `topup.return`)
  - `resources/views/cabinet/topup.blade.php`, `topup-return.blade.php` (new)
  - `resources/views/components/pay-card.blade.php` — iWon button added
  - `lang/{uz,ru,en}/app.php` — new `topup.*` keys
  - `tests/Feature/TopUpTest.php` (new, 15 tests)

## Decisions

User asked to bring an iWon Business payment-gateway integration into this
app, pointing at a reference doc (`docs/api/iwon-api.md`, already in the
repo) that actually described a **different, sibling application**
("sola-premium-project-v.2" / "Sola Portal" — Laravel 9 + MongoDB, a public
hotspot captive-portal that sells tariffs to anonymous users via
Payme/Click/iWon). Investigated first and confirmed *this* app (`sola/
cabinet`) had **no payment-initiation feature of any kind** before this
task — only a read-only payment-history viewer — and no shared code with
that sibling project. Flagged this architecture mismatch to the user before
writing anything, then got explicit direction via clarifying questions:

1. **Amount**: free-form user-entered field (not tied to a specific
   tariff/shortfall).
2. **Placement**: added to the existing low-balance `<x-pay-card>` on the
   Home page (kept the pre-existing manual "type your contract number into
   Payme/Click/Uzum" hint alongside it, since only iWon is actually
   integrated).
3. **iWon credentials**: user confirmed `service_id=883` and
   `account_param=acc_id` (the `transactionParams` key iWon reads as the
   billing account) are correct/approved for this merchant, and asked for
   the feature to launch **active**, not gated off — set in this
   environment's own `.env` (not `.env.example`, which still defaults
   `IWON_ACTIVE=false` for anyone else setting up a fresh install).

**Design, given this app has no database** (pure stateless SOLA-API client,
cookie session only — see `AbonentSession`'s own docblock): iWon's
integration is a plain unsigned browser GET redirect with **no
server-to-server callback of any kind** (confirmed against
`docs/api/iwon-api.md`, the provider's own reference — not a gap in this
app's choice). Confirmation can only ever be "ask billing for the current
balance and compare," the same pattern the docs describe Payme/Click using
elsewhere in the wider Sola product family. A short-lived (30-minute)
encrypted cookie (`pending_topup`) carries `{account_id, balance_before,
amount, initiated_at, additional_id}` across the redirect round-trip — the
only state this stateless app can keep for one payment attempt. `checkReturn()`
polls via a server-rendered `<meta http-equiv="refresh">` (no JS dependency,
consistent with this app's offline-resilience-first design elsewhere) for up
to 5 minutes before switching to a "not confirmed yet, check payment
history" state.

`IwonCheckout::redirectUrl()` converts so'm→tiyin with `round()` (not a bare
`(int)` cast, which can truncate a tiyin on float boundaries — the provider's
own documented gotcha) and generates a digit-only, ≤17-character
`additional_id` (13-digit ms timestamp + 4 random digits) as the only replay
guard this integration has, since iWon never calls back to dedupe on its own.

## Manual verification

Ran the full flow against a local server with `SOLA_FAKE=true` (in-process
fake billing, no real money) via `curl` with an isolated cookie jar: fresh
login → account select → `GET /topup` → `POST /topup`. Confirmed the actual
redirect `Location` header had the exact correct query string (amount in
tiyin, currency, serviceId, correctly urlencoded `transactionParams` and
`returnUrl`), the `pending_topup` cookie had `httponly`/`samesite=lax`/1800s
Max-Age, and `GET /topup/return` correctly showed the "checking" state with
the meta-refresh tag when balance hadn't moved, in the correct (`ru`) locale.

⚠️ **Did not click through to iWon's real hosted form in a live browser.**
Also worth flagging: the Chrome tab used for a visual spot-check already
carried a persistent session cookie for the real production test account
1336708 ("TEST PAYMENTS") from outside this conversation — this is the
user's real, persistent browser profile, not an isolated sandbox. No form
was submitted there; the check was abandoned in favor of the isolated curl
verification above specifically to avoid any ambiguity near a real payment
flow.

## Pipeline results

- `forge-code-reviewer`: **APPROVE**, with one Warning fixed and one
  Warning investigated-but-not-reproduced:
  1. **Fixed**: the pending cookie didn't snapshot `account_id`. If a
     subscriber switched their active account mid-flow (second tab,
     business+personal pair via the account switcher), `checkReturn()`
     would compare the *new* account's fresh balance against the *old*
     account's snapshot — a meaningless comparison that could show a false
     success for the wrong account. Fixed: `account_id` is now part of the
     cookie and required for it to parse as valid; a mismatch against the
     current session account is treated exactly like a missing cookie
     (redirect to `/topup`, "no pending" message). New regression test:
     `the_return_page_ignores_a_pending_cookie_from_a_different_account`.
  2. **Investigated, not reproduced**: reviewer saw 2 flaky
     `assertRedirect()` failures in ~35 full-suite runs, always passing in
     isolation. Ran the full suite 40 more consecutive times (including
     after the account_id fix) — zero failures. Could not reproduce or
     identify a mechanism; the failing test touches no shared mutable state
     (no `Carbon::setTestNow()`, no config leakage across tests I could
     find). Not fixed because there's no concrete evidence to act on —
     flagged to the user rather than guessed at.
- `forge-security-auditor`: **SHIP**, with one Medium finding, fixed:
  no server-side record of top-up attempts existed anywhere — with no
  database and no callback from iWon, the `pending_topup` cookie was the
  *only* possible audit trail, and it vanishes in 30 minutes. Fixed:
  `Log::info('iwon.topup.initiated', ...)` in `store()` and
  `Log::info('iwon.topup.checked', ...)` in `checkReturn()` (only when the
  result is final — credited or timed out), both keyed on `additional_id`
  so support can cross-reference iWon's own back office. Required
  `IwonCheckout::redirectUrl()` to change its return type from `string` to
  a new `IwonRedirect` DTO (`{url, additionalId}`) so the id could be logged
  without re-parsing it out of the URL. New regression tests:
  `initiating_a_topup_is_logged`, `a_confirmed_topup_is_logged`.
  All other checks (account-targeting can't be spoofed, cookie tamper
  resistance, amount trust, redirect safety, gate consistency, XSS/CSRF,
  no PAN/CVV exposure, `.env` hygiene) verified safe with no changes needed.
- Full suite: 165 passed (up from 150 before this task), 570 assertions.

## Follow-up: the button was invisible on the user's real test account

User checked the real running app (a Docker container, `cabinet_nginx`/
`cabinet_php`, port 8080, bind-mounted to this exact source tree — not the
local `SOLA_FAKE` server used for the initial manual verification above) and
reported not seeing the top-up button at all.

Root cause, found by querying the real production SOLA API directly for the
account in question (1336708, "TEST PAYMENTS") via `docker exec cabinet_php
php artisan tinker`: `AbonentProfile::contractNumber()` returns null for
this account — billing sends `contract_id` but no `contract_number`/
`contract_num`/etc. (none of `AbonentProfile::CANDIDATE_CONTRACT` matched).
This is a **pre-existing** gap, not something the iWon work introduced — but
`cabinet/index.blade.php`'s condition for showing `<x-pay-card>` AT ALL was
`in_array($state, ['low','negative'], true) && filled($contract)`, so the
whole card — including the iWon button nested inside it — never rendered
for this account, despite balance being low (0 vs a 125,000 so'm tariff
cost) and `iwon.active` being true.

Fixed:
- `resources/views/cabinet/index.blade.php` — condition changed to
  `... && (filled($contract) || config('iwon.active'))` — the card shows
  when there's a contract number OR iWon is active, never when neither.
- `resources/views/components/pay-card.blade.php` — the contract-number/
  copy-button block is now wrapped in `@if (filled($contract))`; the iWon
  button (already gated on `config('iwon.active')`) is unaffected either
  way. Also fixed a cosmetic nitpick from review: the button's `mt-4` is
  now conditional on the contract block actually being present above it.
- Two new regression tests: `the_pay_card_offers_iwon_even_without_a_contract_number`,
  `no_pay_card_at_all_without_a_contract_number_when_iwon_is_inactive`.

Verified directly against the real container/account via `tinker` (not just
the test suite) that the new combined condition evaluates `true` for this
exact account. `forge-code-reviewer`: **APPROVE** (one cosmetic nitpick,
fixed as above). Full suite: **167 passed**, 576 assertions.

## Follow-up: amount formatting + new-tab redirect

User asked for two UX refinements: format the amount input so it reads like
a sum "oddiy odam tushunishi kerak" (an ordinary person should understand
it at a glance), and open iWon's payment page in a new tab rather than
navigating the subscriber away from the cabinet mid-payment.

- `resources/views/cabinet/topup.blade.php`'s amount field is now
  `type="text" inputmode="numeric" data-amount-mask` (a native `number`
  input can't display space-formatted digits). New
  `resources/js/modules/amount-mask.js`, mirroring the existing
  `phone-mask.js` pattern, live-formats as the subscriber types
  ("10000" → "10 000"). `TopUpRequest::prepareForValidation()` strips the
  spaces back out before `numeric`/`min`/`max` run, so what's actually
  charged is unaffected either way (locked in by a new test:
  `a_space_formatted_amount_is_accepted_and_parsed_correctly`).
- The form gained `target="_blank"`; a new `resources/js/modules/topup.js`
  swaps the form for a "opened in a new tab, check status here" banner on
  submit. No JS still opens the new tab (native HTML) and the subscriber can
  reach `/topup.return` by hand either way — nothing here is load-bearing
  for the actual payment.

`forge-code-reviewer`: **APPROVE**, three Warnings fixed:
1. `target="_blank"` had no `rel="noopener"` — a reverse-tabnabbing gap
   inconsistent with every other external-target link in this codebase
   (`services.blade.php`, `topbar.blade.php`). Added.
2. `amount-mask.js` unconditionally stripped non-digits from the field on
   page load — on a `old('amount')` redisplay of a genuinely invalid value
   (e.g. "abc", after a "must be numeric" error), this silently collapsed
   the field to *empty*, so the subscriber saw the error message next to a
   blank box with no way to tell what they'd actually typed. Fixed: the
   initial reformat is now skipped unless the redisplayed value is already
   purely digits/spaces; editing the field at all hands control back to the
   normal `input`-time masking.
3. The submit-triggered banner had no `role="status"`, unlike every other
   dynamic status change in this app (`toast.js`, the `data-copy-text`
   pattern used in four other components). Added.

Two optional nitpicks also addressed: `TopUpRequest::prepareForValidation()`
now guards against an array `amount` input before the `(string)` cast
(avoids a noisy PHP warning on a crafted request, still rejected either
way), and a stale comment claiming the form "stays reachable to submit
again" after the banner swap was corrected (it doesn't — only a page reload
or `/topup.return` gets back).

Verified: pure-logic check of the masking regex across edge cases (blank,
invalid, valid-but-low, unspaced, pre-spaced, mixed) confirmed the fix
behaves correctly in every case. Live-browser check (via `javascript_tool`,
no real network request to iWon) confirmed `form.rel === 'noopener'`,
`form.target === '_blank'`, and `banner.getAttribute('role') === 'status'`
are all actually present in the rendered page — not just in source. Full
suite: 168 passed, 578 assertions (unchanged count from before this
follow-up — no new PHP-testable behavior beyond the already-covered
space-stripping case, since the rest is DOM-only). Rebuilt
(`npm run build`) and reconfirmed via `curl` against the live Docker
container (port 8080) that the new build actually reached it.

## Risks flagged

⚠️ **This deployment's `.env` (not `.env.example`) now has `IWON_ACTIVE=true`
with real `IWON_SERVICE_ID=883`/`IWON_ACCOUNT_PARAM=acc_id`** — set per the
user's own explicit confirmation this session, not a default. This makes
real-money top-ups live in this environment as soon as it's the environment
serving traffic. If this local/dev `.env` is not meant to be the production
config, double-check before any deploy that uses it directly.

⚠️ iWon's `transactionParams` account-key name (`acc_id`) was confirmed by
the user as correct for this merchant, but per `docs/api/iwon-api.md`
(iWon's own reference), a wrong key name here means "money moves, balance
never credits" — worth one real end-to-end test with a real card before
trusting this at scale, since nothing in this app can detect that failure
mode on its own (it would just look like `checkReturn()` timing out).

⚠️ The flaky-test finding above (2 failures in ~35 runs, not reproduced in
40 more) — not blocking, but worth keeping an eye on in CI specifically for
this feature, since a merge gate that occasionally red-flags a
money-adjacent feature for an unknown reason deserves more scrutiny than a
shrug if it recurs.

## Left for later

- No dedicated `topup` entry in the top navigation — reachable only via the
  pay-card button, as the user specified. Revisit if usage data shows
  subscribers want it reachable when their balance isn't currently low.
- `POST /topup` has no rate limiting (consistent with every other route in
  this app today, none of which are throttled) — worth reconsidering once
  this is the first route that can trigger an external redirect per
  submission at volume.
- A real end-to-end test with an actual Uzcard/Humo card, per the ⚠️ above.

## User must do

- Confirm `.env`'s `IWON_ACTIVE=true`/`IWON_SERVICE_ID`/`IWON_ACCOUNT_PARAM`
  are the values actually meant to go live, before this environment serves
  real traffic.
- Run one real end-to-end payment to confirm `acc_id` is genuinely the
  right `transactionParams` key iWon reads (only iWon/the user can confirm
  this — it cannot be verified from inside this app).
- No migration — this app has no database.
