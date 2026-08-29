# Drop the account-type badge fallback in the account switcher

- **Date:** 2026-08-28 16:24
- **Mode:** SaaS (kabinet dashboard) — trims a fallback chain, no new markup shape.
- **Screens/components delivered:**
  - `resources/views/components/account-menu.blade.php` — `data-disclosure-panel`, "switch account" list

## Decisions

User ask: "data-disclosure-panel da РАЗОВЫЙ АБОНЕНТ degan so'z kerak
emas. uni o'rniga name agar bo'lmasa login turishi kerak." — the
account-type text ("Разовый аккаунт" / one-time-account, from
`x-account-type :as="text"`, driven by `abonType`) showing in the
disclosure panel isn't wanted; name should fall back to login instead.

The fallback chain in the "switch account" list (desktop dropdown) was
`abonName ?: login ?: <x-account-type as="text">` — added the same day,
earlier in this session, specifically so two blank-named accounts could
still be told apart. Trimmed it to `abonName ?: login`: the type badge
("Временный"/"Разовый"/"Постоянный") tells a subscriber what *kind* of
account it is, not *which* one — it's not an identifier, so as a name
fallback it was noise rather than help. Left the case of both fields blank
unhandled (title reads empty, the accId still shows on the line below) —
narrower than what was asked, and per the existing code comment login is
already the *reliable* field in this same API row, so this is a
theoretical edge case, not an observed one.

`x-account-type` itself is untouched and still in use elsewhere
(`resources/views/auth/select_account.blade.php`'s account-selection
screen, where "what kind of account is this" is exactly the relevant
question) — only this one fallback use was removed.

## Quality floor result

- Verified in a real Chrome browser against the running instance
  (`http://127.0.0.1:8080`, temporary static file under `public/`,
  deleted after use): a row with a login renders the login as its title,
  no badge; a row with both fields blank degrades to just the accId
  underneath an empty title line — no broken layout, no leftover badge.
- `php artisan test` — 145 passed (514 assertions), including
  `the_account_switcher_shows_login_when_billings_name_is_blank`, which
  exercises this exact fallback and was unaffected (it never reached the
  now-removed branch).

## Pipeline results

- `forge-code-reviewer`: not run — RISK=false, three-branch conditional
  collapsed to a ternary, no new logic; covered by the full green suite
  and direct browser verification.
- `forge-security-auditor`: not run — RISK=false, display-only, no new
  data path (accId/login were already rendered on the same row).

## Left for later

The "both abonName and login blank" edge case has no fallback beyond the
accId shown below it. Not fixed here — the user's instruction named only
two fields (name, login), and per the existing docs/comment this case is
believed not to occur in real billing traffic. Revisit if it ever does.

## User must do

Nothing — no migration, no env var, no config change, no asset rebuild
required beyond the normal Vite build already in the deploy pipeline.
