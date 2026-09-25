<?php

declare(strict_types=1);

namespace App\Support\Activity;

/**
 * What came of one tariff change attempt (tariff_changes.result).
 */
enum TariffChangeResult: string
{
    /** Written before billing is called; replaced once it answers. */
    case Pending = 'pending';
    case Success = 'success';
    /** Billing code 129 "Баланс не позволяет изменить тариф". */
    case InsufficientFunds = 'insufficient_funds';
    case BillingError = 'billing_error';
    /** Our own gate refused before billing was asked (see denied_reason). */
    case Denied = 'denied';
    /** Billing did not answer at all (SolaUnavailableException). */
    case Unavailable = 'unavailable';
    /** Still pending long after the request died — see activity:prune. */
    case Unknown = 'unknown';

    /** Billing's "balance does not allow changing the tariff". */
    private const INSUFFICIENT_FUNDS_CODE = '129';

    public static function ofBillingCode(?string $code): self
    {
        return $code === self::INSUFFICIENT_FUNDS_CODE ? self::InsufficientFunds : self::BillingError;
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(fn (self $result): string => $result->value, self::cases());
    }
}
