<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\Lang;

/**
 * Translates a SOLA API error code into a message for the subscriber.
 *
 * The previous implementation only knew twelve codes and answered "Success"
 * for everything else, so genuine failures (116-119, 121-127) were shown to
 * the user as a success. Any code present in lang/<locale>/errors.php is now
 * resolved; the rest fall back to a generic message.
 */
final class ErrorMessages
{
    public static function for(int|string|null $code): string
    {
        $key = "errors.{$code}";

        if ($code !== null && $code !== '' && Lang::has($key)) {
            return (string) trans($key);
        }

        return (string) trans('errors.unknown');
    }
}
