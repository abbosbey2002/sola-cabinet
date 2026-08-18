# Admin tariff panel — end-to-end verification

- **Symptom:** none — this is a pre-ship verification pass of a newly built feature (admin login + tariff enable/disable), not a bug report.
- **What was verified (all against a real `php artisan serve` instance, real cookies, real DB):**
  - `route:list --path=admin` — all 5 routes resolve to the right controller actions.
  - Unauthenticated `GET /admin/tariffs` → 302 to `/admin/login`.
  - `GET /admin/login` → 200, clean render, no Blade errors.
  - Wrong password → 200, re-renders with the translated error, no `admin` cookie set.
  - Correct password → 302 to `admin.tariffs`, `admin` cookie set (HttpOnly, ~8h expiry, matches `AdminSession::LIFETIME`).
  - `POST /admin/logout` → session cleared, next `GET /admin/tariffs` redirects to login again.
  - Toggle round-trip: `POST /admin/tariffs/839/toggle` inserts a `disabled_tariffs` row; toggling the same id again deletes it (idempotent, confirmed via `DB::table('disabled_tariffs')`).
  - `TariffVisibility::filter()` unit-checked directly against a synthetic two-tariff array: the disabled id is stripped, the other survives.
  - `TariffController::connect()` code path re-read: it calls the same `$visibility->filter()` result before the `abort_unless($offered, 403)` check, so a hidden `tariff_id` posted directly is rejected the same way the display list hides it — single source of truth confirmed by construction, not just by the earlier `filter()` unit check.
  - `php artisan test` — 89/89 still green, no regressions.
  - `disabled_tariffs` left empty after testing (cleanup verified).

- **Finding (not a code bug): the default catalog account (1336708) does not resolve against the currently active `API_IP`.**
  - `.env` currently has `API_IP=172.19.1.201:808` active (`.101` commented out) — this has been toggled back and forth during the session.
  - Live probe: `SolaClient::abonentInfo('1336708')` against `.201` → `{"code":110,"errMsg":"Абонент не найден"}`. Same for `availableTariffs('1336708')`.
  - `docs/api/SOLA_API_REFERENCE.md` §14 documents a live probe of account 1336708 on 2026-08-13 — but against `.101`, not `.201`. Tried switching to `.101` to confirm: it hit the *docker bridge collision* documented in `docs/forge-debugger/2026-08-07_15-39_sola-api-unreachable.md` again (route goes to `br-...` instead of the VPN tunnel) — `~/vpn.sh` was broadened to route the whole `172.19.1.0/24` during this session, but that change requires `sudo bash ~/vpn.sh` to actually re-apply (a VPN reconnect), which wasn't run again after the edit. So `.101` could not be reached in this pass to confirm the account exists there.
  - **This is not a defect in the admin panel.** `AdminTariffController::index()` handled the 400/110 business error exactly as designed: it flashed the translated error and rendered the existing empty state (`app.admin.no_tariffs`), with no exception, no broken markup. The account-switcher field exists specifically so an admin can point at whichever account actually resolves on the currently active `API_IP` — this was verified to render and submit correctly.
  - **Not fixed, because there's nothing to fix in this codebase** — it's an external-environment fact (which SOLA box has which test accounts). Left as information for whoever next needs to demo this: confirm which `API_IP` has account 1336708, or supply a different `?acc_id=` that resolves on `.201`.

- **Also found (cosmetic, not fixed — flagging per "same-pattern risks"):** `TariffVisibility::disable()` uses `updateOrInsert(['tariff_id' => $id], [])`, so `created_at`/`updated_at` stay `null` on the `disabled_tariffs` row (the columns are nullable, so no error) — harmless since nothing reads them, but if an audit trail of "when was this hidden" is ever wanted, that insert needs `now()` added explicitly.

- **Regression test:** none written in this pass — that's `forge-test-writer`'s job next, covering the admin auth guard, the login failure/success paths, and `TariffVisibility::filter()`.
