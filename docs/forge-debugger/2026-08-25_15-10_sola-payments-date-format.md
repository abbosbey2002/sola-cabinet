# SOLA /acct/payments date format was wrong

**Report:** "http://172.19.1.201:808/acct/payments apida ... sanalar teskari
ketgan kk.mm.yy bo'lishi kerak" — the live billing API rejects/mishandles the
`pay_begin`/`pay_end` dates as sent; they need to be `d.m.y` (day.month.year,
two-digit year, dot-separated), not `YYYY-MM-DD`.

**Root cause:** the `pay_begin`/`pay_end` range (commit `5819d9b`, 2 days
prior) was implemented purely from the client's verbal description of the
*existence* of the range filter — the date format itself (`YYYY-MM-DD`) was
never confirmed against the live server, and the commit message says so
explicitly ("error-code behavior for the new params hasn't been observed
against the live SOLA server"). The format the client described turned out
to be wrong once it actually hit production.

**Scope:** backend, RISK=true (external API, financial/payment data — read
endpoint only, no mutation).

## Fix

- `app/Support/Period.php` — `startInput()`/`endInput()` (used nowhere else,
  exist solely to build this one request) now format `'d.m.y'` instead of
  `'Y-m-d'`.
- `app/Services/Sola/SolaClient.php` — docblock only; `payments()` itself
  just forwards whatever strings it's given, unchanged.
- `app/Services/Sola/FakeSolaServer.php` — the local offline-dev double now
  parses the new format via `fromPayDate()` before reusing the existing
  `Y-m-d`-shaped `daysBetween()`.
- `docs/api/SOLA_API_REFERENCE.md` §7 — corrected, with today's date and the
  prior-wrong-format's provenance, matching the doc's own convention for
  dated corrections.

## Pipeline

- Implemented directly from the user's specific, direct correction — same
  pattern this codebase already uses for undocumented API behavior (no
  written spec exists for this endpoint's date format either way).
- Self-verified against the real running app: full test suite (118 → 119
  after the added test) green throughout. Confirmed via a manual trace and a
  throwaway round-trip test (since made permanent, see below) that
  `fromPayDate('25.08.26')` reassembles to `2026-08-25` with no d/m/y group
  transposition.
- Did **not** independently probe the live SOLA server myself — network
  access to it exists from this environment (VPN tunnel up, confirmed
  reachable), but firing requests at production billing infrastructure
  wasn't something the user asked for, so I took their direct report at face
  value rather than re-verifying it live.
- `forge-code-reviewer`: APPROVE. One suggestion — no existing test exercised
  `fromPayDate()`'s non-empty/happy path (only the "future month → empty"
  case existed, which passes regardless of correct parsing). Added
  `FakeSolaServerTest::payments_are_read_from_the_dmy_pay_range` to close
  that gap permanently.
- `forge-security-auditor`: SHIP. No injection surface (JSON body field, not
  URL/shell/SQL), no credential exposure, fails closed on malformed input.
  Noted the fake server's `'20'.$matches[3]` century assumption as a
  non-blocking correctness footgun (breaks in year 2100) — dev/test-only
  code, not fixed.
