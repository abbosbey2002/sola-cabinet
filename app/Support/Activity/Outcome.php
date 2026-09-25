<?php

declare(strict_types=1);

namespace App\Support\Activity;

use App\Services\Sola\SolaResponse;

/**
 * How a recorded event ended.
 *
 * - ok: done
 * - fail: billing refused or errored (the code is in error_code)
 * - denied: our own gate said no (403, not a permanent subscriber, legal entity)
 * - invalid: the form did not validate, or the request was throttled
 */
enum Outcome: string
{
    case Ok = 'ok';
    case Fail = 'fail';
    case Denied = 'denied';
    case Invalid = 'invalid';

    public static function ofBilling(SolaResponse $response): self
    {
        return $response->successful() ? self::Ok : self::Fail;
    }

    /** The fallback when a controller did not say: read it off the HTTP status. */
    public static function ofStatus(int $status): self
    {
        return match (true) {
            $status === 403 || $status === 404 => self::Denied,
            $status === 419 || $status === 422 || $status === 429 => self::Invalid,
            $status >= 500 => self::Fail,
            default => self::Ok,
        };
    }
}
