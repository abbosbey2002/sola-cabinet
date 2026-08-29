<?php

declare(strict_types=1);

namespace App\Support;

/**
 * What IwonCheckout::redirectUrl() hands back: the URL to send the
 * subscriber's browser to, and the `additional_id` embedded in it — split
 * out so a caller can log the id without re-parsing it back out of the URL.
 * With no callback from iWon at all, this id is the only thread connecting
 * "we sent this subscriber to pay" to whatever iWon's own back office shows
 * later.
 */
final readonly class IwonRedirect
{
    public function __construct(
        public string $url,
        public string $additionalId,
    ) {}
}
