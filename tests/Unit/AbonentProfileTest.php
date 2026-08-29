<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\Sola\SolaResponse;
use App\Support\AbonentProfile;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The `legal` field decides whether the tariff section is offered at all
 * (TariffController, partials/topbar.blade.php, cabinet/index.blade.php) —
 * confirmed by the client on 2026-08-18.
 */
final class AbonentProfileTest extends TestCase
{
    #[Test]
    public function an_individual_reports_the_literal_physical_person_string(): void
    {
        $this->assertFalse($this->profile(['legal' => 'Физическое лицо'])->isLegalEntity());
    }

    #[Test]
    public function an_individual_reports_zero(): void
    {
        $this->assertFalse($this->profile(['legal' => 0])->isLegalEntity());
        $this->assertFalse($this->profile(['legal' => '0'])->isLegalEntity());
    }

    #[Test]
    public function anything_else_is_a_legal_entity(): void
    {
        $this->assertTrue($this->profile(['legal' => 'Юридическое лицо'])->isLegalEntity());
        $this->assertTrue($this->profile(['legal' => 1])->isLegalEntity());
        $this->assertTrue($this->profile(['legal' => 'unexpected'])->isLegalEntity());
    }

    /**
     * Billing has not migrated every account yet — absent, nothing is
     * restricted, the same as every account behaved before this field
     * existed.
     */
    #[Test]
    public function a_missing_field_is_not_treated_as_a_legal_entity(): void
    {
        $this->assertFalse($this->profile([])->isLegalEntity());
    }

    /**
     * contract_id is billing's internal id for the contract, distinct from
     * contract_number (the "Договор №" string shown to the subscriber) — the
     * two must not be confused or fall back to each other.
     */
    #[Test]
    public function contract_id_and_contract_number_are_read_independently(): void
    {
        $profile = $this->profile(['contract_id' => '100145', 'contract_number' => 'D-100145']);

        $this->assertSame('100145', $profile->contractId());
        $this->assertSame('D-100145', $profile->contractNumber());
    }

    #[Test]
    public function a_missing_contract_id_is_null_rather_than_falling_back_to_contract_number(): void
    {
        $profile = $this->profile(['contract_number' => 'D-100145']);

        $this->assertNull($profile->contractId());
    }

    /**
     * Confirmed live on account 1336708 (2026-08-28) and cross-checked
     * against a real /tariff/connected capture (Smart 75/100/300 all follow
     * the same "<Plan> - <price> <currency word>" naming) — the price this
     * class already reports on its own via currentTariffCost() was showing
     * a second time whenever the raw name was displayed next to it.
     */
    #[Test]
    public function the_display_name_drops_a_price_suffix_that_matches_the_known_cost(): void
    {
        $profile = $this->profile([
            'curr_tariff_name' => 'Smart 50 - 125 000 сум',
            'tariff_price' => '125000',
        ]);

        $this->assertSame('Smart 50', $profile->currentTariffDisplayName());
    }

    /**
     * A plain space is what account 1336708 actually used, but Cyrillic
     * billing exports are also known to use a non-breaking space (U+00A0)
     * as the thousands separator — a name using it must still be
     * recognised, not silently left duplicated.
     */
    #[Test]
    public function the_price_suffix_is_matched_even_with_a_non_breaking_space_separator(): void
    {
        $profile = $this->profile([
            'curr_tariff_name' => "Smart 50 - 125\u{00A0}000 сум",
            'tariff_price' => '125000',
        ]);

        $this->assertSame('Smart 50', $profile->currentTariffDisplayName());
    }

    #[Test]
    public function a_name_with_no_matching_price_suffix_is_left_exactly_as_billing_sent_it(): void
    {
        $profile = $this->profile([
            'curr_tariff_name' => 'Home 100',
            'tariff_price' => '25000',
        ]);

        $this->assertSame('Home 100', $profile->currentTariffDisplayName());
    }

    /**
     * A name that happens to end in "- <number>" for an unrelated reason
     * (not billing's price-in-name convention) must not be mangled just
     * because it superficially resembles the pattern.
     */
    #[Test]
    public function a_trailing_number_that_does_not_match_the_cost_is_left_alone(): void
    {
        $profile = $this->profile([
            'curr_tariff_name' => 'Turbo - 200',
            'tariff_price' => '450000',
        ]);

        $this->assertSame('Turbo - 200', $profile->currentTariffDisplayName());
    }

    #[Test]
    public function no_tariff_name_means_no_display_name(): void
    {
        $this->assertNull($this->profile([])->currentTariffDisplayName());
    }

    #[Test]
    public function no_known_cost_leaves_the_name_untouched(): void
    {
        $profile = $this->profile(['curr_tariff_name' => 'Smart 50 - 125 000 сум']);

        $this->assertSame('Smart 50 - 125 000 сум', $profile->currentTariffDisplayName());
    }

    #[Test]
    public function the_next_tariffs_display_name_drops_its_own_price_suffix_the_same_way(): void
    {
        $profile = $this->profile([
            'next_tariff_name' => 'Smart 75 - 145 000 сум',
            'next_tariff_cost' => 14_500_000, // tiyin — nextTariffCost() divides by 100
        ]);

        $this->assertSame('Smart 75', $profile->nextTariffDisplayName());
    }

    /**
     * @param  array<string, mixed>  $body
     */
    private function profile(array $body): AbonentProfile
    {
        return AbonentProfile::from(new SolaResponse(200, $body));
    }
}
