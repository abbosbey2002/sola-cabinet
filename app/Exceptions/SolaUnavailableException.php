<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Support\ErrorMessages;
use RuntimeException;
use Throwable;

/**
 * The SOLA API could not be reached at all (DNS, TCP, TLS or timeout).
 *
 * A non-200 answer from the API is *not* this exception — that is a business
 * outcome the controllers render through {@see ErrorMessages}.
 */
final class SolaUnavailableException extends RuntimeException
{
    public function __construct(
        public readonly string $endpoint,
        string $message,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
