#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/.."

exec 9>/tmp/cabinet-deploy.lock
flock -n 9 || { echo "another deploy is already running"; exit 1; }

git fetch --depth 1 origin main
git reset --hard origin/main

# Custom 503 reuses the guest layout. If that view cannot render from the
# CLI (no session $errors bag), fall back to Laravel's default maintenance
# page so npm run build still runs — otherwise git reset leaves new Blade
# against a stale public/build and the cabinet ships unstyled.
php artisan down --render=errors::503 || php artisan down

composer install --no-dev --optimize-autoloader

touch database/database.sqlite
php artisan migrate --force

# Activity journal (PostgreSQL). A no-op while ACTIVITY_ENABLED=false. Not
# fatal: the cabinet runs without the journal, and a database that is down
# must not leave the site in maintenance mode — set -e would stop right here,
# before "php artisan up".
php artisan activity:migrate --force || echo "WARNING: activity:migrate failed — journal tables not migrated" >&2

npm ci
npm run build

php artisan optimize
php artisan up
