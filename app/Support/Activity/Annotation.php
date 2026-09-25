<?php

declare(strict_types=1);

namespace App\Support\Activity;

/**
 * What a controller told ActivityRecorder about the current request's event.
 * A null outcome means "only extra meta — let the HTTP status decide".
 */
final readonly class Annotation
{
    /**
     * @param  array<string, scalar|null>  $meta
     */
    public function __construct(
        public ?Outcome $outcome,
        public ?string $errorCode,
        public ?string $errorMessage,
        public array $meta,
        public ?ActivityEvent $event,
    ) {}
}
