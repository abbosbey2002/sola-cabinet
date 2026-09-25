<?php

declare(strict_types=1);

namespace App\Support\Activity;

use App\Exceptions\SolaUnavailableException;
use App\Services\Sola\SolaClient;
use App\Services\Sola\SolaResponse;
use App\Support\AbonentProfile;
use App\Support\ConnectedTariff;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * The tariff_changes audit: one row per "change tariff" attempt, whatever
 * came of it (plan §4).
 *
 * The row is written as `pending` *before* billing is called, synchronously —
 * unlike the activity journal, this is the money audit, and the case that
 * matters most is billing switching the tariff and the answer never arriving.
 * attempt() then settles it: success, insufficient_funds (code 129),
 * billing_error, unavailable, or — for anything else that blows up — unknown.
 *
 * Recording still never blocks the switch itself: if the activity database is
 * down, the subscriber's tariff is changed anyway and the failure is logged.
 */
final class TariffChangeRecorder
{
    public function __construct(
        private readonly ActivityRecorder $activity,
        private readonly RequestContext $context,
        private readonly SolaClient $sola,
    ) {}

    /**
     * Write the attempt as `pending`. Returns its id, or null when recording is
     * off or failed (attempt() then just runs the call).
     *
     * @param  array<string, mixed>|null  $newTariff  the /tariff/available row
     */
    public function begin(
        Request $request,
        SolaResponse $info,
        int $tariffId,
        ?array $newTariff,
        string $timing,
        CarbonImmutable $effectiveDate,
        ?ConnectedTariff $connected,
    ): ?int {
        return $this->insert($request, $info, $tariffId, $newTariff, [
            'result' => TariffChangeResult::Pending->value,
            'timing' => $timing,
            'effective_date' => $effectiveDate->format('Y-m-d'),
            'old_tariff_connected_at' => $connected?->startedAt()?->format('Y-m-d'),
        ]);
    }

    /**
     * Our own gate refused before billing was asked.
     *
     * @param  array<string, mixed>|null  $newTariff
     */
    public function deny(Request $request, SolaResponse $info, int $tariffId, ?array $newTariff, DeniedReason $reason): void
    {
        $this->insert($request, $info, $tariffId, $newTariff, [
            'result' => TariffChangeResult::Denied->value,
            'denied_reason' => $reason->value,
        ]);
    }

    /**
     * Run the billing call and settle the pending row with whatever it says.
     *
     * @param  Closure(): SolaResponse  $connect
     *
     * @throws SolaUnavailableException rethrown after the row is marked unavailable
     */
    public function attempt(?int $changeId, string $accountId, Closure $connect): SolaResponse
    {
        $started = hrtime(true);
        $elapsed = fn (): int => (int) round((hrtime(true) - $started) / 1_000_000);

        try {
            $response = $connect();
        } catch (SolaUnavailableException $e) {
            $this->settle($changeId, TariffChangeResult::Unavailable, $elapsed(), 'unavailable', $e->getMessage());

            throw $e;
        } catch (Throwable $e) {
            $this->settle($changeId, TariffChangeResult::Unknown, $elapsed(), null, $e->getMessage());

            throw $e;
        }

        if ($response->successful()) {
            $this->settle($changeId, TariffChangeResult::Success, $elapsed());
            $this->recordAftermath($changeId, $accountId);
        } else {
            $code = $response->errorCode() === null ? null : (string) $response->errorCode();

            $this->settle(
                $changeId,
                TariffChangeResult::ofBillingCode($code),
                $elapsed(),
                $code,
                $response->errorMessage(),
            );
        }

        return $response;
    }

