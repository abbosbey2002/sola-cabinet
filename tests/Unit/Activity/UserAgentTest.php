<?php

declare(strict_types=1);

namespace Tests\Unit\Activity;

use App\Support\Activity\UserAgent;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class UserAgentTest extends TestCase
{
    /**
     * @return iterable<string, array{string, array<int, ?string>}>
     */
    public static function agents(): iterable
    {
        yield 'Android Chrome' => [
            'Mozilla/5.0 (Linux; Android 13; SM-A515F) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Mobile Safari/537.36',
            ['Chrome', '140.0', 'Android', '13', 'mobile', 'SM-A515F'],
        ];
        yield 'Android reduced UA hides the model' => [
            'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Mobile Safari/537.36',
            ['Chrome', '140.0', 'Android', '10', 'mobile', null],
        ];
        yield 'Samsung Internet is not Chrome' => [
            'Mozilla/5.0 (Linux; Android 14; SM-S918B) AppleWebKit/537.36 (KHTML, like Gecko) SamsungBrowser/25.0 Chrome/121.0.0.0 Mobile Safari/537.36',
            ['Samsung', '25.0', 'Android', '14', 'mobile', 'SM-S918B'],
        ];
        yield 'iPhone Safari' => [
            'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1',
            ['Safari', '18.5', 'iOS', '18.5', 'mobile', 'iPhone'],
        ];
        yield 'iPad is a tablet' => [
            'Mozilla/5.0 (iPad; CPU OS 17_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.1 Mobile/15E148 Safari/604.1',
            ['Safari', '17.1', 'iOS', '17.1', 'tablet', 'iPad'],
        ];
        yield 'Edge on Windows is not Chrome' => [
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0',
            ['Edge', '140.0', 'Windows', '10.0', 'desktop', null],
        ];
        yield 'Yandex' => [
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 YaBrowser/24.4.0.0 Safari/537.36',
            ['Yandex', '24.4', 'Windows', '10.0', 'desktop', null],
        ];
        yield 'Firefox on macOS' => [
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:131.0) Gecko/20100101 Firefox/131.0',
            ['Firefox', '131.0', 'macOS', '10.15', 'desktop', null],
        ];
        yield 'a crawler' => [
            'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)',
            [null, null, null, null, 'bot', null],
        ];
    }

    /**
     * @param  array<int, ?string>  $expected  browser, version, os, os version, device type, model
     */
    #[Test]
    #[DataProvider('agents')]
    public function it_reads_the_families_the_segments_group_by(string $header, array $expected): void
    {
        $agent = UserAgent::parse($header);

        $this->assertSame($expected, [
            $agent->browser, $agent->browserVersion, $agent->os, $agent->osVersion, $agent->deviceType, $agent->deviceModel,
        ]);
    }

    #[Test]
    public function no_header_is_all_null(): void
    {
        $agent = UserAgent::parse(null);

        $this->assertNull($agent->browser);
        $this->assertNull($agent->deviceType);
    }
}
