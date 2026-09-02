# Prod styles missing after deploy

- **Symptom:** Production cabinet rendered without (new) styles after pushing home-page + CI-fix commits.
- **Root cause:** `scripts/deploy.sh` runs `git reset --hard` then `php artisan down --render=errors::503`. The 503 view extends `layouts.guest`, which called `$errors->any()`. Artisan has no `ShareErrorsFromSession` middleware, so `$errors` is undefined. `set -e` aborted the script **before** `npm ci && npm run build`. New Blade was already on disk; `public/build` stayed on the previous Vite hash (gitignored). Tailwind 4 CSS therefore lacked the new utilities.
- **Evidence chain:**
  - GitHub Actions deploy logs for `9f3e896` and `b028986`: `Failed to enter maintenance mode: Undefined variable $errors (View: resources/views/layouts/guest.blade.php)`, exit 1.
  - Last successful deploy: `d04cdef` (2026-08-29). Later main pushes failed at `artisan down`.
  - Local tinker `view('errors.503')->render()`: same exception. After `isset($errors)`, it renders.
- **Fix:** Guard `$errors` in guest/app/admin/admin-guest layouts. Deploy falls back to `php artisan down` if the custom 503 cannot render.
- **Numbers:** n/a
- **Regression test:** `tests/Feature/MaintenancePageTest.php`
- **Same-pattern risks:** Any other Blade used from CLI (`down --render`, mail previews, `view:cache` of odd paths) that assumes HTTP-shared `$errors` or `session()`. `cabinet/partials/topup-form.blade.php` still uses `$errors->has` — HTTP-only, left alone.
