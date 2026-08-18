<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\Sola\FakeLoginServer;
use App\Services\Sola\SolaClient;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\StrayRequestException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The narrower stand-in for billing that skips only the SMS step (see
 * config/sola.php, "Login Bypass"). Unlike FakeSolaServer, everything past
 * /identify and /verify has to reach the real network — that is the entire
 * point, so a subscriber's real data is what the cabinet ends up showing.
 */
final class FakeLoginServerTest extends TestCase
{
    private const BASE_URL = 'http://billing.test:808';

    #[Test]
    public function identify_always_answers_with_the_pinned_test_account(): void
    {
        $sola = $this->client();

        $response = $sola->identify('998901234567');

        $this->assertTrue($response->successful());

        $accounts = (array) $response->get('accs');

        $this->assertCount(1, $accounts);
        $this->assertSame(1336708, $accounts[0]['accId']);
    }

    #[Test]
    public function verify_accepts_any_code(): void
    {
        $sola = $this->client();

        $this->assertTrue($sola->verify('998901234567', '0000')->successful());
        $this->assertTrue($sola->verify('998901234567', '9999')->successful());
    }

    /**
     * Everything past login has to reach the real API — a fake dashboard
     * defeats the purpose of pinning to a real test account.
     */
    #[Test]
    public function other_endpoints_are_left_to_the_real_network(): void
    {
        $http = new Factory;

        (new FakeLoginServer(self::BASE_URL))->install($http);
        $http->preventStrayRequests();

        $sola = new SolaClient($http, [
            'base_url' => self::BASE_URL,
            'username' => 'test',
            'password' => 'secret',
            'secret_key' => 'secret-key',
        ]);

        $this->expectException(StrayRequestException::class);

        $sola->abonentInfo('1336708');
    }

    private function client(): SolaClient
    {
        $http = new Factory;

        (new FakeLoginServer(self::BASE_URL))->install($http);

        return new SolaClient($http, [
            'base_url' => self::BASE_URL,
            'username' => 'test',
            'password' => 'secret',
            'secret_key' => 'secret-key',
        ]);
    }
}
