<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\BillingHistory;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * A negative row is not a new debit — the client confirmed it is a prior
 * credit being reversed, so the pair together added nothing to the balance.
 * The home page's "last payment" card has to find the last payment that
 * still stands, not just the last row with a plus sign.
 */
final class LastRealPaymentTest extends TestCase
{
    /**
     * Real account history (2026-08-24 through 2026-08-27, account 1336708):
     * two ±200 000 pairs and one ±1 450 000 pair each cancel out, leaving the
     * 250 000 payment as the only one nothing reverses — and that leftover
     * matches the period's own reported total (250 000 сум) exactly.
     */
    #[Test]
    public function reversed_payments_are_skipped_down_to_the_one_nothing_cancels(): void
    {
        $rows = [
            ['payment_id' => '1067505', 'amount' => -20_000_000],
            ['payment_id' => '1067504', 'amount' => -20_000_000],
            ['payment_id' => '1067503', 'amount' => 20_000_000],
            ['payment_id' => '1067502', 'amount' => 20_000_000],
            ['payment_id' => '1065665', 'amount' => -145_000_000],
            ['payment_id' => '1065034', 'amount' => 145_000_000],
            ['payment_id' => '1064146', 'amount' => -14_500_000],
            ['payment_id' => '1064145', 'amount' => 25_000_000],
            ['payment_id' => '1064144', 'amount' => 14_500_000],
        ];

        $this->assertSame('1064145', BillingHistory::lastRealPayment($rows)['payment_id']);
    }

    /**
     * The reversal is the most recent row, and it cancels the payment right
     * behind it — not just any earlier positive row. Naively skipping every
     * negative row and returning the first positive one would wrongly return
     * the payment this very reversal cancels.
     */
    #[Test]
    public function a_reversal_cancels_the_specific_payment_it_matches_not_just_any_earlier_one(): void
    {
        $rows = [
            ['payment_id' => 'reversal', 'amount' => -20_000_000],
            ['payment_id' => 'cancelled', 'amount' => 20_000_000],
            ['payment_id' => 'real', 'amount' => 25_000_000],
        ];

        $this->assertSame('real', BillingHistory::lastRealPayment($rows)['payment_id']);
    }

    #[Test]
    public function an_unmatched_amount_does_not_cancel_a_different_payment(): void
    {
        $rows = [
            ['payment_id' => 'unrelated-reversal', 'amount' => -20_000_000],
            ['payment_id' => 'real', 'amount' => 25_000_000],
        ];

        $this->assertSame('real', BillingHistory::lastRealPayment($rows)['payment_id']);
    }

    #[Test]
    public function nothing_left_standing_means_no_last_payment(): void
    {
        $rows = [
            ['payment_id' => 'reversal', 'amount' => -20_000_000],
            ['payment_id' => 'cancelled', 'amount' => 20_000_000],
        ];

        $this->assertNull(BillingHistory::lastRealPayment($rows));
    }

    #[Test]
    public function an_empty_history_has_no_last_payment(): void
    {
        $this->assertNull(BillingHistory::lastRealPayment([]));
    }

    /**
     * Not observed in real data, but the sum of a fully-cancelled-out chain
     * could in principle land on exactly zero — it must not be mistaken for
     * a real payment (it is not positive) or crash the loop.
     */
    #[Test]
    public function a_zero_amount_row_is_skipped(): void
    {
        $rows = [
            ['payment_id' => 'zero', 'amount' => 0],
            ['payment_id' => 'real', 'amount' => 25_000_000],
        ];

        $this->assertSame('real', BillingHistory::lastRealPayment($rows)['payment_id']);
    }
}
