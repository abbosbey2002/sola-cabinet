# Subscriber's display name now comes from /identify, not /abonent/info

- **Date:** 2026-08-28 16:41
- **SCOPE:** backend · **RISK:** false (display-only value, no auth/session-validity logic touched) · **SIZE:** one employee (3 files)
- **Files changed:**
  - `app/Http/Controllers/Auth/AuthController.php`
  - `tests/Feature/AuthTest.php`
  - `docs/api/SOLA_API.md`

## What was reported

User sent a screenshot of the real running instance (`http://127.0.0.1:8080`,
account 1000033): the header's account-switcher trigger and the
`data-disclosure-panel` header both showed **"Разовый абонент"** as the
subscriber's own name.

## First pass — corrected mid-session

The first fix (superseded by this note, not shipped) kept reading
`/abonent/info`'s `name` field and added a denylist comparing it against
the three known account-type labels, falling back to `login` on a match.
The user corrected the approach directly: **don't read `name` from
`/abonent/info` at all — use `/identify`'s own `abonName`, falling back to
`login`, exactly like `x-account-menu`'s "switch account" list already
does.** That is what shipped; the denylist approach was removed entirely,
not layered underneath it.

## Why the corrected approach is better, not just different

- `/identify`'s `accs[]` already carries `abonName` and `login` per
  account (`docs/api/SOLA_API.md` §1) — the exact same row `selectAccount()`
  already reads `accId`/`abonType`/`login` from. No extra API call needed.
- `/abonent/info`'s `name` is billing's own free text for a different
  purpose (the account-detail card) and was observed returning a generic
  type label instead of null or a real name — unreliable as a display
  name by nature, not just in the one case a denylist could patch over.
  `abonName` is documented as "often blank" (never observed to contain
  type-label garbage), which is a materially different, safer failure
  mode: blank is easy to detect and fall back from; a plausible-looking
  wrong string is not.
- Consequence: `AuthController::switchAccount()` no longer calls
  `$this->sola->abonentInfo($accountId)` at all — that request existed
  only to feed `name` into the (now-removed) old logic. One fewer external
  API call per account switch.
- `resolveFullName()` moved from taking `(name, account)` to taking just
  `account`, and the call moved from `switchAccount()` into the shared
  `selectAccount()` helper — which `login()`'s single-account auto-select
  path already calls too. That path previously never set `full_name` at
  all (a gap noted, out of scope, in the first pass of this fix); it now
  does, for free, because the fix lives where both callers already are.

## Fix

```php
private function resolveFullName(array $account): string
{
    $abonName = (string) ($account['abonName'] ?? '');

    return $abonName !== '' ? $abonName : (string) ($account['login'] ?? '');
}
```

Called from `selectAccount()`, which both `login()` (single-account path)
and `switchAccount()` already call.

## Documentation

`docs/api/SOLA_API.md` §3's `name` row now states plainly that the cabinet
does not source the display name from this field at all, and points at
`abonName`/`login` in §1 instead — so a future reader doesn't wonder why
`name` looks unused.

## Tests

`AuthTest.php`:

- `a_known_phone_is_sent_an_sms_and_lands_on_the_verify_screen` (existing,
  single-account login path) — added a `full_name` cookie assertion,
  closing the gap noted above.
- `switching_account_falls_back_to_login_when_billings_own_name_is_blank`
  (rewritten from the first pass) — `abonName` blank, `login` populated;
  no `/abonent/info` fake needed any more since the code no longer calls it.
- `switching_account_uses_the_account_own_name` (rewritten) — `abonName`
  populated, passes through unchanged.

`php artisan test` — 147 passed (524 assertions).

## User must do

Nothing — no migration, no env var, no config change, no asset rebuild.
