# SOLA /acct/payments pay_begin/pay_end sent a 2-digit year

- **Symptom:** User report: "payments so'rovda ... yil to'liq emas, faqat
  oxiri ketyabdi 22 bo'lib 2022 bo'lishi kerak" — the year in the outgoing
  `pay_begin`/`pay_end` params was 2 digits instead of 4.

- **Root cause:** `Period::paymentsStart()`/`paymentsEnd()`
  (`app/Support/Period.php`) formatted the date with PHP's lowercase `y`
  (2-digit year) instead of `Y` (4-digit). This was a deliberate change made
  2 days prior (2026-08-25, see
  `docs/forge-debugger/2026-08-25_15-10_sola-payments-date-format.md`),
  itself a correction of an earlier unverified `YYYY-MM-DD` guess from
  2026-08-19 — both prior changes were made from a client description of the
  live server's behavior, neither independently probed by the agent doing
  the work. The 2026-08-25 "confirmation" turned out wrong.

- **Evidence chain:** Traced every place a year could render short: grepped
  all Blade views and `resources/js/` for date formatting — every UI display
  (`payment/result.blade.php:77`, `period-form.blade.php`) already used a
  4-digit year, ruling out a display bug. The only 2-digit year in the
  request/response path was `Period::paymentsStart()`/`paymentsEnd()`
  (`d.m.y`), consumed solely by `SolaClient::payments()` as
  `pay_begin`/`pay_end`. Confirmed with the user this is expected to be
  4-digit before changing a payment-API integration that carried an explicit
  "confirmed against live server" note from 2 days earlier — asked for the
  source (fresh live check vs. re-guess); user restated the requirement
  directly rather than citing a fresh live probe.

- **Fix:**
  - `app/Support/Period.php` — `paymentsStart()`/`paymentsEnd()` now format
    `'d.m.Y'` instead of `'d.m.y'`.
  - `app/Services/Sola/SolaClient.php` — docblock updated (`payments()`
    itself just forwards the strings, unchanged).
  - `app/Services/Sola/FakeSolaServer.php` — `fromPayDate()` regex now
    matches a 4-digit year group and uses it directly, dropping the
    `'20'.$matches[3]` century-splice the prior security audit had flagged
    as a non-blocking footgun (breaks in year 2100) — that code path no
    longer exists.
  - `tests/Unit/FakeSolaServerTest.php`,
    `tests/Unit/BillingHistoryPaymentsTest.php` — updated fixtures/assertions
    from 2-digit to 4-digit year.
  - `docs/api/SOLA_API_REFERENCE.md` §7 and its endpoint-table footnote —
    corrected, recording both prior wrong guesses and today's fix.

- **Regression test:** `FakeSolaServerTest::payments_are_read_from_the_dmy_pay_range`
  and `BillingHistoryPaymentsTest::a_multi_month_period_makes_exactly_one_request`
  now assert the 4-digit shape; both were already the tests pinning this
  format, just updated in place rather than added new (same test, corrected
  expectation — the format itself is what changed, not the coverage).

- **Same-pattern risks:** Grepped for any other `format('d.m.y')` (or
  similar lowercase-year format call) — none found; this was the only
  consumer.

- **Not independently verified live:** As with the 2026-08-25 fix, this
  session did not itself fire a request at the live SOLA server — the
  change was made on the user's direct, confirmed correction. If this turns
  out wrong again, the next investigation should get an actual live probe
  before trusting either the client's or an engineer's memory of the format
  a third time.

## Pipeline

- Full test suite: 120 passed (507 assertions) before and after, no
  regressions.
- `forge-code-reviewer` + `forge-security-auditor` pending (RISK=true —
  payment API integration).
