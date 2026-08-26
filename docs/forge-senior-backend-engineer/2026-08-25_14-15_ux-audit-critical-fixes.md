# Cabinet UX audit — critical fixes

**Request:** implement the 3 CRITICAL findings from the 9-page UX audit
(artifact: "Cabinet UX Audit"), scope confirmed by the user as "3 criticals
only" out of 3 critical / 13 major / 12 minor total findings.

**Scope:** frontend, RISK=false (display/UX only — no new money-moving logic,
no auth changes; the price shown in the device confirm dialog and the
contract number in the pay-card both surface data the page already had).

## What changed

1. **Dashboard + Payment — "how to pay" visibility.** New reusable
   `resources/views/components/pay-card.blade.php`: contract number in large
   type + one-tap copy-to-clipboard, replacing inert muted-text sentences on
   both `cabinet/index.blade.php` (shown only for `low`/`negative` balance
   states) and `payment/index.blade.php` (shown unconditionally above the
   history table instead of below it). New `resources/js/modules/copy.js`
   (Clipboard API + execCommand fallback, delegation-based like every other
   module here), new `copy` icon, removed two now-dead translation keys and
   added three new ones across uz/ru/en.
2. **Devices — price in the confirm dialog.** The "Add device" button's
   `data-confirm` text now interpolates the actual cost when known, instead
   of a generic question with the price shown only in an unrelated paragraph
   elsewhere on the page.
3. **Services — empty-state fallback.** The page can no longer render as a
   dead end when every card AND the call-center number are unconfigured.

## Pipeline

- Implemented, then self-verified against the real running app (not just
  `php -l`): ran the full existing suite (118 tests) after every change,
  and wrote throwaway scratch feature tests (since deleted — not part of
  this diff) to exercise each new code path through real HTTP requests:
  dashboard with a negative balance, payment page with a contract number,
  services page with everything unconfigured.
- **Incidental discovery, not fixed (out of scope):** while testing the
  services empty-state with `speedtest_url` nulled out, hit a pre-existing,
  unrelated bug in `resources/views/partials/topbar.blade.php` — the
  speedtest nav item has no `'route'` key, so
  `$item['url'] ?? route($item['route'])` throws if that config is ever
  explicitly set to `null` (it has a non-null default, so this doesn't fire
  today). Worked around it in the scratch test with `''` instead of `null`.
  Flagging for a future task, not fixing here.
- `forge-code-reviewer`: first pass returned **FIX BLOCKERS FIRST** with a
  real regression — the pay-card's dashboard condition (`$state !== 'ok'`)
  also matched `$state === null` (no tariff cost to judge the balance
  against — a real, expected case per `AbonentProfile::missingSpecFields()`),
  which would have told a subscriber with an *unknown*, possibly-fine
  balance to go pay. Fixed by narrowing the condition to
  `in_array($state, ['low', 'negative'], true)`. Also fixed a warning: the
  tone lookup in `pay-card.blade.php` referenced its own variable name on
  the right-hand side of its assignment (`$tones = [...][$tone] ?? $tones['action']`)
  — harmless today since every call site passes a valid tone, but a live
  trap for the next one; renamed the literal to `$toneMap`. Applied the
  accessibility nitpick too (`role="status"` on the copy button's label so
  the "Copied" swap is announced).
- Re-verified after fixes: added back the negative-balance and
  no-verdict-state scratch tests (the second one specifically targeting the
  regression), both passed, full suite still green (118/118), then deleted
  the scratch file again.
- `forge-security-auditor`: skipped, RISK=false.

## Not done (explicitly deferred, per user's stated scope)

The 13 MAJOR and 12 MINOR findings from the same audit — auth-flow JS
friction (login mask caret, OTP resend/auto-submit), tariff page false
affordance, devices touch-target verification, traffic mobile sort, and
others — are documented in the audit artifact but not implemented.
