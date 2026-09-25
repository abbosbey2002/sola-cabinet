<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Activity journal, tariff-change history and admin audit
    |--------------------------------------------------------------------------
    |
    | What subscribers do in the cabinet, recorded for the admin panel's
    | statistics (docs/plans/2026-09-23_activity-log-and-stats.md). None of it
    | is ever shown back to a subscriber: every number on a cabinet screen still
    | comes from the billing API in real time.
    |
    | Off by default. The journal stores personal data (phone, name, IP), so it
    | is switched on in production only once the company's lawyer has signed
    | off under ZRU-547 and the privacy policy says so. With the flag off
    | nothing is written and the admin statistics pages show empty tables.
    |
    */

    'enabled' => (bool) env('ACTIVITY_ENABLED', false),

    /*
     * The database connection every activity table lives on — PostgreSQL in
     * production (config/database.php "activity"). Kept apart from the default
     * SQLite file, which only holds admins and the tariff allow-list.
     */
    'connection' => env('ACTIVITY_DB_CONNECTION', 'activity'),

    /*
     * Retention. Raw events are pruned after this many months; the daily and
     * monthly roll-ups built from them are kept indefinitely. Tariff changes
     * and the admin audit trail are money / personal-data access records and
     * are kept for years.
     */
    'retention' => [
        'events_months' => (int) env('ACTIVITY_EVENTS_RETENTION_MONTHS', 12),
        'tariff_changes_years' => (int) env('ACTIVITY_TARIFF_CHANGES_RETENTION_YEARS', 3),
        'admin_audit_years' => (int) env('ACTIVITY_ADMIN_AUDIT_RETENTION_YEARS', 3),
    ],

    /*
     * A tariff change that is still "pending" after this many minutes never
     * heard back from billing (the PHP process died mid-request). The prune
     * command marks it "unknown" so it stands out in the admin list.
     */
    'pending_timeout_minutes' => 15,

    /*
     * Offline IP → city lookup: a MaxMind GeoLite2-City (or DB-IP Lite)
     * .mmdb file on this server. No IP ever leaves the box. Unset → the
     * default path below; a file that is not there → geo columns stay null.
     */
    'geoip_database' => env('ACTIVITY_GEOIP_DATABASE') ?: storage_path('app/geoip/GeoLite2-City.mmdb'),

];
