<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\Activity\ActivityEvent;
use App\Support\Activity\ActivityRecorder;
use App\Support\Activity\Outcome;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

/**
 * Records one activity_events row for every cabinet route that stands for an
 * event (ActivityEvent::forRoute) — menu pages and server actions alike.
 *
 * Appended to the "web" group, so it wraps the route's own middleware: a
 * throttled login still passes through here and is recorded as rate_limited,
 * and a FormRequest that fails validation is seen as the redirect it becomes.
 *
 * The outcome is whatever the controller annotated; failing that, it is read
 * off the response (403 → denied, validation → invalid, 5xx → fail).
 */
final class RecordActivity
{
    public function __construct(private readonly ActivityRecorder $recorder) {}

    public function handle(Request $request, Closure $next): Response
    {
        $this->recorder->reset();

        $routeEvent = ActivityEvent::forRoute($request->route()?->getName());

        if ($routeEvent === null || ! ActivityRecorder::isEnabled()) {
            return $next($request);
        }

        $started = hrtime(true);

        try {
            $response = $next($request);
        } catch (Throwable $e) {
            // Normally the router turns an exception into a response before it
            // gets here; one thrown while rendering (the billing-down 503 is
            // an abort() inside a render callback) bubbles past instead. Still
            // an event — record it, then let it go on to the handler.
            $status = $e instanceof HttpExceptionInterface ? $e->getStatusCode() : 500;

            $this->recorder->record(
                $request,
                $status === 429 ? ActivityEvent::RateLimited : $routeEvent,
                Outcome::ofStatus($status),
                $status,
                (int) round((hrtime(true) - $started) / 1_000_000),
                ($this->recorder->annotation()->meta ?? [])
                    + ($status === 429 ? ['route_event' => $routeEvent->value] : []),
                (string) $status,
            );

            throw $e;
        }

        $status = $response->getStatusCode();
        $annotation = $this->recorder->annotation();
        $event = $annotation?->event ?? $routeEvent;
        $meta = $annotation->meta ?? [];

        if ($status === 429) {
            $meta['route_event'] = $event->value;
            $event = ActivityEvent::RateLimited;
        }

        $outcome = $annotation?->outcome ?? $this->outcomeOf($response, $status);

        $this->recorder->record(
            $request,
            $event,
            $outcome,
            $status,
            (int) round((hrtime(true) - $started) / 1_000_000),
            $meta,
            $annotation?->errorCode ?? ($status >= 500 ? (string) $status : null),
            $annotation?->errorMessage,
        );

        return $response;
    }

    private function outcomeOf(Response $response, int $status): Outcome
    {
        $exception = property_exists($response, 'exception') ? $response->exception : null;

        if ($exception instanceof ValidationException) {
            return Outcome::Invalid;
        }

        return Outcome::ofStatus($status);
    }
}
