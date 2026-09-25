<?php

declare(strict_types=1);

namespace Tests\Feature\Activity;

use App\Support\TariffVisibility;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\FakesSubscriberBilling;
use Tests\Concerns\UsesActivityDatabase;
use Tests\TestCase;

/**
 * tariff_changes — every "change tariff" attempt ends as exactly one row with
 * a final result, whatever billing or our own gate said (plan §4).
 */
final class TariffChangeHistoryTest extends TestCase
{
    // enabled_tariffs lives in the shared local sqlite file.
    use DatabaseTransactions;
    use FakesSubscriberBilling;
    use UsesActivityDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
        $this->setUpActivityDatabase();

        (new TariffVisibility)->enable(9);
    }

    #[Test]
    public function a_successful_switch_is_one_row_with_both_tariffs_in_som(): void
    {
        $this->fakeBilling();

        $this->verifiedSubscriber()
            ->post('/tariffs/connect', ['tariff' => 9, 'timing' => 'now'])
            ->assertRedirect(route('tariff'))
            ->assertSessionHas('info', trans('app.modal.success_tariff'));

        $rows = $this->activity()->table('tariff_changes')->get();
        $this->assertCount(1, $rows);
        $change = $rows->first();

        $this->assertSame('success', $change->result);
        $this->assertSame('1001', $change->account_id);
        $this->assertSame('998901234567', $change->phone);
        $this->assertSame('839', $change->old_tariff_id);
        $this->assertSame('9', $change->new_tariff_id);
        // Trailing space from billing trimmed.
        $this->assertSame('Paket 30 kun', $change->new_tariff_name);
        // /tariff/available.cost is tiyin → 2 500 000 / 100.
        $this->assertEquals(25000.0, (float) $change->new_tariff_price);
        // /abonent/info.tariff_price and saldo are already soʻm — not divided.
        $this->assertEquals(100000.0, (float) $change->old_tariff_price);
        $this->assertEquals(125000.50, (float) $change->balance_before);
        $this->assertSame('now', $change->timing);
        $this->assertNotNull($change->billing_duration_ms);
        $this->assertStringContainsString('"saldo":"125000.50"', (string) $change->profile_snapshot);

        // The deferred re-read after success filled the aftermath.
        $this->assertEquals(125000.50, (float) $change->balance_after);

        // And the journal carries the same attempt.
        $event = $this->activity()->table('activity_events')->where('event', 'tariff.connect')->sole();
        $this->assertSame('ok', $event->outcome);
        $this->assertStringContainsString('"tariff_change_id":'.$change->id, (string) $event->meta);
    }

    #[Test]
    public function billing_code_129_is_recorded_as_insufficient_funds_and_explained_to_the_subscriber(): void
    {
        $this->fakeBilling([
            '*/tariff/connect' => Http::response(['code' => 129, 'errMsg' => 'Баланс не позволяет изменить тариф'], 400),
        ]);

        $this->verifiedSubscriber()
            ->post('/tariffs/connect', ['tariff' => 9, 'timing' => 'now'])
            ->assertRedirect(route('tariff'))
            // Our own wording, which says what to do next — not billing's bare errMsg.
            ->assertSessionHas('danger', trans('errors.129'));

        $change = $this->activity()->table('tariff_changes')->sole();
        $this->assertSame('insufficient_funds', $change->result);
        $this->assertSame('129', $change->error_code);
        $this->assertSame('Баланс не позволяет изменить тариф', $change->error_message);
        $this->assertNull($change->balance_after);

        $event = $this->activity()->table('activity_events')->where('event', 'tariff.connect')->sole();
        $this->assertSame('fail', $event->outcome);
        $this->assertSame('129', $event->error_code);
    }

    #[Test]
    public function any_other_billing_code_is_a_billing_error(): void
    {
        $this->fakeBilling([
            '*/tariff/connect' => Http::response(['code' => 100, 'errMsg' => 'Системная ошибка'], 400),
        ]);

        $this->verifiedSubscriber()->post('/tariffs/connect', ['tariff' => 9, 'timing' => 'now']);

        $change = $this->activity()->table('tariff_changes')->sole();
        $this->assertSame('billing_error', $change->result);
        $this->assertSame('100', $change->error_code);
    }

    #[Test]
    public function billing_not_answering_is_recorded_as_unavailable_and_never_left_pending(): void
    {
        $this->fakeBilling([
            '*/tariff/connect' => fn () => throw new ConnectionException('cURL error 28'),
        ]);

        $this->verifiedSubscriber()
            ->post('/tariffs/connect', ['tariff' => 9, 'timing' => 'now'])
            ->assertStatus(503);

        $change = $this->activity()->table('tariff_changes')->sole();
        $this->assertSame('unavailable', $change->result);
        $this->assertSame(0, $this->activity()->table('tariff_changes')->where('result', 'pending')->count());

        $event = $this->activity()->table('activity_events')->where('event', 'tariff.connect')->sole();
        $this->assertSame('fail', $event->outcome);
        $this->assertSame(503, (int) $event->http_status);
    }

    #[Test]
    public function a_legal_entity_is_denied_with_the_reason_and_billing_is_never_asked(): void
    {
        $this->fakeBilling([
            '*/abonent/info' => Http::response(['saldo' => '1000', 'curr_tariff_id' => '839', 'curr_tariff_name' => 'Home 100', 'legal' => 'Юридическое лицо']),
        ]);

        $this->verifiedSubscriber()
            ->post('/tariffs/connect', ['tariff' => 9, 'timing' => 'now'])
            ->assertForbidden();

        $change = $this->activity()->table('tariff_changes')->sole();
        $this->assertSame('denied', $change->result);
        $this->assertSame('legal_entity', $change->denied_reason);
        $this->assertTrue((bool) $change->is_legal_entity);

        // The 403 is in the journal too — deferred writes run on error responses.
        $this->assertSame('denied', $this->activity()->table('activity_events')->where('event', 'tariff.connect')->value('outcome'));

        Http::assertNotSent(fn ($request): bool => str_contains($request->url(), '/tariff/connect'));
    }

    #[Test]
    public function a_temporary_subscriber_with_a_tariff_is_denied_as_not_permanent(): void
    {
        $this->fakeBilling();

        $this->verifiedSubscriber(type: 0)
            ->post('/tariffs/connect', ['tariff' => 9, 'timing' => 'now'])
            ->assertForbidden();

        $this->assertSame('not_permanent', $this->activity()->table('tariff_changes')->sole()->denied_reason);
    }

    #[Test]
    public function a_tariff_billing_does_not_offer_is_denied_as_not_in_available(): void
    {
        $this->fakeBilling();

        $this->verifiedSubscriber()
            ->post('/tariffs/connect', ['tariff' => 99999, 'timing' => 'now'])
            ->assertForbidden();

        $change = $this->activity()->table('tariff_changes')->sole();
        $this->assertSame('not_in_available', $change->denied_reason);
        $this->assertNull($change->new_tariff_name);
    }

    #[Test]
    public function a_tariff_the_admin_has_not_enabled_is_denied_as_not_allowed(): void
    {
        $this->fakeBilling();

        // 412 is offered by billing but was never switched on in /admin/tariffs.
        $this->verifiedSubscriber()
            ->post('/tariffs/connect', ['tariff' => 412, 'timing' => 'now'])
            ->assertForbidden();

        $change = $this->activity()->table('tariff_changes')->sole();
        $this->assertSame('not_allowed', $change->denied_reason);
        $this->assertSame('Turbo 200', $change->new_tariff_name);
        $this->assertEquals(45000.0, (float) $change->new_tariff_price);
    }

    #[Test]
    public function a_broken_activity_database_never_blocks_the_switch_itself(): void
    {
        $this->fakeBilling();
        Schema::connection('activity')->drop('tariff_changes');

        $this->verifiedSubscriber()
            ->post('/tariffs/connect', ['tariff' => 9, 'timing' => 'now'])
            ->assertRedirect(route('tariff'))
            ->assertSessionHas('info', trans('app.modal.success_tariff'));

        Http::assertSent(fn ($request): bool => str_contains($request->url(), '/tariff/connect'));
    }

    #[Test]
    public function nothing_is_recorded_while_the_journal_is_switched_off(): void
    {
        config(['activity.enabled' => false]);
        $this->fakeBilling();

        $this->verifiedSubscriber()->post('/tariffs/connect', ['tariff' => 9, 'timing' => 'now']);

        $this->assertSame(0, $this->activity()->table('tariff_changes')->count());
        $this->assertSame(0, $this->activity()->table('activity_events')->count());
    }
}
