#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/.."

exec 9>/tmp/cabinet-deploy.lock
flock -n 9 || { echo "another deploy is already running"; exit 1; }

git fetch --depth 1 origin main
git reset --hard origin/main

php artisan down --render=errors::503

composer install --no-dev --optimize-autoloader

touch database/database.sqlite
php artisan migrate --force

npm ci
npm run build

php artisan optimize
php artisan up
