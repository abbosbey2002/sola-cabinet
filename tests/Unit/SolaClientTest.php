<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Exceptions\SolaUnavailableException;
use App\Services\Sola\SolaClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The API rejects any request whose X-Access-Token does not match the body it
 * received, so these assertions guard the one thing a framework upgrade can
 * break invisibly: the bytes on the wire.
 */
final class SolaClientTest extends TestCase
{
    private const USERNAME = 'test';

    private const SECRET = 'secret-key';

    #[Test]
    public function the_access_token_signs_the_exact_body_that_is_sent(): void
    {
        Http::fake(['*' => Http::response(['name' => 'Tester'])]);

        $this->client()->abonentInfo('1001');

        Http::assertSent(function (Request $request): bool {
            $body = $request->body();

            $this->assertSame(
                md5(self::USERNAME.' '.self::SECRET.' '.$body),
                $request->header('X-Access-Token')[0],
            );

            return true;
        });
    }

    #[Test]
    public function the_body_keeps_the_field_order_the_api_has_always_received(): void
    {
        Http::fake(['*' => Http::response([])]);

        $this->client()->payments('1001', '2026-07-01', '2026-07-31');

        Http::assertSent(fn (Request $request): bool => $request->body() === json_encode([
            'acc_id' => '1001',
            'pay_begin' => '2026-07-01',
            'pay_end' => '2026-07-31',
            'lang' => 'ru',
        ]));
    }

    #[Test]
    public function traffic_detail_sends_a_start_end_range_not_a_month(): void
    {
        Http::fake(['*' => Http::response([])]);

        $this->client()->trafficDetail('1001', '2026-07-01', '2026-07-31');

        Http::assertSent(fn (Request $request): bool => $request->body() === json_encode([
            'acc_id' => '1001',
            'detail_start' => '2026-07-01',
            'detail_end' => '2026-07-31',
            'lang' => 'ru',
        ]));
    }

    #[Test]
    public function connecting_a_tariff_is_sent_without_a_lang_field(): void
    {
        Http::fake(['*' => Http::response([])]);

        $this->client()->connectTariff('1001', 42, '2026-09-01');

        Http::assertSent(fn (Request $request): bool => $request->body() === json_encode([
            'acc_id' => '1001',
            'tariff_id' => 42,
            'tariff_conndate' => '2026-09-01',
        ]));
    }

    #[Test]
    public function requests_carry_basic_auth_and_a_json_content_type(): void
    {
        Http::fake(['*' => Http::response([])]);

        $this->client()->devices('1001');

        Http::assertSent(function (Request $request): bool {
            $this->assertSame(
                'Basic '.base64_encode(self::USERNAME.':secret'),
                $request->header('Authorization')[0],
            );
            $this->assertSame('application/json', $request->header('Content-Type')[0]);

            return true;
        });
    }

    #[Test]
    public function the_endpoint_path_has_no_double_slash(): void
    {
        Http::fake(['*' => Http::response([])]);

        $this->client()->abonentInfo('1001');

        Http::assertSent(fn (Request $request): bool => $request->url() === 'http://sola.test/abonent/info');
    }

    #[Test]
    public function a_business_error_is_returned_rather_than_thrown(): void
    {
        Http::fake(['*' => Http::response(['code' => 120, 'errMsg' => 'bad code'], 400)]);

        $response = $this->client()->verify('998901234567', '0000');

        $this->assertTrue($response->failed());
        $this->assertSame(120, $response->errorCode());
        $this->assertSame('bad code', $response->errorMessage());
    }

    /**
     * The test above uses verify(), which is a mutation and therefore never
     * gets a retry policy — so it could never catch this. Reads do get one, and
     * PendingRequest::retry() throws on any non-2xx once tries > 1 unless it is
     * told otherwise. That turned every business error on a read into an
     * uncaught RequestException and a 500 page.
     */
    #[Test]
    public function a_business_error_on_a_retried_read_is_returned_rather_than_thrown(): void
    {
        Http::fake(['*' => Http::response(['code' => 114, 'errMsg' => 'no such account'], 400)]);

        $response = $this->client()->abonentInfo('0');

        $this->assertTrue($response->failed());
        $this->assertSame(400, $response->status);
        $this->assertSame(114, $response->errorCode());
        $this->assertSame('no such account', $response->errorMessage());
    }

    #[Test]
    public function a_business_error_on_a_read_is_not_retried(): void
    {
        $attempts = 0;

        Http::fake(function () use (&$attempts) {
            $attempts++;

            return Http::response(['code' => 114, 'errMsg' => 'no such account'], 400);
        });

        $this->client()->abonentInfo('0');

        // Retries exist for dropped connections. The API having answered
        // "that account does not exist" is a final answer, not a blip.
        $this->assertSame(1, $attempts);
    }

    #[Test]
    public function an_unreachable_api_raises_a_typed_exception(): void
    {
        Http::fake(fn () => throw new ConnectionException('cURL error 7'));

        $this->expectException(SolaUnavailableException::class);

        $this->client()->abonentInfo('1001');
    }

    #[Test]
    public function a_read_is_retried_but_a_mutation_is_not(): void
    {
        $attempts = 0;

        Http::fake(function () use (&$attempts) {
            $attempts++;

            throw new ConnectionException('cURL error 7');
        });

        // Reads are safe to repeat.
        try {
            $this->client()->devices('1001');
        } catch (SolaUnavailableException) {
            //
        }

        $this->assertSame(2, $attempts, 'a read should be retried once');

        $attempts = 0;

        // Adding a device twice would hand out two permits.
        try {
            $this->client()->addDevice('1001');
        } catch (SolaUnavailableException) {
            //
        }

        $this->assertSame(1, $attempts, 'a mutation must not be retried');
    }

    private function client(): SolaClient
    {
        return new SolaClient(app(Factory::class), [
            'base_url' => 'http://sola.test/',
            'username' => self::USERNAME,
            'password' => 'secret',
            'secret_key' => self::SECRET,
            'timeout' => 10,
            'connect_timeout' => 3,
            'retry_times' => 2,
            'retry_sleep_ms' => 1,
        ]);
    }
}
