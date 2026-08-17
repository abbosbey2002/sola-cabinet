<?php

declare(strict_types=1);

namespace App\Services\Sola;

use Carbon\CarbonImmutable;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\Request;

/**
 * A stand-in for the SOLA billing API, for local development only.
 *
 * Billing lives on SOLA's internal network (see API_IP). Off the VPN every
 * call ends in "cURL error 28", and because the cabinet is a thin client over
 * that API — there is no local database — every page then renders as a 503.
 * This class answers those calls in-process so the UI can be worked on without
 * the tunnel.
 *
 * It is installed as an HTTP client stub rather than as a second SolaClient:
 * the real client still runs unchanged — its Basic auth, its X-Access-Token
 * signature over the exact JSON bytes, its retry rules, its ConnectionException
 * handling — and only the wire underneath is replaced. Nothing on the
 * production path changes shape, and the switch is one env flag.
 *
 * Bodies follow the shapes the real API returns, as pinned by tests/Feature.
 * Values are derived from the request (account, day) through crc32 rather than
 * rand(), so a refresh shows the same numbers and screenshots stay stable.
 */
final class FakeSolaServer
{
    /** Every phone the fake accepts carries these two accounts. */
    private const ACCOUNTS = [
        ['accId' => 1001, 'abonType' => 2, 'abonName' => 'Alisher Karimov'],
        ['accId' => 1002, 'abonType' => 1, 'abonName' => 'Nodira Yusupova'],
    ];

    /** The one phone the fake refuses, so the "unknown subscriber" screen stays reachable. */
    private const UNKNOWN_PHONE = '998900000000';

    /** The one SMS code the fake rejects; any other four digits verify. */
    private const WRONG_CODE = '0000';

    /** The fields /abonent/info has always returned, per account. */
    private const PROFILES = [
        '1001' => [
            'name' => 'Alisher Karimov',
            'email' => 'a.karimov@example.uz',
            'phone' => '998901234567',
            'address' => 'Toshkent sh., Chilonzor t., Bunyodkor ko\'ch. 12',
            'status' => 'active',
            'contract_date' => '2019-09-05',
            'contract_number' => 'D-100145',
            'saldo' => 125000,
            'curr_tariff_id' => '839',
            'curr_tariff_name' => 'Home 100',
            // Costs are tiyin, as billing reports them.
            'curr_tariff_cost' => 2500000,
        ],
        '1002' => [
            'name' => 'Nodira Yusupova',
            'email' => 'n.yusupova@example.uz',
            'phone' => '998901234567',
            'address' => 'Toshkent sh., Yunusobod t., Amir Temur shoh ko\'ch. 4',
            'status' => 'active',
            'contract_date' => '2023-02-17',
            'contract_number' => 'D-204871',
            'saldo' => -18500,
            'curr_tariff_id' => '9',
            'curr_tariff_name' => 'Paket 30 kun',
            'curr_tariff_cost' => 1200000,
        ],
    ];

    /** What /tariff/available offers. Costs in tiyin, volumes in MiB. */
    private const TARIFFS = [
        ['tariff_id' => '839', 'tariff_name' => 'Home 100', 'cost' => 2500000, 'tspd' => '100', 'spdu' => 'Mbps', 'tprd' => '30', 'prdu' => 'DAY', 'vol' => '0'],
        ['tariff_id' => '9', 'tariff_name' => 'Paket 30 kun', 'cost' => 1200000, 'tspd' => '50', 'spdu' => 'Mbps', 'tprd' => '30', 'prdu' => 'DAY', 'vol' => '0'],
        // A padded name, exactly as billing sends some of them — the cabinet
        // has to keep matching this row by id, not by name.
        ['tariff_id' => '77', 'tariff_name' => 'Paket 5 soat ', 'cost' => 300000, 'tspd' => '500', 'spdu' => 'Mbps', 'tprd' => '5', 'prdu' => 'HOUR', 'vol' => '0'],
        ['tariff_id' => '412', 'tariff_name' => 'Turbo 200', 'cost' => 4500000, 'tspd' => '200', 'spdu' => 'Mbps', 'tprd' => '30', 'prdu' => 'DAY', 'vol' => '0'],
        ['tariff_id' => '515', 'tariff_name' => 'Limit 500 GB', 'cost' => 1800000, 'tspd' => '150', 'spdu' => 'Mbps', 'tprd' => '30', 'prdu' => 'DAY', 'vol' => '512000'],
    ];

    /** What a further device permit costs, in tiyin. */
    private const CONNECT_COST = '1500000';

    /** How many permits an account may hold, so the refusal path stays reachable. */
    private const DEVICE_LIMIT = 5;

