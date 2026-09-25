<?php

declare(strict_types=1);

namespace App\Support;

use App\Services\Sola\SolaResponse;
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

    /**
     * A failed billing response, worded for the subscriber: our own
     * translation when the code has one (it speaks the page's language and can
     * say what to do next — "top up and try again" for 129), billing's errMsg
     * when it does not, the generic message when there is neither.
     */
    public static function forResponse(SolaResponse $response): string
    {
        $code = $response->errorCode();

        if ($code !== null && $code !== 0 && Lang::has("errors.{$code}")) {
            return (string) trans("errors.{$code}");
        }

        return $response->errorMessage() ?? (string) trans('errors.unknown');
    }
}
