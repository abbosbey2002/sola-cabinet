# Traffic/Payment date-range cap removed

**Request:** "unday chegarani olib tashlang kerak emas" — remove the 12-month
cap on how wide a date range a subscriber can request on the Traffic and
Payment pages.

**Scope:** backend, RISK=true (external API / payment data, same subsystem as
the same-day SOLA date-format fix).

## Decision trail (important — read before touching this again)

1. Explained the tradeoff before implementing: Traffic makes one HTTP call
   per month in the range (no range endpoint on `/traffic/detail`), so an
   unbounded range means unbounded sequential calls — framed at the time as
   "the traffic page could be slow for whoever asks for a huge range." User
   confirmed: remove it completely, on both pages.
2. Implemented the removal (`Period::MAX_MONTHS`, the clamp in `between()`,
   `wasClamped()`, the `clamped` UI note, and the `dash.clamped` translation
   all removed — see below for the full list).
3. **`forge-security-auditor` returned FIX CRITICAL+HIGH FIRST**, and
   correctly identified that the risk is sharper than what was described in
   step 1: with no throttle on `POST /statistics`/`POST /finance` and no
   floor on the requested start date, one authenticated subscriber
   requesting e.g. `start=1970-01-01` can tie up a single PHP-FPM worker for
   hours (hundreds of sequential SOLA calls, each up to ~30s with retries),
   and a few browser tabs from the same session can tie up several workers
   at once — **exhausting the shared worker pool for every other
   subscriber**, not just slowing the requester's own page. It also flagged
   that a burst of that many calls could push SOLA's own error-logging
   queue past its documented silent-failure threshold
   (`docs/api/SOLA_API_REFERENCE.md`, `msg_qnum >= 100`), degrading error
   visibility for the whole billing system, not just this feature.
4. Went back to the user with the corrected, sharper framing (shared
   worker-pool exhaustion / SOLA gateway degradation for *other*
   subscribers, not self-inconvenience) and three options: raise to a
   generous cap (36–60 months, recommended), restore the 12-month cap, or
   remove it anyway. **User chose to remove it anyway, fully informed.**
   `forge-code-reviewer` independently returned APPROVE on the same diff
   (with one non-blocking "should fix" suggesting a sanity floor like
   `after_or_equal:2015-01-01` on the date input, distinct from a range-width
   cap — not applied, since the user's explicit choice was to remove
   restriction, not add a different one back).

**This is a knowingly-accepted risk on the user's own infrastructure, not an
oversight.** If this ever needs revisiting (e.g. after an actual worker
exhaustion incident), the fix the security audit recommended was: a bound on
the number of months `BillingHistory::traffic()` will actually walk,
independent of what the raw requested range says, and/or `throttle`
middleware on `traffic.filter`/`payment.filter`.

## What changed

- `app/Support/Period.php` — removed `MAX_MONTHS`, the clamp in `between()`,
  and `wasClamped()`.
- **Separate bug found and fixed in the same pass**: the earlier SOLA
  date-format fix had wrongly repurposed `Period::startInput()`/`endInput()`
  (used by `period-form.blade.php` to populate an HTML5 `<input type="date">`
  — needs `Y-m-d`) to return the SOLA-specific `d.m.y` format instead,
  silently breaking the date picker (an invalid HTML5 date value is just
  ignored by the browser). No test caught this. Fixed by reverting
  `startInput()`/`endInput()` to `Y-m-d` and adding dedicated
  `paymentsStart()`/`paymentsEnd()` (`d.m.y`) used only by
  `BillingHistory::payments()`. Added a permanent regression test,
  `CabinetTest::the_period_picker_carries_an_html5_date_value`, specifically
  so this exact class of bug (two callers, two formats, one pair of methods)
  can't silently recur.
- `TrafficController`/`PaymentController` — removed `wasClamped()` wiring.
- `PeriodRequest::requestedStart()` — removed, now-unused.
- `components/period-note.blade.php` — removed the `clamped` prop/branch;
  `incomplete` (a failed month, unrelated concern) untouched.
- Four blade files (`payment/index`, `payment/result`, `trafic/index`,
  `trafic/result`) — removed all `clamped`/`:clamped` wiring.
- `dash.clamped` translation key removed from uz/ru/en (confirmed unused
  elsewhere first).
- `tests/Feature/CabinetTest.php` — rewrote the old
  `an_over_long_range_is_clamped_instead_of_hammering_billing` into
  `a_long_range_is_honoured_in_full_instead_of_clamped` (asserts one HTTP
  call per actual month in a >12-month range, not a capped count).

## Pipeline

- Full suite green throughout (120 tests, up from 118 at session start).
- `forge-code-reviewer`: APPROVE.
- `forge-security-auditor`: FIX CRITICAL+HIGH FIRST → user informed,
  explicitly overrode after the sharper risk was laid out. Shipping as the
  user's knowing choice, documented here for whoever reads this next.
