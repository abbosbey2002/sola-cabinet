<?php

declare(strict_types=1);

namespace App\Support\Admin;

/**
 * admins.role. What each role may do is AdminAbility's business, not this
 * enum's — see AdminRole::can().
 */
enum AdminRole: string
{
    /** Everything, including the tariff allow-list. */
    case Admin = 'admin';
    /** Numbers and technical detail, but subscribers' phones and names masked. */
    case Analyst = 'analyst';
    /** Subscribers' contact details and history, no technical detail. */
    case Sales = 'sales';

    public function can(AdminAbility $ability): bool
    {
        return in_array($this, $ability->roles(), true);
    }

    /** Where this role lands after signing in — the first screen it may open. */
    public function homeRoute(): string
    {
        return $this === self::Admin ? 'admin.tariffs' : 'admin.stats';
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(fn (self $role): string => $role->value, self::cases());
    }
}
