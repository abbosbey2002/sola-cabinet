# Header/menu/home-page fixes from client TZ feedback doc

- **Date:** 2026-08-28 13:11
- **Source:** Client feedback doc (Google Doc, 6 numbered points, Uzbek). Split into independent tasks per point; each investigated against the real codebase before any change (several points turned out to already be resolved, misdescribed, or blocked on missing billing-API fields — see Decisions).

- **Scope:**
  - `resources/views/cabinet/index.blade.php` — `$cost` computation split from `$currentCost`
  - `resources/views/components/account-menu.blade.php` — collapsed trigger label
  - `resources/views/partials/topbar.blade.php` — identity `<dl>` breakpoint, mobile drawer order, speedtest nav item removed (separate earlier request in this session)
  - `tests/Feature/CabinetTest.php` — 3 new tests

- **Decisions (by TZ point):**
  1. **"TEST PAYMENTS kerak emas"** — investigated, no such feature/button exists anywhere in the app. "TEST PAYMENTS" (l/s 1336708) is the real account name of the QA team's live-billing test account (see `docs/task/QA_CHECKLIST.md:19`, `docs/forge-qa-browser/2026-08-27_17-02_tz-v2-fixes-verification.md`). Client confirmed it was seen "in the header, on large screens" — i.e. this is the same element as point 4, not a separate bug. No separate code change; resolved by point 4's fix.
  2. **Header account-menu "Personal account" → name.** `account-menu.blade.php`'s collapsed trigger button showed the label `app.accounts.personal` ("Personal account"/"Hisob raqam") above the account number. Now shows the subscriber's name (`$name`, from the `full_name` cookie, already used elsewhere in this component) above the account number, truncated at `max-w-[9rem]`.
  3. **"Next charge summ kerak... joriy tarif yoki next tarif narxi bo'ladi."** The amount was never removed (still in `index.blade.php`); client clarified the real ask: the home page's "next charge" figure and balance verdict must reflect whichever tariff will actually be billed — the queued tariff's price when a switch is pending, else the current tariff's price. Fixed: `$cost` now checks `$profile->nextTariff() !== null` (a real pending switch) before trusting `nextTariffCost()`, falling back to `currentTariffCost()` otherwise — gating added after the security-auditor flagged that trusting a stray `next_tariff_cost` with no queued tariff name could show a wrong verdict. A separate `$currentCost` (always `currentTariffCost()`) keeps the "current tariff" summary card showing that tariff's own price, unaffected. The last-payment card itself needed no change — already fixed in prior commits (`f3072da`, `9462db6`).
  4. **Header name/contract number invisible.** `<dl class="u-id-facts ...">` was gated `2xl:flex` (≥1536px) — invisible on nearly all real screens. Changed to `xl:flex` (≥1280px): covers the large majority of real desktop/laptop widths (1366, 1440, 1512, 1728, 1920+) while avoiding the 1024–1280px range the code-reviewer flagged as already crowded at that row (phone number, view-settings, lang-switch, account-menu all join from `sm`/`lg`) — chose `xl` over the initially-considered `lg` specifically to stay out of that untested crowded range. Truncation (`min-w-0` + `truncate` on both fields) already guards against overflow either way.
  5. **Home page "looks like an input range."** Confirmed: `<x-day-meter>` is intentionally slider-shaped. Client wants a full redesign in a separate session, direction given: something closer to a calendar/deadline ("muddat") representation, not a slider. **Not started** — needs its own `forge-frontend-design` session with mockups for the client to pick from before implementing.
  6. **Mobile drawer order.** Was: logo → identity `<dl>` → account switcher → nav menu → language → call center. Reordered so the nav menu (the actual navigation) comes right after the logo/close row; identity, account switcher, language and call-center numbers all moved below it. The `flex-1` that used to live on `<nav>` (to push language/call to the bottom) moved to a dedicated empty spacer `<div class="flex-1">` in the same relative position, since `<nav>` no longer needs to stretch.

- **Pipeline results:**
  - `forge-code-reviewer`: **APPROVE**. One warning (breakpoint crowding risk, addressed by switching `lg` → `xl`), one nitpick (an unrequested `border-b-2` on the relocated `<nav>` — left as-is, cosmetic and consistent with the drawer's existing section-border style).
  - `forge-security-auditor` (RISK=true, money-display logic): **SHIP**. One Medium finding (ungated `nextTariffCost()` trust) — fixed, with a new regression test.
  - Full suite: 143 passed / 512 assertions.
  - Attempted live-browser verification of the `xl` breakpoint at 1024–1280px in the running app (`SOLA_FAKE=true php artisan serve`); abandoned after repeated screenshot/input tool failures in the browser session (not a code issue — Chrome extension tooling was unresponsive). The `xl` choice stands on the static crowding analysis above; a manual check by the client/QA on a ~1280px-wide screen would still be worth doing.

- **Risks flagged:** None outstanding.

- **Left for later:**
  - Point 5 (home page redesign) — needs a `forge-frontend-design` session.
  - Manual visual confirmation of the `xl` header breakpoint on a real ~1280–1366px screen (browser-tool verification attempt failed, see above).

- **User must do:** Nothing — no migrations, env vars, or deploy steps beyond normal deploy.
