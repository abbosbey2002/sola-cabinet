<?php

declare(strict_types=1);

namespace App\Support;

use App\Services\Sola\SolaClient;
use Carbon\CarbonImmutable;

/**
 * Traffic and payment history over an arbitrary date range.
 *
 * Both take the range directly in one call — traffic() sends detail_begin/
 * detail_end, payments() sends pay_begin/pay_end. Neither walks the range
 * month by month any more; billing added range filtering to both endpoints.
 */
final class BillingHistory
{
    public function __construct(private readonly SolaClient $sola) {}

    /**
     * @return array{rows: list<array<string, mixed>>, input: float, output: float, incomplete: bool}
     */
    public function traffic(string $accountId, Period $period): array
    {
        $response = $this->sola->trafficDetail($accountId, $period->detailBegin(), $period->detailEnd());

        if ($response->failed()) {
            return ['rows' => [], 'input' => 0.0, 'output' => 0.0, 'incomplete' => true];
        }

        $rows = [];

        // Billing is asked for exactly this range, but the boundary rows are
        // still trimmed defensively — the same guard payments() relies on.
        foreach ((array) $response->get('detail', []) as $row) {
            if (is_array($row) && $period->contains($row['event_time'] ?? null)) {
                $rows[] = $row;
            }
        }

        usort($rows, fn (array $a, array $b): int => strcmp((string) ($b['event_time'] ?? ''), (string) ($a['event_time'] ?? '')));

        return [
            'rows' => $rows,
            // The API reports bytes; every screen speaks MiB.
            'input' => self::sum($rows, 'traffic_input') / 1024 / 1024,
            'output' => self::sum($rows, 'traffic_output') / 1024 / 1024,
            'incomplete' => false,
        ];
    }

    /**
     * @return array{rows: list<array<string, mixed>>, total: float, incomplete: bool}
     */
    public function payments(string $accountId, Period $period): array
    {
        $response = $this->sola->payments($accountId, $period->paymentsStart(), $period->paymentsEnd());

        if ($response->failed()) {
            return ['rows' => [], 'total' => 0.0, 'incomplete' => true];
        }

        $rows = [];

        // Billing is asked for exactly this range, but the boundary rows are
        // still trimmed defensively — the same guard traffic() relies on.
        foreach ((array) $response->get('payments', []) as $row) {
            if (is_array($row) && $period->contains($row['payment_date'] ?? null)) {
                $rows[] = $row;
            }
        }

        usort($rows, fn (array $a, array $b): int => strcmp((string) ($b['payment_date'] ?? ''), (string) ($a['payment_date'] ?? '')));

        return [
            'rows' => $rows,
            // Amounts arrive in tiyin.
            'total' => self::sum($rows, 'amount') / 100,
            'incomplete' => false,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private static function sum(array $rows, string $column): float
    {
        $total = 0.0;

        foreach ($rows as $row) {
            $total += (float) ($row[$column] ?? 0);
        }

        return $total;
    }

    /**
     * The payment statuses the spec names, mapped from whatever free text the
     * API sends. Anything unrecognised keeps its own label and a neutral tone,
     * which is honest: inventing "оплачено" for an unknown string would be
     * worse than showing the string.
     */
    public static function paymentTone(?string $status): string
    {
        // Billing answers in the language of the request, so the same status
        // arrives as "оплачено", "to'langan" or "paid" depending on `lang`.
        // Uzbek writes its apostrophe four different ways; they are folded to
        // one before matching, otherwise "to`langan" reads as an unknown
        // status and every paid row loses its green.
        $normalised = str_replace(['ʻ', 'ʼ', '`', '’'], "'", mb_strtolower(trim((string) $status)));

        return match (true) {
            $normalised === '' => 'neutral',
            // The lookahead keeps "to'lanmagan" out: it contains the paid stem
            // and would otherwise be reported as settled.
            (bool) preg_match('/опла(чен|та прошла)|success|paid|to\'lan(?!magan)|tasdiq|заверш/u', $normalised) => 'ok',
            (bool) preg_match('/ожид|pending|kutil|в обработ|process/u', $normalised) => 'pending',
            (bool) preg_match('/ошиб|error|fail|xato|отклон/u', $normalised) => 'error',
            (bool) preg_match('/отмен|cancel|bekor/u', $normalised) => 'cancelled',
            default => 'neutral',
        };
    }

    public static function parseDate(?string $value): ?CarbonImmutable
    {
        return $value ? CarbonImmutable::parse($value) : null;
    }

    /**
     * The most recent payment that nothing later reverses.
     *
     * Billing does not send a "this reverses payment X" reference — a
     * reversal is simply a later row for the exact same amount, negated: the
     * client confirmed a negative row is not a new debit, it is a prior
     * credit being cancelled, so together they add up to nothing received.
     * Matching by amount, most-recent-first, finds the last payment that
     * still stands — verified against a real account's history, where every
     * ± pair nets to zero and the leftover matches the period's own total
     * exactly.
     *
     * A reversal with no earlier same-amount row in this list (the payment it
     * cancels fell outside the requested period) is simply left unmatched —
     * out of scope for a single-month card, same as the API being asked for
     * one month at a time in general.
     *
     * The count-per-amount below is not a verified 1:1 link between a
     * specific reversal and the row it cancels — with three or more rows
     * sharing an amount it is a greedy match, not a traced reference. Billing
     * sends no "this reverses payment X" id to check against, so amount and
     * recency are the only signal there is.
     *
     * @param  list<array<string, mixed>>  $rows  most-recent-first, as
     *                                            payments() returns them
     * @return array<string, mixed>|null
     */
    public static function lastRealPayment(array $rows): ?array
    {
        $pendingReversals = [];

        foreach ($rows as $row) {
            $amount = (int) ($row['amount'] ?? 0);

            if ($amount < 0) {
                $pendingReversals[-$amount] = ($pendingReversals[-$amount] ?? 0) + 1;

                continue;
            }

            if ($amount === 0) {
                continue;
            }

            if (($pendingReversals[$amount] ?? 0) > 0) {
                $pendingReversals[$amount]--;

                continue;
            }

            return $row;
        }

        return null;
    }
}
