# CI DashboardView canTopUp false

- **Symptom:** GitHub Actions `php artisan test` failed on `DashboardViewTest::one_off_subscribers_get_the_one_time_layout_kind` — `assertTrue($view->canTopUp)` got false. 162 tests also warned `file_get_contents(.../.env): Failed to open stream`.
- **Root cause:** `DashboardView::make` sets `canTopUp = config('iwon.active') && $kind !== KIND_LEGAL`. The one-off test did not pin `iwon.active` (siblings did). Local `.env` has `IWON_ACTIVE=true`, so the test passed on the developer machine. CI has no `.env`, so `env('IWON_ACTIVE', false)` is false. The `.env` warnings are the same missing file; PHPUnit 12 surfaces the PHP warning.
- **Evidence chain:**
  - CI stack: `tests/Unit/DashboardViewTest.php:26` false is not true.
  - Local `config('iwon.active')` is true via `.env`; `php artisan test --filter=DashboardViewTest` passed.
  - After pinning `IWON_ACTIVE=false` in `phpunit.xml`, the unpatched test failed locally with the same assertion as CI (red). Restoring `config(['iwon.active' => true])` went green.
  - Ruled out: production `canTopUp` logic is correct; this was a hermeticity bug, not a dashboard regression.
- **Fix:** Pin `iwon.active` in the one-off test; add inactive-iwon case; pin `IWON_ACTIVE=false` in `phpunit.xml`; copy `.env.example` + `key:generate` in CI before migrate. Bonus: `CabinetTest` empty-whitelist case now `disable(9)` so local sqlite admin rows cannot turn a 403 into a 302.
- **Numbers:** n/a
- **Regression test:** `tests/Unit/DashboardViewTest.php` (existing test now hermetic + `one_off_subscribers_cannot_top_up_when_iwon_is_inactive`)
- **Same-pattern risks:** Any test that reads `config('iwon.active')` / `config('web3.active')` / `config('geoip.*')` without pinning will pass locally and fail in CI. TopUpTest already pins. Feature tests that assert iWon UI must keep doing so.
