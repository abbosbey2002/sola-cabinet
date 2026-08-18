<?php

declare(strict_types=1);

namespace App\Services\Sola;

use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\Request;

/**
 * Bypasses the SMS step only, so the cabinet can be driven against one real
 * billing account without waiting on a test-environment SMS that never
 * arrives.
 *
 * Unlike FakeSolaServer, this stub owns exactly two endpoints — /identify and
 * /verify. Every other call (abonent/info, device/list, payments, …) falls
 * through to the real network, so the account it logs into is read from
 * live billing, not invented data. It therefore requires the VPN to be up.
 */
final class FakeLoginServer
{
    /**
     * The one account this bypass logs into. Chosen by whoever is testing —
     * not configurable, so the bypass cannot drift onto a subscriber's real
     * account by a typo in .env.
     */
    private const ACCOUNT_ID = 1336708;

    /** Matches AbonentSession::TYPE_PERMANENT — the fewest restrictions, so nothing under test is gated off. */
    private const ABON_TYPE = 2;

    public function __construct(
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
        if (! str_starts_with($request->url(), $this->baseUrl)) {
            return null;
        }

        return match ((string) parse_url($request->url(), PHP_URL_PATH)) {
            '/identify' => $this->identify(),
            '/verify' => $this->verify(),
            // Anything else — real account data, over the real network.
            default => null,
        };
    }

    private function identify(): PromiseInterface
    {
        return HttpFactory::response([
            'accs' => [[
                'accId' => self::ACCOUNT_ID,
                'abonType' => self::ABON_TYPE,
                'abonName' => 'Test account '.self::ACCOUNT_ID,
            ]],
        ], 200);
    }

    private function verify(): PromiseInterface
    {
        return HttpFactory::response(['result' => 'ok'], 200);
    }
}
