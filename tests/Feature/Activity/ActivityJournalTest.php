<?php

declare(strict_types=1);

namespace Tests\Feature\Activity;

use App\Support\Activity\ActivityStats;
use App\Support\Activity\ReportPeriod;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\FakesSubscriberBilling;
use Tests\Concerns\UsesActivityDatabase;
use Tests\TestCase;

/**
 * activity_events — one row per menu page and per action, written after the
 * response, never at the subscriber's expense (plan §3).
 */
final class ActivityJournalTest extends TestCase
{
    use FakesSubscriberBilling;
    use UsesActivityDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
        $this->setUpActivityDatabase();
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    #[Test]
    public function every_menu_page_is_recorded_with_the_account_and_the_device(): void
    {
        $this->fakeBilling();
        $subscriber = $this->verifiedSubscriber()
            ->withHeader('User-Agent', 'Mozilla/5.0 (Linux; Android 13; SM-A515F) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Mobile Safari/537.36')
            ->withUnencryptedCookie('sola_scr', '412,915,412,dark,lg');

        foreach (['/', '/tariffs', '/devices', '/statistics', '/finance', '/services'] as $path) {
            $subscriber->get($path)->assertOk();
        }

        $events = $this->activity()->table('activity_events')->orderBy('id')->get();

        $this->assertSame(
            ['page.home', 'page.tariffs', 'page.devices', 'page.statistics', 'page.finance', 'page.services'],
            $events->pluck('event')->all(),
        );

        $home = $events->first();
        $this->assertSame('ok', $home->outcome);
        $this->assertSame('1001', $home->account_id);
        $this->assertSame('998901234567', $home->phone);
        $this->assertSame('Tester Testov', $home->full_name);
        $this->assertSame(2, (int) $home->abon_type);
        $this->assertSame('mobile', $home->device_type);
        $this->assertSame('Chrome', $home->browser);
        $this->assertSame('Android', $home->os);
        $this->assertSame('SM-A515F', $home->device_model);
        $this->assertSame(412, (int) $home->screen_w);
        $this->assertSame('dark', $home->theme);
        $this->assertSame('lg', $home->text_size);
        $this->assertSame('/', $home->path);
        $this->assertSame(200, (int) $home->http_status);
        $this->assertNotNull($home->request_id);
    }

    #[Test]
    public function hits_count_every_visit_and_uniques_count_accounts_not_phones(): void
    {
        $this->fakeBilling();

        for ($i = 0; $i < 5; $i++) {
            $this->verifiedSubscriber()->get('/')->assertOk();
        }

        // Same phone, a second account — a second unique.
        $this->verifiedSubscriber(accountId: '1002')->get('/')->assertOk();

        $home = app(ActivityStats::class)
            ->byEvent(ReportPeriod::between(null, null))
            ->firstWhere('event', 'page.home');

        $this->assertSame(6, $home->hits);
        $this->assertSame(2, $home->uniques);
    }

    #[Test]
    public function a_failed_billing_action_records_billings_code(): void
    {
        $this->fakeBilling([
            '*/device/new' => Http::response(['code' => 127, 'errMsg' => 'Имеется устройство с не привязанным MAC адресом'], 400),
        ]);

        $this->verifiedSubscriber()->post('/devices/add')->assertRedirect(route('devices'));

        $event = $this->activity()->table('activity_events')->sole();
        $this->assertSame('device.add', $event->event);
        $this->assertSame('fail', $event->outcome);
        $this->assertSame('127', $event->error_code);
        $this->assertSame('Имеется устройство с не привязанным MAC адресом', $event->error_message);
    }

    #[Test]
    public function a_forbidden_action_is_recorded_as_denied(): void
    {
        $this->fakeBilling();

        $this->verifiedSubscriber(type: 1)->post('/devices/add')->assertForbidden();

        $event = $this->activity()->table('activity_events')->sole();
        $this->assertSame('device.add', $event->event);
        $this->assertSame('denied', $event->outcome);
        $this->assertSame(403, (int) $event->http_status);
    }

    #[Test]
    public function a_form_that_fails_validation_is_recorded_as_invalid(): void
    {
        $this->fakeBilling();

        $this->verifiedSubscriber()
            ->from('/finance')
            ->post('/finance', ['start' => 'yesterday', 'end' => '2026-08-31'])
            ->assertSessionHasErrors('start');

        $event = $this->activity()->table('activity_events')->sole();
        $this->assertSame('filter.finance', $event->event);
        $this->assertSame('invalid', $event->outcome);
    }

    #[Test]
    public function a_period_filter_records_the_range_it_asked_for(): void
    {
        $this->fakeBilling();

        $this->verifiedSubscriber()->post('/statistics', ['start' => '2026-08-01', 'end' => '2026-08-31'])->assertOk();

        $event = $this->activity()->table('activity_events')->sole();
        $this->assertSame('filter.statistics', $event->event);
        $this->assertSame(['begin' => '2026-08-01', 'end' => '2026-08-31'], json_decode((string) $event->meta, true));
    }

