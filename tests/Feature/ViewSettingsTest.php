<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The theme / text-size panel.
 *
 * These are display preferences with no server side at all — the value lives in
 * localStorage and the whole mechanism hangs off two root attributes. What can
 * still break server-side is the markup contract the client script depends on,
 * so that is what is pinned here: the radio names, the three values each one
 * offers, and the inline boot script that stops the theme flashing.
 */
final class ViewSettingsTest extends TestCase
{
    private const ACCOUNT_ID = '1001';

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
    }

    #[Test]
    public function the_guest_screens_carry_the_panel(): void
    {
        $this->fakeIdentify();

        $response = $this->get('/auth/login');

        $response->assertOk();
        $this->assertPanelContract($response->getContent());
    }

    #[Test]
    public function the_cabinet_carries_the_panel(): void
    {
        $this->fakeCabinet();

        $response = $this->verifiedSubscriber()->get('/');

        $response->assertOk();
        $this->assertPanelContract($response->getContent());
    }

    /**
     * The boot script has to be inline in <head>. Moved into the bundle it would
     * run after first paint, and every dark-theme subscriber would get a white
     * flash on every navigation.
     */
    #[Test]
    public function the_theme_is_restored_before_the_stylesheet_paints(): void
    {
        $this->fakeIdentify();

        $content = $this->get('/auth/login')->getContent();

        $boot = strpos($content, "localStorage.getItem('sola-theme')");
        $styles = strpos($content, '<link rel="stylesheet"');

        $this->assertNotFalse($boot, 'the inline theme boot script is missing');
        $this->assertNotFalse($styles, 'no stylesheet link was rendered');
        $this->assertLessThan($styles, $boot, 'the boot script must run before the stylesheet');
    }

    private function assertPanelContract(string $content): void
    {
        // Both controls are three-state, and the empty value matters: it is what
        // removes the attribute and restores "follow the system" / "normal".
        foreach (['light', 'dark', ''] as $value) {
            $this->assertStringContainsString(
                '<input type="radio" name="sola-theme" value="'.$value.'">',
                $content,
            );
        }

        foreach (['', 'lg', 'xl'] as $value) {
            $this->assertStringContainsString(
                '<input type="radio" name="sola-text" value="'.$value.'">',
                $content,
            );
        }

        // The panel rides on the shared disclosure module; without this hook it
        // renders but never opens.
        $this->assertStringContainsString('data-disclosure-trigger', $content);
    }

    private function fakeIdentify(): void
    {
        Http::fake([
            '*/identify' => Http::response(['accs' => [['accId' => self::ACCOUNT_ID]]]),
        ]);
    }

    private function fakeCabinet(): void
    {
        Http::fake([
            '*/abonent/info' => Http::response([
                'name' => 'Tester Testov',
                'saldo' => '19000',
                'curr_tariff_id' => '4',
                'curr_tariff_name' => 'Paket 2 soat',
                'device_count' => '1',
                'device_active_count' => '0',
            ]),
            '*/device/list' => Http::response(['devices' => [], 'connect_cost' => '-1']),
            '*/tariff/available' => Http::response(['tariffs' => []]),
            '*/identify' => Http::response(['accs' => [['accId' => self::ACCOUNT_ID]]]),
            '*' => Http::response([]),
        ]);
    }

    private function verifiedSubscriber(): self
    {
        return $this->withCookies([
            'verify' => '1',
            'account' => self::ACCOUNT_ID,
            'login' => '998901234567',
            'phone' => '998901234567',
            'data' => json_encode(['type' => 2]),
        ]);
    }
}
