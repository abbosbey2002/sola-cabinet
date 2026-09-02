<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\IpLocation;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class IpLocationTest extends TestCase
{
    protected function tearDown(): void
    {
        Cache::flush();

        parent::tearDown();
    }

    #[Test]
    public function it_skips_lookup_when_geo_is_disabled(): void
    {
        Http::fake();

        $geo = new IpLocation(active: false, timeoutSeconds: 2, cacheHours: 24);

        $this->assertNull($geo->formatLocation('8.8.8.8'));

        Http::assertNothingSent();
    }

    #[Test]
    public function it_does_not_lookup_private_addresses(): void
    {
        Http::fake();

        $geo = new IpLocation(active: true, timeoutSeconds: 2, cacheHours: 24);

        $this->assertNull($geo->formatLocation('10.0.0.5'));

        Http::assertNothingSent();
    }

    #[Test]
    public function it_formats_city_and_country_from_ipwho(): void
    {
        Http::fake([
            'https://ipwho.is/185.139.68.1*' => Http::response([
                'success' => true,
                'city' => 'Tashkent',
                'country' => 'Uzbekistan',
            ]),
        ]);

        $geo = new IpLocation(active: true, timeoutSeconds: 2, cacheHours: 24);

        $this->assertSame('Tashkent, Uzbekistan', $geo->formatLocation('185.139.68.1'));
    }

    #[Test]
    public function it_caches_successful_lookups(): void
    {
        Http::fake([
            'https://ipwho.is/185.139.68.1*' => Http::response([
                'success' => true,
                'city' => 'Tashkent',
                'country' => 'Uzbekistan',
            ]),
        ]);

        $geo = new IpLocation(active: true, timeoutSeconds: 2, cacheHours: 24);

        $geo->formatLocation('185.139.68.1');
        $geo->formatLocation('185.139.68.1');

        Http::assertSentCount(1);
    }
}
