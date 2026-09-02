# DashboardView iWon pin

- **Date:** 2026-09-02 12:53
- **Coverage summary:** Unit — one-off layout still asserts `canTopUp` when iWon is on; new case asserts `canTopUp` is false when iWon is off. Feature — empty-whitelist connect now clears tariff 9 first.
- **Failure scenarios included:** iWon inactive (UI gate off even for one-off subscribers). Empty whitelist despite a locally enabled tariff 9.
- **Deliberately NOT tested & why:** Production `TopUpController` 404 when iWon is off is already in `TopUpTest`. Legal-entity `canTopUp` false even with iWon on is already in `DashboardViewTest`.
- **Known gaps / next tests:** Other optional flags (`web3.active`, `geoip`) are not pinned in `phpunit.xml`; add if a test starts reading them without `config()`.
- **Suite result:** `php artisan test` — 191 passed (619 assertions). Pint clean. Red→green: unpatched one-off test fails at line 26 with phpunit `IWON_ACTIVE=false`; patched test passes.
