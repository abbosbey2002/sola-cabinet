<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class AuthTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
    }

    #[Test]
    public function the_login_screen_is_reachable(): void
    {
        $this->get(route('login'))->assertOk();
    }

    #[Test]
    public function a_known_phone_is_sent_an_sms_and_lands_on_the_verify_screen(): void
    {
        Http::fake(['*/identify' => Http::response([
            'accs' => [['accId' => 1001, 'abonType' => 2, 'abonName' => 'Tester', 'login' => 'TESTER01']],
        ])]);

        $response = $this->post(route('login'), ['login' => '+998 901234567']);

        $response->assertOk();
        // A single account is selected straight away, so the picker is skipped.
        $response->assertCookie('account', '1001');
        $response->assertCookie('login', '998901234567');
        $response->assertSessionHas('phone', '998901234567');
        // Billing's own login for the account — a distinct field from the
        // phone the subscriber typed, deliberately given a different value
        // here to prove one is not silently aliasing the other.
        $response->assertCookie('billing_login', 'TESTER01');
        $response->assertCookie('full_name', 'Tester');

        Http::assertSent(fn ($request): bool => $request['phn'] === '998901234567'
            && $request['sendsms'] === 1);
    }

    #[Test]
    public function an_unknown_phone_is_shown_the_translated_api_error(): void
    {
        Http::fake(['*/identify' => Http::response(['code' => 110], 400)]);

        $response = $this->post(route('login'), ['login' => '998900000000']);

        $response->assertOk();
        $response->assertSee(trans('errors.110'), escape: false);
        $response->assertCookieMissing('account');
    }

    #[Test]
    public function an_unrecognised_error_code_no_longer_reports_success(): void
    {
        // Code 126 was outside the old switch and used to render as "Success".
        Http::fake(['*/identify' => Http::response(['code' => 126], 400)]);

        $response = $this->post(route('login'), ['login' => '998900000000']);

        $response->assertSee(trans('errors.126'), escape: false);
        $response->assertDontSee('Success');
    }

    #[Test]
    public function a_correct_sms_code_verifies_the_session(): void
    {
        Http::fake([
            '*/verify' => Http::response(['result' => 'ok']),
            '*/identify' => Http::response([
                'accs' => [['accId' => 1001, 'abonType' => 2, 'abonName' => 'Tester']],
            ]),
        ]);

        $response = $this->withCookies(['login' => '998901234567', 'phone' => '998901234567'])
            ->post(route('verify'), ['code' => '1234']);

        $response->assertRedirect(route('cabinet'));
        $response->assertCookie('verify', '1');

        Http::assertSent(fn ($request): bool => ! str_contains($request->url(), '/verify')
            || ($request['phn'] === '998901234567' && $request['smsCode'] === '1234'));
    }

    #[Test]
    public function a_wrong_sms_code_keeps_the_session_unverified(): void
    {
        Http::fake(['*/verify' => Http::response(['code' => 120], 400)]);

        $response = $this->withCookies(['login' => '998901234567'])
            ->post(route('verify'), ['code' => '0000']);

        $response->assertOk();
        $response->assertSee(trans('errors.120'), escape: false);
        $response->assertCookieMissing('verify');
    }

    #[Test]
    public function a_phone_with_several_accounts_gets_the_picker(): void
    {
        Http::fake([
            '*/verify' => Http::response(['result' => 'ok']),
            '*/identify' => Http::response([
                'accs' => [
                    ['accId' => 1001, 'abonType' => 2, 'abonName' => 'First'],
                    ['accId' => 1002, 'abonType' => 2, 'abonName' => 'Second'],
                ],
            ]),
        ]);

        $response = $this->withCookies(['login' => '998901234567', 'phone' => '998901234567'])
            ->post(route('verify'), ['code' => '1234']);

        $response->assertOk();
        $response->assertSee('First');
        $response->assertSee('Second');
    }

    /**
     * The subscriber's display name is sourced from /identify's own
     * `abonName`/`login` — never /abonent/info's `name`, which was observed
     * live 2026-08-28 on account 1000033 (abonType "1", one-off) returning
     * the literal string "Разовый абонент" (billing's own generic label for
     * the account type) instead of a real name or null. `abonName` is
     * documented as "often blank", so `login` — /identify's reliable field
     * for that same row — stands in when it is (see
     * AuthController::resolveFullName()).
     */
    #[Test]
    public function switching_account_falls_back_to_login_when_billings_own_name_is_blank(): void
    {
        Http::fake(['*/identify' => Http::response([
            'accs' => [
                ['accId' => 1000033, 'abonType' => 1, 'abonName' => '', 'login' => '998907654321'],
            ],
        ])]);

        $response = $this->withCookies(['verify' => '1', 'phone' => '998901234567'])
            ->get(route('set.account', 1000033));

        $response->assertRedirect(route('cabinet'));
        $response->assertCookie('full_name', '998907654321');
    }

    #[Test]
    public function switching_account_uses_the_account_own_name(): void
    {
        Http::fake(['*/identify' => Http::response([
            'accs' => [
                ['accId' => 1001, 'abonType' => 2, 'abonName' => 'Абдуллаев Абдулла', 'login' => 'TESTER01'],
            ],
        ])]);

        $response = $this->withCookies(['verify' => '1', 'phone' => '998901234567'])
            ->get(route('set.account', 1001));

        $response->assertRedirect(route('cabinet'));
        $response->assertCookie('full_name', 'Абдуллаев Абдулла');
    }

    #[Test]
    public function logging_out_clears_the_session_cookies(): void
    {
        $response = $this->withCookies([
            'verify' => '1',
            'account' => '1001',
            'login' => '998901234567',
            'billing_login' => 'TESTER01',
        ])->get(route('logout'));

        $response->assertRedirect(route('login'));
        $response->assertCookieExpired('verify');
        $response->assertCookieExpired('account');
        $response->assertCookieExpired('login');
        // Left uncleared, a shared browser's next subscriber would open the
        // cabinet still showing the previous one's billing login.
        $response->assertCookieExpired('billing_login');
    }

    #[Test]
    public function repeated_login_attempts_are_throttled(): void
    {
        Http::fake(['*/identify' => Http::response(['code' => 110], 400)]);

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post(route('login'), ['login' => '998900000000'])->assertOk();
        }

        // The sixth submission would be the sixth SMS in a minute.
        $this->post(route('login'), ['login' => '998900000000'])->assertStatus(429);
    }

    #[Test]
    public function repeated_sms_code_guesses_are_throttled(): void
    {
        Http::fake(['*/verify' => Http::response(['code' => 120], 400)]);

        for ($attempt = 0; $attempt < 10; $attempt++) {
            $this->withCookies(['login' => '998901234567'])
                ->post(route('verify'), ['code' => '0000'])
                ->assertOk();
        }

        $this->withCookies(['login' => '998901234567'])
            ->post(route('verify'), ['code' => '0000'])
            ->assertStatus(429);
    }

    #[Test]
    public function the_locale_switcher_only_accepts_supported_languages(): void
    {
        $this->get('/change/lang/uz')->assertRedirect();
        $this->get('/change/lang/../../etc/passwd')->assertNotFound();
        $this->get('/change/lang/de')->assertNotFound();
    }

    /**
     * The cabinet used to ship two template sets picked from the User-Agent.
     * It now serves one responsive set, so the markup must not vary by device —
     * a tablet, a desktop window narrowed to phone width and a real phone all
     * have to get the same document and let CSS decide.
     */
    #[Test]
    public function every_device_gets_the_same_responsive_markup(): void
    {
        $agents = [
            'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 Mobile/15E148',
            'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 Chrome/120 Safari/537.36',
            'Mozilla/5.0 (Linux; Android 14; SM-X510) AppleWebKit/537.36 Chrome/120 Safari/537.36',
        ];

        $bodies = [];

        foreach ($agents as $agent) {
            $response = $this->withHeaders(['User-Agent' => $agent])->get(route('login'));

            $response->assertOk();
            $bodies[] = $response->getContent();
        }

        $this->assertCount(1, array_unique($bodies));
    }
}
