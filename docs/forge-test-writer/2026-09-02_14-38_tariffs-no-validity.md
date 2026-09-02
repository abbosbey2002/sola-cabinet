# Tariff page hides validity
- **Date:** 2026-09-02_14-38
- **Coverage summary:** Feature `the_current_tariff_is_matched_by_id_not_by_name` now expects speed · volume (no `5 hours` / `30 days`).
- **Failure scenarios included:** none new — this is a display contract on an existing match-by-id test.
- **Deliberately NOT tested & why:** admin `/admin/tariffs` still shows duration; subscriber-only change.
- **Assumptions / risks:** locale default `ru` — assertions use `trans()`, so uz/en hour/day strings are also covered if PHPUnit locale changes.
- **How to run:** `docker compose exec -u www-data php php artisan test --filter=the_current_tariff_is_matched_by_id_not_by_name`
