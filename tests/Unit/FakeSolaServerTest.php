<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\Sola\FakeSolaServer;
use App\Services\Sola\SolaClient;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\StrayRequestException;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The offline stand-in for billing (see DOCKER.md, "VPN'siz ishlash").
 *
 * A fake that answers with the wrong shape is worse than no fake at all: the
 * cabinet would render blanks and the developer would go hunting in the views.
 * So these assertions pin the contract the real API is read through — the keys
 * the controllers and templates ask for, and the mutations that have to stick.
 */
final class FakeSolaServerTest extends TestCase
{
    private const BASE_URL = 'http://billing.test:808';

    private const ACCOUNT_ID = '1001';

    #[Test]
    public function the_dashboard_endpoints_answer_in_the_shape_the_cabinet_reads(): void
    {
        $sola = $this->client();

        $info = $sola->abonentInfo(self::ACCOUNT_ID);

        $this->assertTrue($info->successful());
        $this->assertNotNull($info->get('name'));
        $this->assertNotNull($info->get('curr_tariff_id'));
        $this->assertIsNumeric($info->get('saldo'));

        $devices = $sola->devices(self::ACCOUNT_ID);

        $this->assertTrue($devices->successful());
        $this->assertNotSame([], (array) $devices->get('devices'));

        foreach ((array) $devices->get('devices') as $device) {
            // The columns tariff.blade.php renders for every row.
            $this->assertSame(
                ['permit_id', 'mac', 'ip', 'connect_date', 'readonly'],
                array_keys($device),
            );
        }

        $tariffs = (array) $sola->availableTariffs(self::ACCOUNT_ID)->get('tariffs');

        $this->assertNotSame([], $tariffs);

        foreach ($tariffs as $tariff) {
            // The view reads all five terms without a null guard.
            foreach (['tariff_id', 'tariff_name', 'cost', 'tspd', 'spdu', 'tprd', 'prdu', 'vol'] as $key) {
                $this->assertArrayHasKey($key, $tariff);
            }
        }
    }

    /**
     * History is read a month at a time and trimmed to the requested days, so
     * every row has to fall inside the month it was asked for — and billing
     * never reports a session that has not happened yet.
     */
    #[Test]
    public function history_stays_inside_the_month_it_was_asked_for(): void
    {
        $sola = $this->client();
        $month = now()->format('Y-m');

        $rows = (array) $sola->trafficDetail(self::ACCOUNT_ID, $month)->get('detail');

        $this->assertNotSame([], $rows);

        foreach ($rows as $row) {
            $this->assertStringStartsWith($month, (string) $row['event_time']);
            $this->assertLessThanOrEqual(CarbonImmutable::now()->endOfDay(), CarbonImmutable::parse($row['event_time']));
        }

        // A month the subscriber has not lived through yet is simply empty.
        $future = now()->addYear()->format('Y-m');

        $this->assertSame([], (array) $sola->trafficDetail(self::ACCOUNT_ID, $future)->get('detail'));
        $this->assertSame([], (array) $sola->payments(self::ACCOUNT_ID, $future)->get('payments'));
    }

    #[Test]
    public function an_added_permit_survives_into_the_next_request(): void
    {
        $sola = $this->client();

        $before = count((array) $sola->devices(self::ACCOUNT_ID)->get('devices'));

        $this->assertTrue($sola->addDevice(self::ACCOUNT_ID)->successful());

        $devices = (array) $sola->devices(self::ACCOUNT_ID)->get('devices');

        $this->assertCount($before + 1, $devices);

        $added = end($devices);

        $this->assertTrue($sola->deleteDevice(self::ACCOUNT_ID, (string) $added['permit_id'])->successful());
        $this->assertCount($before, (array) $sola->devices(self::ACCOUNT_ID)->get('devices'));
    }

    /**
     * The contract's own line is read-only in billing. If the fake let it go,
     * the delete link would look fine locally and fail on the real API.
     */
    #[Test]
    public function the_contract_line_cannot_be_released(): void
    {
        $sola = $this->client();

        $devices = (array) $sola->devices(self::ACCOUNT_ID)->get('devices');
        $readonly = null;

        foreach ($devices as $device) {
            if ($device['readonly']) {
                $readonly = $device;
            }
        }

        $this->assertNotNull($readonly, 'the seed should include a read-only permit');

        $response = $sola->deleteDevice(self::ACCOUNT_ID, (string) $readonly['permit_id']);

        $this->assertTrue($response->failed());
        $this->assertNotNull($response->errorMessage());
        $this->assertCount(count($devices), (array) $sola->devices(self::ACCOUNT_ID)->get('devices'));
    }

    /**
     * A connected tariff takes effect at the next billing date, so it comes
     * back as the *next* tariff and leaves the current one alone.
     */
    #[Test]
    public function a_connected_tariff_is_reported_as_the_next_one(): void
    {
        $sola = $this->client();

        $current = $sola->abonentInfo(self::ACCOUNT_ID)->get('curr_tariff_name');

        $this->assertTrue($sola->connectTariff(self::ACCOUNT_ID, 412, now()->format('Y-m-d'))->successful());

        $info = $sola->abonentInfo(self::ACCOUNT_ID);

        $this->assertSame('Turbo 200', $info->get('next_tariff_name'));
        $this->assertSame($current, $info->get('curr_tariff_name'));
    }

    /**
     * A browser that still holds the account cookie from a session on the VPN
     * carries an id the fake never seeded. It has to answer for that id too —
     * otherwise the first page after switching SOLA_FAKE on is a dashboard of
     * empty dashes, which reads as a broken fake.
     */
    #[Test]
    public function an_account_id_left_over_from_a_real_session_still_gets_a_record(): void
    {
        $info = $this->client()->abonentInfo('1328593');

        $this->assertTrue($info->successful());
        $this->assertNotNull($info->get('name'));
        $this->assertNotNull($info->get('curr_tariff_name'));
        $this->assertSame('D-1328593', $info->get('contract_number'));
    }

    /**
     * Both error screens have to stay reachable without the VPN, and the codes
     * are the ones the real API sends — they are looked up in lang/*.
     */
    #[Test]
    public function the_reserved_phone_and_sms_code_are_refused(): void
    {
        $sola = $this->client();

        $this->assertSame(110, $sola->identify('998900000000')->errorCode());
        $this->assertSame(120, $sola->verify('998901234567', '0000')->errorCode());

        $this->assertTrue($sola->identify('998901234567')->successful());
        $this->assertTrue($sola->verify('998901234567', '1234')->successful());
    }

    /**
     * The fake owns one API, not the whole HTTP client — anything else has to
     * keep going to the network, or an unrelated call would silently get
     * billing's answer.
     */
    #[Test]
    public function requests_to_other_hosts_are_left_alone(): void
    {
        $http = new Factory;

        $this->server()->install($http);
        $http->preventStrayRequests();

        $this->expectException(StrayRequestException::class);

        $http->get('https://example.test/somewhere');
    }

    private function client(): SolaClient
    {
        $http = new Factory;

        $this->server()->install($http);

        return new SolaClient($http, [
            'base_url' => self::BASE_URL,
            'username' => 'test',
            'password' => 'secret',
            'secret_key' => 'secret-key',
        ]);
    }

    private function server(): FakeSolaServer
    {
        return new FakeSolaServer(Cache::store('array'), self::BASE_URL);
    }
}
