# `charge_date` is the NEXT payment date, not the last one

- **Date:** 2026-08-18 17:04
- **Scope:** backend · **RISK:** false — read-only display fix, no money movement
- **Result:** `php artisan test` → **112 passed (461 assertions)**

## What happened

Same day as the previous session's "legal-entity tariff gate" work
(`2026-08-18_15-24_legal-entity-tariff-gate.md`), which built
`AbonentProfile::nextChargeDate()` on the premise that `/abonent/info`'s
`charge_date` is the LAST charge, deriving the next one via +1 month
(individual) or end-of-month (legal entity). The client corrected this the
same day: `charge_date` **already is** the upcoming payment date — billing
has done whatever individual/legal-entity math it does on its own side before
sending it. No arithmetic belongs on the cabinet's side at all.

## Changes

- `app/Support/AbonentProfile.php` — `nextChargeDate()`'s private helper no
  longer branches on `isLegalEntity()` or does month arithmetic; it just
  returns `$this->date('charge_date')`. Comments rewritten to record both the
  wrong premise and the correction, so a future reader does not reintroduce
  the arithmetic from the git history.
- `app/Http/Controllers/CabinetController.php` — comment updated.
- `app/Services/Sola/FakeSolaServer.php` — `charge_date` fake value moved from
  "start of this month" (a plausible last-charge) to "start of next month" (a
  plausible upcoming payment), comment updated.
- `tests/Feature/CabinetTest.php` — the two tests asserting month-later /
  end-of-month derivation replaced with two tests asserting the date passes
  through unchanged for both an individual and a legal entity.
- `docs/api/SOLA_API_REFERENCE.md` — `charge_date` row and §6 gap note
  corrected.

`AbonentProfile::isLegalEntity()` itself is untouched — it still gates the
tariff section (`TariffController`, topbar, dashboard card) from
`2026-08-18_15-24_legal-entity-tariff-gate.md`; that part of the client's
2026-08-18 statement was correct and is unrelated to `charge_date`.

## Left for later

None — this was a same-day field-semantics correction, not new scope. Worth
flagging to the client if `charge_date` is ever seen equal to "today" or in
the past in production, since the whole dashboard meter now assumes it is a
future date.