    private const PAYMENT_SYSTEMS = ['PayNet', 'Payme', 'Click', 'Uzum'];

    public function __construct(
        private readonly CacheRepository $cache,
        private readonly string $baseUrl,
    ) {}

    /**
     * Point the HTTP client at this class instead of the network.
     */
    public function install(HttpFactory $http): void
    {
        $http->fake(fn (Request $request): ?PromiseInterface => $this->handle($request));
    }

    private function handle(Request $request): ?PromiseInterface
    {
        // The fake owns one API, not the whole HTTP client: anything else keeps
        // going to the network.
        if (! str_starts_with($request->url(), $this->baseUrl)) {
            return null;
        }

        /** @var array<string, mixed> $payload */
        $payload = (array) $request->data();

        return match ((string) parse_url($request->url(), PHP_URL_PATH)) {
            '/identify' => $this->identify($payload),
            '/verify' => $this->verify($payload),
            '/abonent/info' => $this->abonentInfo($payload),
            '/device/list' => $this->deviceList($payload),
            '/device/new' => $this->deviceNew($payload),
            '/device/delete' => $this->deviceDelete($payload),
            '/tariff/available' => $this->ok(['tariffs' => self::TARIFFS]),
            '/tariff/connected' => $this->tariffConnected($payload),
            '/tariff/connect' => $this->tariffConnect($payload),
            '/acct/payments' => $this->payments($payload),
            '/traffic/detail' => $this->trafficDetail($payload),
            // An endpoint the fake has not learned answers as an error rather
            // than pretending the call succeeded with an empty body.
            default => $this->fail(199, 404),
        };
    }

    // -- Authentication --------------------------------------------------

