<?php

declare(strict_types=1);

namespace App\Support\Activity;

/**
 * Why TariffController refused a tariff change before billing was asked.
 */
enum DeniedReason: string
{
    case LegalEntity = 'legal_entity';
    case NotPermanent = 'not_permanent';
    /** Not in this account's /tariff/available at all. */
    case NotInAvailable = 'not_in_available';
    /** Offered by billing, but not opted in by an admin (enabled_tariffs). */
    case NotAllowed = 'not_allowed';
}
