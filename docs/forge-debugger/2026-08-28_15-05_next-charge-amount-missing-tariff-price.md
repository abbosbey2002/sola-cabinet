# "Next charge" amount missing — real field is tariff_price, not curr_tariff_cost

- **Date:** 2026-08-28 15:05
- **Scope:**
  - `app/Support/AbonentProfile.php` — `currentTariffCost()` fix + disagreement logging
  - `tests/Feature/CabinetTest.php` — regression test with real captured fixture
  - `docs/api/SOLA_API.md` — documented the confirmed field

## Reproduce → evidence → fix

User report: "next charge summ nega chiqmadi" (why isn't the next-charge amount
showing) on production. Local repro with `SOLA_FAKE=true` showed the amount
displaying correctly (the fake seeds `curr_tariff_cost`), so the gap had to be
in real billing's response shape for the affected account, not in the app
logic itself as written.

User enabled the VPN this session, which gave direct network access to the
real SOLA API (`172.19.1.201:808`). Queried `/abonent/info` for the reported
account (1336708, "TEST PAYMENTS" — a documented QA test account, see
`docs/task/QA_CHECKLIST.md:19`) via the app's own `SolaClient` (correct
auth/signing, no guessing at the wire format):

```json
{
  "curr_tariff_name": "Smart 50 - 125 000 сум",
  "tariff_price": "125000",
  "saldo": "0",
  "charge_date": "2026-09-24",
  ...
}
```

Root cause: the real price field is `tariff_price`, which was never in
`AbonentProfile::CANDIDATE_CURRENT_TARIFF_COST` (`curr_tariff_cost`,
`tariff_cost`, `curr_tariff_price`, `abon_cost` — none present on this
account) — so `currentTariffCost()` returned null and the view correctly (by
design) showed only the date, no invented amount.

Second finding, not obvious from the field name alone: `tariff_price` is
already in **so'm**, not tiyin like every other candidate. Confirmed by
cross-referencing the tariff's own name in the same response — at tiyin,
`"125000"` would be 1 250 so'm, contradicting "125 000 сум" in
`curr_tariff_name`. Verified the fix against the live account directly
(`AbonentProfile::from($realResponse)->currentTariffCost()` → `125000.0`,
correct) before writing any test.

## Decisions

`currentTariffCost()` now reads `tariff_price` directly (no `/100`) before
falling back to the old tiyin-based candidate list — `nextTariffCost()` was
NOT touched, since nothing in the captured response suggested an analogous
`next_tariff_price` field; adding one would have been a guess, not a finding.

Added `Log::warning()` when `tariff_price` and a legacy candidate are both
present and disagree — per the security auditor's finding: preferring the
confirmed field silently (the way this method now does) means a future
account where the two genuinely disagree would ship a wrong verdict with no
signal. `nextChargeDate()` two methods up in the same file already has this
exact asymmetry-with-a-reason documented (confirmed field checked last, on
purpose, to catch exactly this kind of disagreement) — this fix's shape is
the mirror image (confirmed field checked first, since it's the only one
observed live), so the logging keeps both methods honest the same way.

Updated `docs/api/SOLA_API.md` §3 (`/abonent/info`) with the confirmed
`tariff_price` field, its so'm unit, and the reasoning — so the next engineer
chasing a similar report on a different account finds the answer already
recorded instead of re-doing the VPN investigation from scratch.

## Pipeline results

- `forge-code-reviewer`: **APPROVE**. One warning (SOLA_API.md was stale) —
  fixed. One nitpick (unflagged asymmetry vs. `nextChargeDate()`'s pattern) —
  fixed via the logging above.
- `forge-security-auditor`: **SHIP**. One Medium finding (silent disagreement
  between `tariff_price` and a legacy field could ship a wrong amount on a
  different account, though the failure direction is conservative — it can
  only push the verdict toward a false "you're short" warning, never hide a
  real shortfall) — fixed via the logging above. Confirmed the test fixture's
  use of the real QA test account's data is established project precedent
  (already hardcoded elsewhere pre-existing this change), not a new PII leak.
  Confirmed no debugging-session residue (curl dumps, account data) was left
  in logs or the working tree — `storage/logs/` is git-ignored and grepped
  clean.
- Full suite: 145 passed / 514 assertions.

## Risks flagged

None outstanding. The one real risk (silent unit/field disagreement on a
different account) now logs a warning instead of shipping silently wrong.

## Left for later

None specific to this fix. `nextTariffCost()`'s candidate list remains
unverified against a live account with a queued tariff switch — worth the
same VPN-verification treatment if/when a similar report comes in for that
figure specifically.

## User must do

Nothing beyond deploying the changed files — no migration, no env var, no
config change. Worth keeping an eye on the new `AbonentProfile: tariff_price
disagrees with a legacy tariff-cost field` log line for the first few days
after deploy, in case another account surfaces the disagreement this fix
now watches for.
