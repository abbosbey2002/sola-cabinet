<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\BillingHistory;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Spec §6 asks for four coloured payment statuses, but billing sends the
 * status as free text translated into the language of the request. The colour
 * therefore hangs entirely on this matcher, and a miss is not neutral in
 * effect: a settled payment shown grey reads to the subscriber as "something
 * went wrong with my money".
 */
final class PaymentToneTest extends TestCase
{
    /**
     * The bug this pins: with lang=uz the live API answers "to'langan", which
     * matched none of the Russian or English stems, so every paid row on
     * /finance rendered in the neutral grey pill.
     */
    #[Test]
    public function the_uzbek_word_for_paid_is_recognised(): void
    {
        $this->assertSame('ok', BillingHistory::paymentTone("to'langan"));
    }

    /**
     * Billing is not consistent about which apostrophe it sends, and the
     * subscriber cannot tell them apart on screen — neither should the match.
     */
    #[Test]
    public function every_spelling_of_the_uzbek_apostrophe_counts_as_paid(): void
    {
        foreach (["to'langan", 'to`langan', 'toʻlangan', 'toʼlangan', 'to’langan'] as $status) {
            $this->assertSame('ok', BillingHistory::paymentTone($status), $status);
        }
    }

    /**
     * The negation carries the paid stem inside it. Reporting an unpaid charge
     * as settled is the one mistake in this file that costs the subscriber
     * money, so it is pinned separately.
     */
    #[Test]
    public function the_uzbek_negation_is_never_reported_as_paid(): void
    {
        $this->assertNotSame('ok', BillingHistory::paymentTone("to'lanmagan"));
    }

    #[Test]
    public function the_statuses_the_spec_names_keep_their_tones(): void
    {
        $this->assertSame('ok', BillingHistory::paymentTone('Оплачено'));
        $this->assertSame('ok', BillingHistory::paymentTone('paid'));
        $this->assertSame('pending', BillingHistory::paymentTone('Ожидает оплаты'));
        $this->assertSame('pending', BillingHistory::paymentTone('kutilmoqda'));
        $this->assertSame('error', BillingHistory::paymentTone('Ошибка'));
        $this->assertSame('cancelled', BillingHistory::paymentTone('Отменено'));
        $this->assertSame('cancelled', BillingHistory::paymentTone('bekor qilingan'));
    }

    /**
     * An unknown string keeps its own label and a neutral tone. Guessing a
     * colour for it would be a claim the cabinet cannot support.
     */
    #[Test]
    public function an_unknown_status_stays_neutral(): void
    {
        $this->assertSame('neutral', BillingHistory::paymentTone('shaxmat'));
        $this->assertSame('neutral', BillingHistory::paymentTone(null));
        $this->assertSame('neutral', BillingHistory::paymentTone('   '));
    }
}