    /**
     * @param  array<string, mixed>|null  $newTariff
     * @param  array<string, string|null>  $outcome
     */
    private function insert(Request $request, SolaResponse $info, int $tariffId, ?array $newTariff, array $outcome): ?int
    {
        if (! ActivityRecorder::isEnabled()) {
            return null;
        }

        try {
            $profile = AbonentProfile::from($info);
            $account = $this->activity->account();
            $now = CarbonImmutable::now('UTC')->format('Y-m-d H:i:s');

            $row = [
                'created_at' => $now,
                'updated_at' => $now,
                'account_id' => $account['account_id'] ?? '',
                'billing_login' => $account['billing_login'],
                'phone' => $account['phone'],
                'full_name' => $account['full_name'],
                'abon_type' => $account['abon_type'],
                'is_legal_entity' => $profile->isLegalEntity(),
                'account_status' => self::limit($profile->status(), 64),
                'address' => self::limit($profile->address(), 255),
                'email' => self::limit($profile->email(), 255),
                'contract_date' => $profile->contractDate()?->format('Y-m-d'),
                'balance_before' => self::money($profile->balance()),
                'next_charge_date' => $profile->nextChargeDate()?->format('Y-m-d'),
                'old_tariff_id' => self::limit($profile->currentTariffId(), 16),
                'old_tariff_name' => self::limit($profile->currentTariffDisplayName(), 255),
                'old_tariff_price' => self::money($profile->currentTariffCost()),
                'new_tariff_id' => (string) $tariffId,
                'profile_snapshot' => json_encode($info->body, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE) ?: null,
            ] + self::newTariffColumns($newTariff) + $outcome + $this->context->basics($request);

            return (int) $this->db()->table('tariff_changes')->insertGetId($row);
        } catch (Throwable $e) {
            Log::error('activity: tariff change not recorded', [
                'tariff_id' => $tariffId,
                'result' => $outcome['result'] ?? null,
                'reason' => $e->getMessage(),
            ]);

            return null;
        }
    }

    private function settle(
        ?int $changeId,
        TariffChangeResult $result,
        int $durationMs,
        ?string $errorCode = null,
        ?string $errorMessage = null,
    ): void {
        if ($changeId === null) {
            return;
        }

        try {
            $this->db()->table('tariff_changes')->where('id', $changeId)->update([
                'result' => $result->value,
                'billing_duration_ms' => $durationMs,
                'error_code' => self::limit($errorCode, 16),
                'error_message' => self::limit($errorMessage, 255),
                'updated_at' => CarbonImmutable::now('UTC')->format('Y-m-d H:i:s'),
            ]);
        } catch (Throwable $e) {
            Log::error('activity: tariff change result not recorded', [
                'tariff_change_id' => $changeId,
                'result' => $result->value,
                'reason' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Balance and pending tariff as billing reports them after a successful
     * switch — one extra read, after the response has gone out.
     */
    private function recordAftermath(?int $changeId, string $accountId): void
    {
        if ($changeId === null) {
            return;
        }

        defer(function () use ($changeId, $accountId): void {
            try {
                $info = $this->sola->abonentInfo($accountId);

                if ($info->failed()) {
                    return;
                }

                $profile = AbonentProfile::from($info);

                $this->db()->table('tariff_changes')->where('id', $changeId)->update([
                    'balance_after' => self::money($profile->balance()),
                    'pending_tariff_after' => self::limit($profile->nextTariffDisplayName(), 255),
                ]);
            } catch (Throwable $e) {
                Log::warning('activity: tariff change aftermath not recorded', [
                    'tariff_change_id' => $changeId,
                    'reason' => $e->getMessage(),
                ]);
            }
        });
    }

    /**
     * Prices from /tariff/available.cost are tiyin; stored in soʻm like every
     * other amount in the table.
     *
     * @param  array<string, mixed>|null  $tariff
     * @return array<string, string|null>
     */
    private static function newTariffColumns(?array $tariff): array
    {
        if ($tariff === null) {
            return ['new_tariff_name' => null, 'new_tariff_price' => null, 'new_tariff_speed' => null, 'new_tariff_period' => null];
        }

        $cost = $tariff['cost'] ?? null;

        return [
            'new_tariff_name' => self::limit(trim((string) ($tariff['tariff_name'] ?? '')), 255),
            'new_tariff_price' => is_numeric($cost) ? self::tiyinToSom((int) $cost) : null,
            'new_tariff_speed' => self::limit(trim((string) ($tariff['tspd'] ?? '').' '.(string) ($tariff['spdu'] ?? '')), 32),
            'new_tariff_period' => self::limit(trim((string) ($tariff['tprd'] ?? '').' '.(string) ($tariff['prdu'] ?? '')), 32),
        ];
    }

    /** Integer arithmetic, so 12345 tiyin is exactly "123.45", not a float's guess. */
    private static function tiyinToSom(int $tiyin): string
    {
        $sign = $tiyin < 0 ? '-' : '';
        $tiyin = abs($tiyin);

        return sprintf('%s%d.%02d', $sign, intdiv($tiyin, 100), $tiyin % 100);
    }

    /** Soʻm with kopecks as a decimal string — never a float into the column. */
    private static function money(?float $som): ?string
    {
        return $som === null ? null : number_format($som, 2, '.', '');
    }

    private static function limit(?string $value, int $length): ?string
    {
        return $value === null || $value === '' ? null : mb_substr($value, 0, $length);
    }

    private function db(): ConnectionInterface
    {
        return DB::connection(config('activity.connection'));
    }
}
