<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\Sola\SolaResponse;
use App\Support\AbonentProfile;
use App\Support\DashboardView;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class DashboardViewTest extends TestCase
{
    #[Test]
    public function one_off_subscribers_get_the_one_time_layout_kind(): void
    {
        $view = DashboardView::make(
            $this->profile(['saldo' => '1000', 'curr_tariff_name' => 'Guest']),
            null,
            isOneTime: true,
        );

        $this->assertSame(DashboardView::KIND_ONE_TIME, $view->kind);
        $this->assertFalse($view->showTariff);
        $this->assertTrue($view->canTopUp);
        $this->assertSame(3, $view->metricColumnCount());
    }

    #[Test]
    public function legal_entities_get_the_legal_layout_and_cannot_top_up(): void
    {
        config(['iwon.active' => true]);

        $view = DashboardView::make(
            $this->profile([
                'saldo' => '1000',
                'curr_tariff_name' => 'Corp',
                'legal' => 'Юридическое лицо',
            ]),
            null,
            isOneTime: false,
        );

        $this->assertSame(DashboardView::KIND_LEGAL, $view->kind);
        $this->assertTrue($view->showTariff);
        $this->assertFalse($view->canTopUp);
        $this->assertFalse($view->canSwitchTariff);
    }

    #[Test]
    public function permanent_individuals_get_full_dashboard_capabilities(): void
    {
        config(['iwon.active' => true]);

        $view = DashboardView::make(
            $this->profile([
                'saldo' => '50000',
                'curr_tariff_name' => 'Home 100',
                'legal' => 'Физическое лицо',
            ]),
            null,
            isOneTime: false,
        );

        $this->assertSame(DashboardView::KIND_PERMANENT, $view->kind);
        $this->assertTrue($view->showTariff);
        $this->assertTrue($view->canTopUp);
        $this->assertTrue($view->canSwitchTariff);
        $this->assertSame(4, $view->metricColumnCount());
    }

    private function profile(array $body): AbonentProfile
    {
        return AbonentProfile::from(new SolaResponse(200, $body));
    }
}
