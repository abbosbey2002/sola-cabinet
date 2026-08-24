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
     * @param  array<string, mixed>  $body
     */
    private function profile(array $body): AbonentProfile
    {
        return AbonentProfile::from(new SolaResponse(200, $body));
    }
}
