# PostgreSQL for the activity journal

- **Date:** 2026-09-25 16:24
- **Current state:** production is bare-metal PHP (no Docker) deployed by `scripts/deploy.sh` over SSH from GitHub Actions; local dev is Docker (php + nginx, network pinned to 10.123.0.0/24). Load: ≤5 000 daily active subscribers expected → one PostgreSQL on the same server is enough.
- **Decisions:**
  - `docker-compose.yml`: `postgres:18-alpine` service with healthcheck, named volume, bound to 127.0.0.1:5433; not a `depends_on` of php (the cabinet runs without the journal). Network untouched.
  - `docker/php/Dockerfile`: `libpq-dev` + `pdo_pgsql` (verified in the built image).
  - `.env.example`: `ACTIVITY_*` block, off by default.
  - `scripts/deploy.sh`: `php artisan activity:migrate --force || echo WARNING` — non-fatal so a down database never leaves the site in maintenance mode under `set -e`.
  - CI unchanged: `phpunit.xml` points the journal at in-memory SQLite.
  - Docs: `DOCKER.md` (local), new `docs/task/ACTIVITY_JOURNAL_DEPLOY.uz.md` (server). `docs/task/DEPLOY.ru.md` left alone — it describes a different product (Sola Portal).
- **Rollback:** `ACTIVITY_ENABLED=false` + `config:clear` (1 min, cabinet unaffected); tables `migrate:rollback --database=activity --path=database/migrations/activity` (verified up/down on PG 18); `admins.role` `migrate:rollback --step=1`; compose/Dockerfile via git revert.
- **Risks & mitigation:** personal data — flag off until lawyer sign-off; no cron → rollups empty and table grows (documented, no error); backups — pg_dump cron documented, restore must be tested once.
- **User must do:** install PostgreSQL + `php-pgsql` on the server, create role/db, fill `.env`, add `schedule:run` cron, optionally GeoLite2 file + monthly refresh, pg_dump cron.
- **Left for later:** monitoring of the journal DB size; managed PostgreSQL if daily actives exceed ~50 000.
