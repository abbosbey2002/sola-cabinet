# Admin seeder — password reset

**Task:** User forgot the admin panel password. Create a seeder and run it.

**SCOPE:** backend · **RISK:** true (auth/admin credentials) · **SIZE:** small but RISK=true → full chain.

## What was done
- Added `database/seeders/AdminSeeder.php`: resets the `admins` table's `admin` row password to a fresh `Str::password(16)` (or creates the row if missing), hashed with `Hash::make`. Prints the plaintext once via `$this->command?->warn()`.
- Added `"Database\\Seeders\\": "database/seeders/"` to `composer.json` psr-4 autoload — no `database/seeders/` directory existed in this project before, and no `DatabaseSeeder.php` exists either (seeder is only runnable via `--class=Database\Seeders\AdminSeeder`, not bare `db:seed`).
- Ran `composer dump-autoload` and `php artisan db:seed --class="Database\Seeders\AdminSeeder"` — new password delivered to the user in the session.
- `php artisan test` — 179 passed, no regressions.

## Review outcomes
- `forge-code-reviewer`: **APPROVE**. Warning: seeder overwrites an existing admin's password with no confirmation/`--force` guard, unlike the sibling `app/Console/Commands/CreateAdminCommand.php` which refuses if the username exists. Flagged as intentional (this *is* the password-reset path), not a bug.
- `forge-security-auditor`: **SHIP**. Same medium finding — silent credential rotation on re-run. Explicitly called out: safe today because it's CLI-only and not wired into any automatic seeding flow; would become a real risk if someone later adds `$this->call(AdminSeeder::class)` to a `DatabaseSeeder` used in CI/deploy, since a routine `db:seed` would then silently rotate the live admin's password.

## Known follow-up (not done, flagged only)
If this seeder is ever wired into `DatabaseSeeder`/CI, add a guard (skip-if-exists or `--force`/confirm prompt) before that happens — see both review reports above.

No routing incident — RISK=true correctly triggered both `forge-code-reviewer` and `forge-security-auditor` gates in parallel, no unnecessary agent calls.
