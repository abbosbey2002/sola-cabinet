# Home balance card: billing status

- **Date:** 2026-09-03 13:54
- **Coverage summary:** Feature tests on `GET /` — live-shaped `"Активен"` is rendered with `data-abonent-status`; omitted `status` hides the marker. `php artisan test` — 197 passed.
- **Failure scenarios included:** missing API field (hide). Invalid/auth/concurrency N/A — display of an already-fetched string.
- **Deliberately NOT tested & why:** empty-string `status` — same `AbonentProfile::string()` path as omit. Colour mapping — none exists. Devices page still asserts no `app.header.status` column (unchanged).
- **Red→green:** new tests; would fail before the Blade pill existed (`assertSee('data-abonent-status')`).
- **Command:** `php artisan test` (197 passed, 643 assertions)
