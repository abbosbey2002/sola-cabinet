<?php

declare(strict_types=1);

namespace App\Support\Activity;

use App\Services\Sola\SolaResponse;
use App\Support\AbonentProfile;
use App\Support\AbonentSession;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Writes the activity journal (activity_events).
 *
 * Scoped per request. The RecordActivity middleware decides *which* event a
 * route is and writes the row once the response is known; a controller only
 * adds what the middleware cannot see — billing's error code, the amount, the
 * tariff id — through annotate(). One request, one row.
 *
 * Recording must never cost the subscriber anything: the insert runs after the
 * response has been sent (defer) and a failure is logged, never thrown.
 */
final class ActivityRecorder
{
    private const META_LIMIT_BYTES = 4096;

    private ?Annotation $annotation = null;

    private ?AbonentProfile $profile = null;

    public function __construct(
        private readonly AbonentSession $session,
        private readonly RequestContext $context,
    ) {}

    /**
     * Forget the previous request's annotation. Scoped bindings are only
     * flushed per process, so a long-lived app (the test suite, a worker)
     * would otherwise carry one request's outcome into the next.
     */
    public function reset(): void
    {
        $this->annotation = null;
        $this->profile = null;
    }

    public static function isEnabled(): bool
    {
        return (bool) config('activity.enabled');
    }

    /**
     * Say how this request's event ended, when the HTTP status alone would
     * not: a billing refusal still renders a 200 or a redirect.
     *
     * @param  array<string, scalar|null>  $meta
     */
    public function annotate(
        Outcome $outcome,
        ?SolaResponse $billing = null,
        array $meta = [],
        ?ActivityEvent $event = null,
    ): void {
        $this->annotation = new Annotation(
            $outcome,
            $billing !== null && $billing->failed() ? self::code($billing->errorCode()) : null,
            $billing !== null && $billing->failed() ? $billing->errorMessage() : null,
            array_merge($this->annotation->meta ?? [], $meta),
            $event ?? $this->annotation?->event,
        );
    }

    /**
     * Add fields to the event without deciding its outcome.
     *
     * @param  array<string, scalar|null>  $meta
     */
    public function addMeta(array $meta): void
    {
        $this->annotation = $this->annotation === null
            ? new Annotation(null, null, null, $meta, null)
            : new Annotation(
                $this->annotation->outcome,
                $this->annotation->errorCode,
                $this->annotation->errorMessage,
                array_merge($this->annotation->meta, $meta),
                $this->annotation->event,
            );
    }

    public function annotation(): ?Annotation
    {
        return $this->annotation;
    }

    /**
     * The /abonent/info the controller already read — reused for the account
     * columns rather than asking billing again.
     */
    public function withProfile(AbonentProfile $profile): void
    {
        $this->profile = $profile;
    }

    /**
     * Queue one activity_events row, written after the response is sent.
     *
     * @param  array<string, scalar|null>  $meta
     */
    public function record(
        Request $request,
        ActivityEvent $event,
        Outcome $outcome,
        ?int $httpStatus = null,
        ?int $durationMs = null,
        array $meta = [],
        ?string $errorCode = null,
        ?string $errorMessage = null,
    ): void {
        if (! self::isEnabled()) {
            return;
        }

        try {
            $row = $this->row($request, $event, $outcome, $httpStatus, $durationMs, $meta, $errorCode, $errorMessage);
        } catch (Throwable $e) {
            self::report($e, $event);

            return;
        }

        // always(): Laravel skips deferred work on a 4xx/5xx response by
        // default, and a denied, throttled or failed request is exactly what
        // the journal has to see.
        defer(function () use ($row, $event): void {
            try {
                DB::connection(config('activity.connection'))->table('activity_events')->insert($row);
            } catch (Throwable $e) {
                self::report($e, $event);
            }
        })->always();
    }

    /**
     * The account columns as the session knows them right now — shared with
     * TariffChangeRecorder.
     *
     * @return array{account_id: ?string, billing_login: ?string, phone: ?string, full_name: ?string, abon_type: ?int}
     */
    public function account(): array
    {
        $phone = $this->session->phone();

        return [
            'account_id' => self::nullable($this->session->accountId(), 32),
            'billing_login' => self::nullable($this->session->billingLogin(), 64),
            'phone' => $phone > 0 ? (string) $phone : null,
            'full_name' => self::nullable($this->session->fullName(), 255),
            'abon_type' => $this->session->abonentType(),
        ];
    }

    /**
     * @param  array<string, scalar|null>  $meta
     * @return array<string, mixed>
     */
    private function row(
        Request $request,
        ActivityEvent $event,
        Outcome $outcome,
        ?int $httpStatus,
        ?int $durationMs,
        array $meta,
        ?string $errorCode,
        ?string $errorMessage,
    ): array {
        $now = CarbonImmutable::now('UTC');

        return [
            'occurred_at' => $now->format('Y-m-d H:i:s'),
            'occurred_on' => $now->format('Y-m-d'),
            'duration_ms' => $durationMs,
            'event' => $event->value,
            'outcome' => $outcome->value,
            'error_code' => self::nullable($errorCode, 16),
            'error_message' => self::nullable($errorMessage, 255),
            'is_legal_entity' => $this->profile?->isLegalEntity(),
            'http_status' => $httpStatus,
            'meta' => self::encodeMeta($meta),
        ] + $this->account() + $this->context->forEvent($request);
    }

    /** @param array<string, scalar|null> $meta */
    private static function encodeMeta(array $meta): ?string
    {
        if ($meta === []) {
            return null;
        }

        $json = json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);

        if ($json === false || strlen($json) > self::META_LIMIT_BYTES) {
            return json_encode(['truncated' => true]) ?: null;
        }

        return $json;
    }

    private static function code(?int $code): ?string
    {
        return $code === null ? null : (string) $code;
    }

    private static function nullable(?string $value, int $length): ?string
    {
        return $value === null || $value === '' ? null : mb_substr($value, 0, $length);
    }

    private static function report(Throwable $e, ActivityEvent $event): void
    {
        Log::warning('activity: event not recorded', [
            'event' => $event->value,
            'reason' => $e->getMessage(),
        ]);
    }
}
