# Client login page — Uzbek-only phone mask

**Request:** "@login pageda client qismida telefonga maska qo'ying faqat uzb nomerlar uchun"

**Scope:** frontend, RISK=false (client-side input formatting only; `LoginRequest`
already strips `+`/spaces and accepts up to 20 chars, so server behavior is
unchanged), single employee.

## What changed
- `resources/js/modules/phone-mask.js` (new) — formats the login field as
  `+998 XX XXX XX XX` while typing, locks the `998` country code so no other
  country's number fits through the field, and normalizes pasted numbers
  regardless of whether they carry `+`, `998`, punctuation, or spaces.
- `resources/js/app.js` — registered `initPhoneMask()` in `boot()`, following
  the existing delegation/no-op module pattern.
- `resources/views/auth/login.blade.php` — added `data-phone-mask` to the
  `login` input (client-facing page only; admin login untouched).

## Pipeline
- Implemented, then hand-verified the mask logic against typing, mid-prefix
  backspacing, and paste-with/without-country-code scenarios via a standalone
  Node script (all cases passed) — no separate debugger pass needed for a
  change this contained.
- `forge-code-reviewer`: first pass found a real blocker — an initial
  `maxlength="17"` on the input truncated pasted numbers containing
  punctuation (e.g. `"+998 (90) 123-45-67"`) at the raw-character level
  before the JS mask could normalize them, silently dropping trailing
  digits. Fixed by removing `maxlength` entirely (the JS already hard-caps
  at 9 subscriber digits; server-side `max:20` remains the no-JS fallback).
  Re-verified: build clean, no other regressions.
- `forge-security-auditor`: skipped, RISK=false.

## Known trade-off (not a blocker)
The field re-snaps the caret to the end on every `input` and `focus` event,
so it's effectively append-only — a subscriber can't click into the middle
of an already-typed number to fix one digit, only delete from the end.
Flagged by review as an acceptable simplification for a 17-character field;
not changed.