    /**
     * Regression: Laravel's middleware priority moved ThrottleRequests in
     * front of RecordActivity, so a 429 never reached the journal.
     */
    #[Test]
    public function a_throttled_sign_in_is_recorded_as_rate_limited(): void
    {
        $this->fakeBilling(['*/identify' => Http::response(['code' => 110, 'errMsg' => 'Абонент не найден'], 400)]);

        for ($i = 0; $i < 6; $i++) {
            $response = $this->post('/auth/login', ['login' => '901234567']);
        }

        $response->assertStatus(429);

        $limited = $this->activity()->table('activity_events')->where('event', 'rate_limited')->sole();
        $this->assertSame('invalid', $limited->outcome);
        $this->assertSame(['route_event' => 'auth.sms_requested'], json_decode((string) $limited->meta, true));

        // The five that got through were recorded as billing refusals, with the typed phone.
        $attempts = $this->activity()->table('activity_events')->where('event', 'auth.sms_requested')->get();
        $this->assertCount(5, $attempts);
        $this->assertSame('110', $attempts->first()->error_code);
        $this->assertStringContainsString('"phone_typed":"901234567"', (string) $attempts->first()->meta);
    }

    #[Test]
    public function the_sms_code_and_session_secrets_are_never_stored(): void
    {
        $this->fakeBilling();

        $this->withCookies(['login' => '998901234567', 'phone' => '998901234567'])
            ->post('/auth/verify', ['code' => '4821']);

        $stored = json_encode($this->activity()->table('activity_events')->get()->all());

        $this->assertStringContainsString('auth.verified', (string) $stored);
        $this->assertStringNotContainsString('4821', (string) $stored);
        $this->assertStringNotContainsString(session()->getId(), (string) $stored);
    }

    #[Test]
    public function the_first_account_pick_and_a_later_switch_are_told_apart(): void
    {
        $this->fakeBilling();

        // Signed in, no account chosen yet.
        $this->withCookies(['verify' => '1', 'login' => '998901234567', 'phone' => '998901234567'])
            ->get('/select/account/1001')
            ->assertRedirect(route('cabinet'));

        $this->verifiedSubscriber()->get('/select/account/1002')->assertRedirect(route('cabinet'));

        $this->assertSame(
            ['auth.account_selected', 'auth.account_switched'],
            $this->activity()->table('activity_events')->orderBy('id')->pluck('event')->all(),
        );
    }

    #[Test]
    public function a_broken_activity_database_never_breaks_a_page(): void
    {
        $this->fakeBilling();
        Schema::connection('activity')->drop('activity_events');

        $this->verifiedSubscriber()->get('/')->assertOk();
    }

    #[Test]
    public function nothing_is_recorded_while_the_journal_is_switched_off(): void
    {
        config(['activity.enabled' => false]);
        $this->fakeBilling();

        $this->verifiedSubscriber()->get('/')->assertOk()->assertDontSee('activity-beacon');

        $this->assertSame(0, $this->activity()->table('activity_events')->count());
    }

    // -- Browser beacon ----------------------------------------------------

    #[Test]
    public function the_page_tells_the_browser_where_to_report(): void
    {
        $this->fakeBilling();

        $this->verifiedSubscriber()->get('/')->assertSee('name="activity-beacon"', false);
    }

    #[Test]
    public function a_table_search_from_the_browser_is_recorded_with_what_was_typed(): void
    {
        $this->verifiedSubscriber()
            ->post('/activity', ['event' => 'ui.search', 'table' => 'payments', 'query' => 'Payme', 'results' => 3, 'junk' => 'x'])
            ->assertNoContent();

        $event = $this->activity()->table('activity_events')->sole();
        $this->assertSame('ui.search', $event->event);
        $this->assertSame('1001', $event->account_id);
        // Only the catalogue's fields — "junk" is dropped.
        $this->assertSame(['table' => 'payments', 'query' => 'Payme', 'results' => 3], json_decode((string) $event->meta, true));
    }

    #[Test]
    public function the_beacon_refuses_anything_outside_the_browser_catalogue(): void
    {
        // A server event name, and a made-up one: both 422, neither stored.
        $this->verifiedSubscriber()->post('/activity', ['event' => 'tariff.connect'])->assertStatus(422);
        $this->verifiedSubscriber()->post('/activity', ['event' => 'ui.anything'])->assertStatus(422);
        $this->verifiedSubscriber()->post('/activity', ['event' => 'ui.search', 'query' => str_repeat('a', 101)])->assertStatus(422);

        $this->assertSame(0, $this->activity()->table('activity_events')->count());
    }

    #[Test]
    public function a_guest_cannot_write_to_the_journal(): void
    {
        $this->post('/activity', ['event' => 'ui.print'])->assertRedirect(route('login'));

        $this->assertSame(0, $this->activity()->table('activity_events')->count());
    }

    #[Test]
    public function the_beacon_is_throttled(): void
    {
        for ($i = 0; $i < 60; $i++) {
            $this->verifiedSubscriber()->post('/activity', ['event' => 'ui.print'])->assertNoContent();
        }

        $this->verifiedSubscriber()->post('/activity', ['event' => 'ui.print'])->assertStatus(429);

        $this->assertSame(60, $this->activity()->table('activity_events')->count());
    }
}
