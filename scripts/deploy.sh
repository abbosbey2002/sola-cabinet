#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/.."

exec 9>/tmp/cabinet-deploy.lock
flock -n 9 || { echo "another deploy is already running"; exit 1; }

git fetch --depth 1 origin main
git reset --hard origin/main

docker compose build --build-arg UID="$(id -u)" --build-arg GID="$(id -g)" php
docker compose up -d

docker compose exec -T -u www-data php php artisan down --render=errors::503

docker compose exec -T -u www-data php composer install --no-dev --optimize-autoloader

touch database/database.sqlite
docker compose exec -T -u www-data php php artisan migrate --force

npm ci
npm run build

docker compose exec -T -u www-data php php artisan optimize
docker compose exec -T -u www-data php php artisan up
