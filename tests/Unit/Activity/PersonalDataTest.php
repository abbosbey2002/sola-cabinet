<?php

declare(strict_types=1);

namespace Tests\Unit\Activity;

use App\Support\Admin\PersonalData;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class PersonalDataTest extends TestCase
{
    #[Test]
    public function a_masked_phone_keeps_only_the_operator_and_the_last_two_digits(): void
    {
        $masked = new PersonalData(false);

        $this->assertSame('+998 90 *** ** 67', $masked->phone('998901234567'));
        $this->assertSame('+998 90 *** ** 67', $masked->phone('+998 (90) 123-45-67'));
        $this->assertSame('*******89', $masked->phone('123456789'));
        $this->assertNull($masked->phone(null));
    }

    #[Test]
    public function an_unmasked_phone_is_only_formatted(): void
    {
        $this->assertSame('+998 90 123 45 67', (new PersonalData(true))->phone('998901234567'));
    }

    #[Test]
    public function a_masked_name_keeps_each_words_first_letter(): void
    {
        $masked = new PersonalData(false);

        $this->assertSame('T***** T*****', $masked->name('Tester Testov'));
        $this->assertSame('Ш****** Р*****', $masked->name('Шукуров Рустам'));
        $this->assertNull($masked->name('  '));
    }

    #[Test]
    public function a_contract_number_that_is_really_a_phone_is_masked_like_one(): void
    {
        $masked = new PersonalData(false);

        $this->assertSame('+998 93 *** ** 11', $masked->login('998935550011'));
        $this->assertSame('D-1004', $masked->login('D-1004'));
    }
}
