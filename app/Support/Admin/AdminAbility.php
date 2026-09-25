<?php

declare(strict_types=1);

namespace App\Support\Admin;

/**
 * The admin panel's permission table (plan §7), in one place.
 *
 * Checked on the server by the admin.can middleware and by the controllers
 * that shape data per role; Blade hiding a link is a courtesy on top.
 */
enum AdminAbility: string
{
    /** /admin/stats* — aggregate numbers. */
    case ViewStats = 'view-stats';
    /** /admin/tariff-changes list and detail. */
    case ViewTariffChanges = 'view-tariff-changes';
    /** Subscribers' phone and full name unmasked. */
    case ViewPersonalData = 'view-personal-data';
    /** /admin/accounts — one account's whole history. */
    case ViewAccountHistory = 'view-account-history';
    /** IP, user agent, the raw /abonent/info snapshot. */
    case ViewTechnicalData = 'view-technical-data';
    /** CSV export (masked for a role without ViewPersonalData). */
    case Export = 'export';
    /** /admin/tariffs — which tariffs subscribers are offered. */
    case ManageTariffs = 'manage-tariffs';

    /** @return list<AdminRole> */
    public function roles(): array
    {
        return match ($this) {
            self::ViewStats, self::ViewTariffChanges, self::Export => [AdminRole::Admin, AdminRole::Analyst, AdminRole::Sales],
            self::ViewPersonalData, self::ViewAccountHistory => [AdminRole::Admin, AdminRole::Sales],
            self::ViewTechnicalData => [AdminRole::Admin, AdminRole::Analyst],
            self::ManageTariffs => [AdminRole::Admin],
        };
    }
}