    /**
     * @param  array<string, mixed>  $payload
     */
    private function identify(array $payload): PromiseInterface
    {
        // 110 is the code billing sends for a phone it does not know.
        return (string) ($payload['phn'] ?? '') === self::UNKNOWN_PHONE
            ? $this->fail(110)
            : $this->ok(['accs' => self::ACCOUNTS]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function verify(array $payload): PromiseInterface
    {
        return (string) ($payload['smsCode'] ?? '') === self::WRONG_CODE
            ? $this->fail(120)
            : $this->ok(['result' => 'ok']);
    }

    // -- The subscriber --------------------------------------------------

    /**
     * @param  array<string, mixed>  $payload
     */
    private function abonentInfo(array $payload): PromiseInterface
    {
        $accountId = $this->accountId($payload);

        if ($accountId === '') {
            return $this->fail(110);
        }

        $profile = $this->profile($accountId);
        $devices = $this->devices($accountId);
        $next = $this->pendingTariff($accountId);

        return $this->ok($profile + [
            'device_count' => count($devices),
            // A permit with a lease is one that is actually carrying traffic.
            'device_active_count' => count(array_filter($devices, fn (array $device): bool => filled($device['ip']))),
            'next_tariff_name' => $next['tariff_name'] ?? null,
            'next_tariff_cost' => $next['cost'] ?? null,
            'next_charge_date' => CarbonImmutable::now()->addMonth()->startOfMonth()->format('Y-m-d'),
        ]);
    }

    /**
     * The record for an account id.
     *
     * Ids outside the seeded pair get one too, derived from the id itself. A
     * browser that still holds the account cookie from a session on the VPN
     * would otherwise land on a dashboard of empty dashes and read as a broken
     * fake rather than as an unknown account.
     *
     * @return array<string, mixed>
     */
    private function profile(string $accountId): array
    {
        if (isset(self::PROFILES[$accountId])) {
            return self::PROFILES[$accountId];
        }

        return array_replace(self::PROFILES['1001'], [
            'contract_number' => 'D-'.$accountId,
            'saldo' => (5 + $this->number($accountId, 'saldo') % 90) * 1000,
        ]);
    }

    // -- Device permits --------------------------------------------------

    /**
     * @param  array<string, mixed>  $payload
     */
    private function deviceList(array $payload): PromiseInterface
    {
        return $this->ok([
            'devices' => $this->devices($this->accountId($payload)),
            'connect_cost' => self::CONNECT_COST,
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function deviceNew(array $payload): PromiseInterface
    {
        $accountId = $this->accountId($payload);
        $devices = $this->devices($accountId);

        if (count($devices) >= self::DEVICE_LIMIT) {
            return $this->fail(130, message: 'Device limit reached ('.self::DEVICE_LIMIT.')');
        }

        $index = count($devices);

        $devices[] = [
            'permit_id' => (string) (100 + $index),
            'mac' => $this->mac($accountId, $index),
            'ip' => '10.0.0.'.(20 + $index),
            'connect_date' => CarbonImmutable::now()->format('Y-m-d'),
            'readonly' => false,
        ];

        $this->storeDevices($accountId, $devices);

        return $this->ok([]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function deviceDelete(array $payload): PromiseInterface
    {
        $accountId = $this->accountId($payload);
        $permitId = (string) ($payload['permit_id'] ?? '');

        $devices = $this->devices($accountId);

        $target = null;

        foreach ($devices as $device) {
            if ((string) $device['permit_id'] === $permitId) {
                $target = $device;
            }
        }

        if ($target === null) {
            return $this->fail(131, message: 'No such permit on this account');
        }

        // The line the contract itself was signed for cannot be released.
        if ($target['readonly']) {
            return $this->fail(132, message: 'This permit cannot be removed');
        }

        $this->storeDevices($accountId, array_values(array_filter(
            $devices,
            fn (array $device): bool => (string) $device['permit_id'] !== $permitId,
        )));

        return $this->ok([]);
    }

    // -- Tariffs ---------------------------------------------------------

    /**
     * @param  array<string, mixed>  $payload
     */
    private function tariffConnect(array $payload): PromiseInterface
    {
        $tariffId = (string) ($payload['tariff_id'] ?? '');

        $tariff = null;

        foreach (self::TARIFFS as $candidate) {
            if ($candidate['tariff_id'] === $tariffId) {
                $tariff = $candidate;
            }
        }

        if ($tariff === null) {
            return $this->fail(140, message: 'Unknown tariff');
        }

        // Recorded rather than applied: billing switches the tariff at the next
        // billing date, and the dashboard reads it back as "next tariff".
        $this->cache->forever($this->key('tariff', $this->accountId($payload)), $tariff);

        return $this->ok(['code' => 0]);
    }

    /**
     * The tariff in force, with the day it started.
     *
     * One row, matching /abonent/info's curr_tariff_id — the shape the live API
     * returned when it was probed. The real one sends a timestamp rather than a
     * date in date_begin, so the fake does too: the cabinet has to keep parsing
     * it either way.
     *
     * @param  array<string, mixed>  $payload
     */
    private function tariffConnected(array $payload): PromiseInterface
    {
        $accountId = $this->accountId($payload);

        if ($accountId === '') {
            return $this->fail(110);
        }

        $profile = $this->profile($accountId);

        // Derived from the id rather than rand(), so a refresh keeps the date.
        $startedAt = CarbonImmutable::now()
            ->startOfMonth()
            ->subMonths(1 + $this->number($accountId, 'tariff-start') % 6)
            ->addDays($this->number($accountId, 'tariff-day') % 20)
            ->setTime(16, 34, 27);

        return $this->ok(['tariffs' => [[
            'tariff_id' => (string) $profile['curr_tariff_id'],
            'tariff_name' => (string) $profile['curr_tariff_name'],
            'date_begin' => $startedAt->format('Y-m-d H:i:s'),
            // Null on the tariff in force; billing fills it once it ends.
            'date_end' => null,
            'tariff_isoff' => '0',
        ]]]);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function pendingTariff(string $accountId): ?array
    {
        /** @var array<string, mixed>|null $tariff */
        $tariff = $this->cache->get($this->key('tariff', $accountId));

        return $tariff;
    }

    // -- History ---------------------------------------------------------

    /**
     * Roughly two payments a month, on stable days.
     *
     * @param  array<string, mixed>  $payload
     */
    private function payments(array $payload): PromiseInterface
    {
        $accountId = $this->accountId($payload);
        $rows = [];

        foreach ($this->daysOf((string) ($payload['pay_month'] ?? '')) as $day) {
            if ($this->number($accountId, $day, 'pay') % 14 !== 3) {
                continue;
            }

            $minute = $this->number($accountId, $day, 'min');

            $rows[] = [
                'payment_id' => (string) (1_000_000 + $this->number($accountId, $day, 'id') % 99_999),
                'payment_date' => $day.' '.sprintf('%02d:%02d:09', 9 + $minute % 10, $minute % 60),
                // Tiyin: 12 000 – 45 000 so'm, in whole thousands.
                'amount' => (12 + $this->number($accountId, $day, 'sum') % 34) * 100_000,
                'payment_system' => self::PAYMENT_SYSTEMS[$this->number($accountId, $day, 'sys') % count(self::PAYMENT_SYSTEMS)],
                'payment_status' => "to'langan",
            ];
        }

        return $this->ok(['payments' => $rows]);
    }

    /**
     * One session a day, which is enough for the totals and the share bars.
     *
     * @param  array<string, mixed>  $payload
     */
    private function trafficDetail(array $payload): PromiseInterface
    {
        $accountId = $this->accountId($payload);
        $rows = [];

        foreach ($this->daysOf((string) ($payload['detail_month'] ?? '')) as $day) {
            $hour = $this->number($accountId, $day, 'hour') % 24;

            $rows[] = [
                'event_time' => $day.' '.sprintf('%02d:%02d:00', $hour, $this->number($accountId, $day, 'minute') % 60),
                // Bytes: a few hundred MiB down, a fraction of that up.
                'traffic_input' => (200 + $this->number($accountId, $day, 'in') % 2_800) * 1024 * 1024,
                'traffic_output' => (20 + $this->number($accountId, $day, 'out') % 300) * 1024 * 1024,
            ];
        }

        return $this->ok(['detail' => $rows]);
    }

    /**
     * The days of a "Y-m" month, never past today — billing has no future rows.
     *
     * @return list<string>
     */
    private function daysOf(string $month): array
    {
        if (preg_match('/^\d{4}-\d{2}$/', $month) !== 1) {
            return [];
        }

        $cursor = CarbonImmutable::parse($month.'-01')->startOfDay();
        $last = $cursor->endOfMonth()->startOfDay()->min(CarbonImmutable::now()->startOfDay());

        $days = [];

        while ($cursor->lessThanOrEqualTo($last)) {
            $days[] = $cursor->format('Y-m-d');
            $cursor = $cursor->addDay();
        }

        return $days;
    }

    // -- State -----------------------------------------------------------

    /**
     * The account's permits. Added and released ones survive between requests,
     * so the device block behaves like the real thing; `php artisan cache:clear`
     * puts it back to the seed.
     *
     * @return list<array<string, mixed>>
     */
    private function devices(string $accountId): array
    {
        /** @var list<array<string, mixed>> */
        return $this->cache->get($this->key('devices', $accountId)) ?? $this->seedDevices($accountId);
    }

    /**
     * @param  list<array<string, mixed>>  $devices
     */
    private function storeDevices(string $accountId, array $devices): void
    {
        $this->cache->forever($this->key('devices', $accountId), $devices);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function seedDevices(string $accountId): array
    {
        // The first permit is the line the contract was signed for: billing
        // marks it read-only, and the cabinet must not offer to delete it.
        $contractLine = [
            'permit_id' => '77',
            'mac' => 'AA:BB:CC:DD:EE:FF',
            'ip' => '10.0.0.5',
            'connect_date' => self::PROFILES[$accountId]['contract_date'] ?? '2019-09-05',
            'readonly' => true,
        ];

        if ($accountId !== '1001') {
            return [$contractLine];
        }

        return [
            $contractLine,
            [
                'permit_id' => '78',
                'mac' => '9C:2A:70:11:04:B3',
                'ip' => '10.0.0.18',
                'connect_date' => '2026-01-15',
                'readonly' => false,
            ],
            // A permit with no lease — it counts towards the total but not
            // towards "active", which is the pair the dashboard shows.
            [
                'permit_id' => '79',
                'mac' => '3C:5A:B4:0E:77:21',
                'ip' => '',
                'connect_date' => '2026-06-02',
                'readonly' => false,
            ],
        ];
    }

    private function key(string $bucket, string $accountId): string
    {
        return "sola-fake:{$bucket}:{$accountId}";
    }

    // -- Helpers ---------------------------------------------------------

    /**
     * @param  array<string, mixed>  $payload
     */
    private function accountId(array $payload): string
    {
        return (string) ($payload['acc_id'] ?? '');
    }

    /**
     * A stable number for a given account/day/purpose. crc32 rather than rand()
     * so the same page renders the same figures on every refresh.
     */
    private function number(string ...$parts): int
    {
        return (int) crc32(implode('|', $parts));
    }

    private function mac(string $accountId, int $index): string
    {
        $seed = $this->number($accountId, (string) $index, 'mac');

        $octets = ['02'];

        for ($byte = 0; $byte < 5; $byte++) {
            $octets[] = sprintf('%02X', ($seed >> ($byte * 5)) & 0xFF);
        }

        return implode(':', $octets);
    }

    /**
     * @param  array<string, mixed>  $body
     */
    private function ok(array $body): PromiseInterface
    {
        return HttpFactory::response($body, 200);
    }

    /**
     * The error shape the real API uses: a non-200 with a code the cabinet
     * translates through the "errors" language file.
     */
    private function fail(int $code, int $status = 400, string $message = ''): PromiseInterface
    {
        return HttpFactory::response([
            'code' => $code,
            'errMsg' => $message !== '' ? $message : 'fake billing: error '.$code,
        ], $status);
    }
}
