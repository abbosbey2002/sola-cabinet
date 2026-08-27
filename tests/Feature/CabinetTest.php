<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Support\TariffVisibility;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Exercises the pages a verified subscriber sees, against a faked SOLA API.
 */
final class CabinetTest extends TestCase
{
    // Only the tariff-visibility tests below touch the database
    // (enabled_tariffs); the transaction keeps those writes from surviving
    // into the shared local sqlite file the rest of these tests don't use.
    use DatabaseTransactions;

    private const ACCOUNT_ID = '1001';

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
    }

    protected function tearDown(): void
    {
        // Several tests here freeze the clock — the day meter and the tariff
        // connection date are both computed from "today".
        Carbon::setTestNow();

        parent::tearDown();
    }

    #[Test]
    public function the_home_page_shows_the_balance_and_the_devices_page_the_permits(): void
    {
        $this->fakeSola();

        // The dashboard was split: the home page answers "will my balance
        // last", and the MAC list moved to its own page.
        $this->verifiedSubscriber()->get('/')
            ->assertOk()
            ->assertSee('Tester Testov')
            ->assertSee('125 000')
            ->assertDontSee('AA:BB:CC:DD:EE:FF');

        $this->verifiedSubscriber()->get('/devices')
            ->assertOk()
            ->assertSee('AA:BB:CC:DD:EE:FF');
    }

    /**
     * The day meter is the page's whole point, so it has to be built from the
     * charge date rather than from the day of the month: the fill and the
     * handle sit at today's position along the cycle, the charge marker at
     * the last day.
     */
    #[Test]
    public function the_day_meter_counts_the_days_to_the_next_charge(): void
    {
        Carbon::setTestNow('2026-08-08');

        $this->fakeSola([
            '*/abonent/info' => Http::response([
                'name' => 'Tester Testov',
                'saldo' => 125000,
                'curr_tariff_name' => 'Home 100',
                'next_charge_date' => '2026-09-01',
            ]),
        ]);

        $content = (string) $this->verifiedSubscriber()->get('/')->getContent();

        // 01.08 → 01.09 is 31 days (span 30); the 8th is day 8, so today sits
        // at 7/30 = 23.33% along the track, and the charge lands on the last
        // day, i.e. 100%.
        $this->assertSame(1, substr_count($content, 'class="u-meter-handle"'));
        $this->assertSame(1, substr_count($content, 'class="u-meter-charge"'));
        $this->assertStringContainsString('u-meter-fill" style="width: 23.33%"', $content);
        $this->assertStringContainsString('u-meter-handle" style="left: 23.33%"', $content);
        $this->assertStringContainsString('u-meter-charge" style="left: 100%"', $content);

        $this->assertStringContainsString(
            trans_choice('app.dash.days_left', 24, ['days' => 24]),
            $content,
        );
    }

    /**
     * When today IS the charge day, the handle takes on the charge marker's
     * own diamond instead of a separate marker being drawn on top of it —
     * two shapes for the same point would just be visual noise.
     */
    #[Test]
    public function the_handle_becomes_the_charge_marker_when_today_is_the_charge_day(): void
    {
        Carbon::setTestNow('2026-09-01');

        $this->fakeSola([
            '*/abonent/info' => Http::response([
                'name' => 'Tester Testov',
                'saldo' => 125000,
                'curr_tariff_name' => 'Home 100',
                'next_charge_date' => '2026-09-01',
            ]),
        ]);

        $content = (string) $this->verifiedSubscriber()->get('/')->getContent();

        $this->assertSame(1, substr_count($content, 'class="u-meter-handle"'));
        $this->assertSame(0, substr_count($content, 'class="u-meter-charge"'));
        $this->assertSame(1, substr_count($content, 'data-t="charge"'));
        $this->assertStringContainsString('u-meter-handle" style="left: 100%"', $content);
    }

    /**
     * Billing's confirmed field: /abonent/info's `charge_date` IS the upcoming
     * payment date already — no arithmetic, it is read straight through.
     */
    #[Test]
    public function the_next_charge_date_is_read_directly_from_charge_date(): void
    {
        Carbon::setTestNow('2026-07-20');

        $this->fakeSola([
            '*/abonent/info' => Http::response([
                'name' => 'Tester Testov',
                'saldo' => 125000,
                'curr_tariff_name' => 'Home 100',
                'charge_date' => '2026-08-15',
            ]),
        ]);

        $this->verifiedSubscriber()->get('/')
            ->assertOk()
            ->assertSee('u-meter-track', escape: false)
            ->assertSee('15.08.2026');
    }

    /**
     * The same `charge_date`, but a legal entity: billing has already applied
     * whatever individual/legal-entity rule it uses on its own side, so the
     * date still passes through unchanged — no end-of-month adjustment here.
     */
    #[Test]
    public function a_legal_entitys_next_charge_date_is_also_read_directly(): void
    {
        Carbon::setTestNow('2026-07-20');

        $this->fakeSola([
            '*/abonent/info' => Http::response([
                'name' => 'Tester Firma OOO',
                'saldo' => 125000,
                'curr_tariff_name' => 'Home 100',
                'charge_date' => '2026-08-15',
                'legal' => 'Юридическое лицо',
            ]),
        ]);

        $this->verifiedSubscriber()->get('/')
            ->assertOk()
            ->assertSee('u-meter-track', escape: false)
            ->assertSee('15.08.2026');
    }

    /**
     * With nothing to anchor a charge date to, thirty-one ticks drawn against a
     * guess would be a confident lie, so there is no meter.
     *
     * This profile carries a contract_date and no curr_tariff_id, and the meter
     * still must not appear: deriving the charge day from the contract was
     * tried on 2026-08-10 and reverted the same day — the charge follows the
     * last tariff change, not the contract day. The assertion below is what
     * keeps that fix from being quietly undone by a future "it's easy, just use
     * contract_date". The legitimate anchor is exercised by the test after it.
     */
    #[Test]
    public function no_charge_date_means_no_meter_rather_than_an_invented_one(): void
    {
        $this->fakeSola();

        $this->verifiedSubscriber()->get('/')
            ->assertOk()
            ->assertSee('125 000')
            ->assertDontSee('u-meter-track', escape: false);
    }

    /**
     * The anchor billing does supply: /tariff/connected reports the day the
     * tariff started, and the period ends on that day of the month. That is
     * enough to draw the meter the dashboard is built around, without billing
     * ever sending a charge date of its own.
     */
    #[Test]
    public function the_meter_is_drawn_from_the_day_the_tariff_started(): void
    {
        Carbon::setTestNow('2026-08-13 09:00:00');

        $this->fakeSola([
            '*/abonent/info' => Http::response([
                'name' => 'Tester Testov',
                'saldo' => 125000,
                'curr_tariff_id' => '839',
                'curr_tariff_name' => 'Home 100',
            ]),
            '*/tariff/connected' => Http::response([
                'tariffs' => [
                    ['tariff_id' => '839', 'tariff_name' => 'Home 100', 'date_begin' => '2026-06-10 16:34:27', 'date_end' => null, 'tariff_isoff' => '0'],
                ],
            ]),
        ]);

        // Anchor day 10, already past on the 13th, so the charge is 10 September
        // — and the cycle it closes opened on 10 August.
        $this->verifiedSubscriber()->get('/')
            ->assertOk()
            ->assertSee('u-meter-track', escape: false)
            ->assertSee('10.09.2026')
            ->assertSee('10.08.2026');
    }

    /**
     * Every item in the top navigation is its own page, and each one marks
     * itself as current so the subscriber knows where they are.
     */
    #[Test]
    public function each_navigation_section_is_its_own_page(): void
    {
        $this->fakeSola();

        $sections = [
            '/' => 'app.nav.home',
            '/tariffs' => 'app.nav.tariff',
            '/devices' => 'app.nav.devices',
            '/statistics' => 'app.nav.statistics',
            '/finance' => 'app.nav.payments',
            '/services' => 'app.nav.services',
        ];

        foreach ($sections as $path => $heading) {
            $response = $this->verifiedSubscriber()->get($path);

            $response->assertOk();
            $response->assertSee('<h1', escape: false);
            $response->assertSee(trans($heading));

            // Exactly one nav entry is marked current — once in the desktop
            // rail and once in the mobile drawer, which render the same list.
            $this->assertSame(
                2,
                substr_count((string) $response->getContent(), 'aria-current="page"'),
                "$path should mark one section as current",
            );
        }
    }

    /**
     * Unlike promo/loyalty/chat — opt-in campaign links, unset by default —
     * the manager's Telegram contact ships with a real default
     * (config/sola.php), so the card is on the services page out of the box.
     */
    #[Test]
    public function the_manager_card_links_to_the_telegram_contact(): void
    {
        $this->fakeSola();

        $this->verifiedSubscriber()->get('/services')
            ->assertOk()
            ->assertSee(trans('app.manager.title'))
            ->assertSee('href="'.config('sola.manager_url').'"', escape: false);
    }

    #[Test]
    public function the_traffic_page_totals_the_reported_bytes(): void
    {
        $this->fakeSola([
            '*/traffic/detail' => Http::response([
                'detail' => [
                    ['traffic_input' => 1048576, 'traffic_output' => 2097152, 'event_time' => '2026-08-01 10:00:00'],
                    ['traffic_input' => 1048576, 'traffic_output' => 0, 'event_time' => '2026-08-02 11:00:00'],
                ],
            ]),
        ]);

        $response = $this->verifiedSubscriber()
            ->post('/statistics', ['start' => '2026-08-01', 'end' => '2026-08-31']);

        $response->assertOk();
        // 2 MiB in, 2 MiB out — an even split. The gauge sweeps 240°, so each
        // half is a 120° arc.
        $this->assertSame(
            2,
            substr_count((string) $response->getContent(), '--arc-len: 120'),
        );
    }

    /**
     * The API answers a range directly (detail_begin/detail_end), so a
     * request spanning months costs one call — but boundary rows outside
     * the requested days are still trimmed defensively.
     */
    #[Test]
    public function a_range_spanning_months_is_fetched_in_one_call_and_trimmed(): void
    {
        $this->fakeSola([
            '*/traffic/detail' => Http::response([
                'detail' => [
                    // Inside the requested 20.06 – 10.08 window.
                    ['traffic_input' => 1048576, 'traffic_output' => 0, 'event_time' => '2026-06-25 10:00:00'],
                    // Before it — trimmed by Period::contains() defensively.
                    ['traffic_input' => 8388608, 'traffic_output' => 0, 'event_time' => '2026-06-02 10:00:00'],
                ],
            ]),
        ]);

        $response = $this->verifiedSubscriber()
            ->post('/statistics', ['start' => '2026-06-20', 'end' => '2026-08-10']);

        $response->assertOk();

        // The block re-renders on its own, without re-reading the
        // subscriber's profile — one traffic call for the whole range.
        Http::assertSentCount(1);

        Http::assertSent(function (Request $request): bool {
            if (str_contains($request->url(), '/traffic/detail')) {
                $this->assertSame('20.06.2026', $request->data()['detail_begin']);
                $this->assertSame('10.08.2026', $request->data()['detail_end']);
            }

            return true;
        });
    }

    /**
     * Billing pads some tariff names with a trailing space, so the current
     * tariff has to be found by id — matching on the name loses it, and the
     * subscriber is then offered a switch to the tariff they already have.
     */
    #[Test]
    public function the_current_tariff_is_matched_by_id_not_by_name(): void
    {
        $this->fakeSola([
            '*/abonent/info' => Http::response([
                'name' => 'Tester Testov',
                'saldo' => 125000,
                'curr_tariff_id' => '839',
                'curr_tariff_name' => 'Paket 5 soat',
            ]),
            '*/tariff/available' => Http::response([
                'tariffs' => [
                    // The trailing space is what billing actually sends.
                    ['tariff_id' => '839', 'tariff_name' => 'Paket 5 soat ', 'cost' => 300000, 'tspd' => '500', 'spdu' => 'Mbps', 'tprd' => '5', 'prdu' => 'HOUR', 'vol' => '0'],
                    ['tariff_id' => '9', 'tariff_name' => 'Paket 30 kun', 'cost' => 2500000, 'tspd' => '500', 'spdu' => 'Mbps', 'tprd' => '30', 'prdu' => 'DAY', 'vol' => '0'],
                ],
            ]),
        ]);

        // Both rows have to be admin-enabled to reach the page at all — the
        // id-matching this test targets happens downstream of that filter.
        (new TariffVisibility)->enable(839);
        (new TariffVisibility)->enable(9);

        $content = (string) $this->verifiedSubscriber()->get('/tariffs')->getContent();

        // The option already in force is the unselectable one, and the padded
        // name is trimmed before it reaches the confirmation dialog.
        $this->assertMatchesRegularExpression('/value="839"[^>]*disabled/s', $content);
        $this->assertDoesNotMatchRegularExpression('/value="9"[^>]*disabled/s', $content);
        $this->assertStringContainsString('data-tariff-name="Paket 5 soat"', $content);

        // /abonent/info gives the tariff's name but none of its terms, so the
        // matched row is what fills them in. The current-tariff card states
        // them as one line — speed, validity, volume, price.
        $this->assertStringContainsString(
            '500 '.trans('app.tariff.unit_mb').' · 5 '.trans('app.tariff.hour').' · '.trans('app.tariff.no_limit'),
            $content,
        );
    }

    /**
     * The timing modal's "from next period" choice shows the actual date it
     * will take effect — the same 1st-of-next-month date connect() charges
     * against, so the two never drift apart — and the "now" choice spells out
     * that the previous tariff is not recalculated and the new one's
     * subscription fee is charged again immediately.
     */
    #[Test]
    public function the_timing_modal_shows_the_next_period_date_and_the_recalculation_note(): void
    {
        $this->fakeSola();

        $content = (string) $this->verifiedSubscriber()->get('/tariffs')->getContent();

        $this->assertStringContainsString(trans('app.modal.now'), $content);
        $this->assertStringContainsString(trans('app.modal.now_hint'), $content);
        $this->assertStringContainsString(trans('app.modal.month'), $content);
        $this->assertStringContainsString(
            now()->addMonth()->firstOfMonth()->format('d.m.Y'),
            $content,
        );

        // No charge_date in this fixture and no curr_tariff_id to derive one
        // from /tariff/connected either — the "next charge" line is omitted
        // rather than showing a guessed date.
        $this->assertStringNotContainsString(
            explode(':date', trans('app.modal.next_charge_note'))[0],
            $content,
        );
    }

    /**
     * When billing HAS told us the next charge date (the same charge_date
     * field the home page reads), the "Сейчас" choice shows it — still the
     * subscriber's already-scheduled charge, not a new one computed for the
     * switch, since billing has not said whether an immediate charge resets
     * that cycle.
     */
    #[Test]
    public function the_timing_modal_shows_the_known_next_charge_date(): void
    {
        Carbon::setTestNow('2026-07-20');

        $this->fakeSola([
            '*/abonent/info' => Http::response([
                'name' => 'Tester Testov',
                'saldo' => 125000,
                'curr_tariff_name' => 'Home 100',
                'charge_date' => '2026-08-15',
            ]),
        ]);

        $this->verifiedSubscriber()->get('/tariffs')
            ->assertOk()
            ->assertSee(trans('app.modal.next_charge_note', ['date' => '15.08.2026']));
    }

    /**
     * /abonent/info names the current tariff but never says when it started;
     * /tariff/connected is the only endpoint that carries it. The row is paired
     * on the id, so a tariff the subscriber does not hold cannot supply the
     * date — the card then shows nothing rather than someone else's.
     */
    #[Test]
    public function the_tariff_page_shows_the_day_the_current_tariff_started(): void
    {
        $this->fakeSola([
            '*/abonent/info' => Http::response([
                'name' => 'Tester Testov',
                'saldo' => 125000,
                'curr_tariff_id' => '839',
                'curr_tariff_name' => 'Home 100',
            ]),
            '*/tariff/connected' => Http::response([
                'tariffs' => [
                    ['tariff_id' => '9', 'tariff_name' => 'Paket 30 kun', 'date_begin' => '2024-01-02 09:00:00', 'date_end' => null, 'tariff_isoff' => '0'],
                    // Billing sends a timestamp; the card shows the day only.
                    ['tariff_id' => '839', 'tariff_name' => 'Home 100', 'date_begin' => '2026-08-10 16:34:27', 'date_end' => null, 'tariff_isoff' => '0'],
                ],
            ]),
        ]);

        $content = (string) $this->verifiedSubscriber()->get('/tariffs')->getContent();

        $this->assertStringContainsString(trans('app.tariff.started_at', ['date' => '10.08.2026']), $content);
        $this->assertStringNotContainsString('02.01.2024', $content);
    }

    /**
     * An account whose current tariff is not among the connected permits — and
     * an empty list — both leave the card without a date rather than borrowing
     * the first row that happens to be there.
     */
    #[Test]
    public function no_matching_permit_leaves_the_start_date_off_the_tariff_page(): void
    {
        $this->fakeSola([
            '*/abonent/info' => Http::response([
                'name' => 'Tester Testov',
                'saldo' => 125000,
                'curr_tariff_id' => '839',
                'curr_tariff_name' => 'Home 100',
            ]),
            '*/tariff/connected' => Http::response([
                'tariffs' => [
                    ['tariff_id' => '9', 'tariff_name' => 'Paket 30 kun', 'date_begin' => '2024-01-02 09:00:00', 'date_end' => null, 'tariff_isoff' => '0'],
                ],
            ]),
        ]);

        $content = (string) $this->verifiedSubscriber()->get('/tariffs')->getContent();

        $this->assertStringNotContainsString(trans('app.tariff.started_at', ['date' => '02.01.2024']), $content);
        $this->assertStringContainsString('Home 100', $content);
    }

    #[Test]
    public function the_cost_of_a_further_device_permit_is_shown_when_billing_prices_it(): void
    {
        $this->fakeSola([
            '*/device/list' => Http::response(['devices' => [], 'connect_cost' => '1500000']),
        ]);

        $this->verifiedSubscriber()->get('/devices')
            ->assertSee(trans('app.dash.connect_cost'), escape: false)
            ->assertSee('15 000');
    }

    /**
     * -1 is what billing sends when it has no price to give; its meaning is
     * undocumented, so the row is left out rather than shown as "-15 сум".
     */
    #[Test]
    public function an_unpriced_device_permit_says_nothing_about_cost(): void
    {
        $this->fakeSola([
            '*/device/list' => Http::response(['devices' => [], 'connect_cost' => '-1']),
        ]);

        $this->verifiedSubscriber()->get('/devices')
            ->assertOk()
            ->assertDontSee(trans('app.dash.connect_cost'), escape: false);
    }

    /**
     * You narrow a table down before you read it, so the search box sits above
     * the rows — under them it is a box you scroll past to reach. Paging is the
     * other way round and stays below, which is why the toolbar and the pager
     * are two components rather than one block that had to pick a side.
     *
     * The export is a print button now: the print stylesheet already lays this
     * table out for A4, and "Save as PDF" is a destination in the dialog on
     * every platform the cabinet is opened from.
     */
    #[Test]
    public function the_payments_table_carries_its_search_and_print_above_the_rows(): void
    {
        $this->fakeSola([
            '*/acct/payments' => Http::response([
                'payments' => [
                    ['payment_id' => '1062960', 'payment_date' => now()->format('Y-m-d').' 16:58:09', 'amount' => 2500000, 'payment_system' => 'PayNet', 'payment_status' => "to'langan"],
                ],
            ]),
        ]);

        $content = (string) $this->verifiedSubscriber()->get('/finance')->getContent();

        $toolbar = strpos($content, 'data-table-search');
        $table = strpos($content, '<table');
        $pager = strpos($content, 'data-table-nav');

        $this->assertIsInt($toolbar);
        $this->assertIsInt($table);
        $this->assertIsInt($pager);
        $this->assertLessThan($table, $toolbar, 'the search box belongs above the table');
        $this->assertGreaterThan($table, $pager, 'the pager belongs below the table');

        $this->assertStringContainsString('data-table-print', $content);
        $this->assertStringContainsString(trans('app.dash.print'), $content);

        // The CSV export and its label are gone, not merely hidden.
        $this->assertStringNotContainsString('data-table-export', $content);
    }

    #[Test]
    public function the_payment_number_is_shown_when_billing_sends_it(): void
    {
        $this->fakeSola([
            '*/acct/payments' => Http::response([
                'payments' => [
                    ['payment_id' => '1062960', 'payment_date' => now()->format('Y-m-d').' 16:58:09', 'amount' => 2500000, 'payment_system' => 'PayNet', 'payment_status' => "to'langan"],
                ],
            ]),
        ]);

        $this->verifiedSubscriber()->get('/finance')
            ->assertSee(trans('app.payment.id'), escape: false)
            ->assertSee('1062960');
    }

    /**
     * Billing sends corrections as negative amounts under the same free-text
     * status a normal payment carries — "оплачено" on a debit reads as
     * billing's mistake, not the subscriber's, so the sign overrides the
     * label and tone rather than the status text winning.
     */
    #[Test]
    public function a_negative_amount_is_labelled_a_charge_not_paid(): void
    {
        $this->fakeSola([
            '*/acct/payments' => Http::response([
                'payments' => [
                    ['payment_id' => '1067505', 'payment_date' => now()->format('Y-m-d').' 14:20:00', 'amount' => -20000000, 'payment_system' => 'касса', 'payment_status' => "to'langan"],
                ],
            ]),
        ]);

        $content = (string) $this->verifiedSubscriber()->get('/finance')->getContent();

        $this->assertStringContainsString(trans('app.payment.charge'), $content);
        $this->assertStringContainsString('u-pill-neutral', $content);
        // The raw billing status text is overridden, not shown alongside it.
        $this->assertStringNotContainsString("to'langan", $content);
    }

    /**
     * The column is not a placeholder: an older payment row without a receipt
     * number leaves the table as it was.
     */
    #[Test]
    public function the_payment_number_column_is_absent_when_billing_omits_it(): void
    {
        $this->fakeSola([
            '*/acct/payments' => Http::response([
                'payments' => [
                    ['payment_date' => now()->format('Y-m-d').' 16:58:09', 'amount' => 2500000, 'payment_system' => 'PayNet', 'payment_status' => "to'langan"],
                ],
            ]),
        ]);

        $this->verifiedSubscriber()->get('/finance')
            ->assertOk()
            ->assertSee('25 000')
            ->assertDontSee(trans('app.payment.id'), escape: false);
    }

    #[Test]
    public function the_payment_page_accepts_a_date_range(): void
    {
        $this->fakeSola();

        $this->verifiedSubscriber()
            ->post('/finance', ['start' => '2026-07-01', 'end' => '2026-07-31'])
            ->assertOk();
    }

    /**
     * Period::startInput()/endInput() and Period::paymentsStart()/
     * paymentsEnd() speak different formats for different consumers — an
     * HTML date input and /acct/payments respectively — and once already got
     * merged into one pair of methods by mistake, which silently broke this
     * picker (a "d.m.y" value is not valid HTML5 date input syntax, so the
     * browser just shows it empty). This pins the one the picker needs.
     */
    #[Test]
    public function the_period_picker_carries_an_html5_date_value(): void
    {
        $this->fakeSola();

        $this->verifiedSubscriber()->get('/finance')
            ->assertOk()
            ->assertSee('value="'.now()->startOfMonth()->format('Y-m-d').'"', escape: false);
    }

    /**
     * The traffic page opens on a trailing month (today minus one calendar
     * month), not the current-calendar-month default /finance and the
     * dashboard use — see Period::lastMonth().
     */
    #[Test]
    public function the_traffic_page_defaults_to_the_trailing_month(): void
    {
        $this->fakeSola();

        $this->verifiedSubscriber()->get('/statistics')
            ->assertOk()
            ->assertSee('value="'.now()->subMonth()->format('Y-m-d').'"', escape: false)
            ->assertSee('value="'.now()->format('Y-m-d').'"', escape: false);
    }

    #[Test]
    public function a_malformed_period_is_rejected(): void
    {
        $this->fakeSola();

        $this->verifiedSubscriber()
            ->from('/')
            ->post('/finance', ['start' => 'not-a-date', 'end' => '2026-07-31'])
            ->assertSessionHasErrors('start');
    }

    /**
     * The range cap was removed on the client's request (2026-08-25): a
     * subscriber can now ask for any span. Billing now takes the range
     * directly (detail_begin/detail_end), so a wide span no longer costs
     * one HTTP round trip per month it covers either.
     */
    #[Test]
    public function a_long_range_is_honoured_in_full_instead_of_clamped(): void
    {
        $this->fakeSola();

        $this->verifiedSubscriber()
            ->post('/statistics', ['start' => '2019-01-01', 'end' => '2026-08-10'])
            ->assertOk();

        // One /abonent/info-free block: one traffic call for the whole range.
        Http::assertSentCount(1);
    }

    #[Test]
    public function the_old_page_urls_still_land_on_the_right_page(): void
    {
        $this->fakeSola();

        $subscriber = $this->verifiedSubscriber();

        $subscriber->get('/traffic/detail')->assertRedirect('/statistics');
        $subscriber->get('/payment/history')->assertRedirect('/finance');

        // /tariffs used to redirect to the dashboard because the tariff block
        // lived there. It is a page in its own right now.
        $subscriber->get('/tariffs')->assertOk();
    }

    #[Test]
    public function an_unverified_visitor_is_sent_to_the_login_screen(): void
    {
        $this->get('/')->assertRedirect(route('login'));
    }

    #[Test]
    public function the_subscriber_cannot_switch_to_an_account_that_is_not_theirs(): void
    {
        $this->fakeSola();

        $this->verifiedSubscriber()
            ->get('/select/account/9999')
            ->assertForbidden();
    }

    #[Test]
    public function the_subscriber_can_switch_to_their_own_account(): void
    {
        $this->fakeSola();

        $this->verifiedSubscriber()
            ->get('/select/account/'.self::ACCOUNT_ID)
            ->assertRedirect(route('cabinet'));
    }

    /**
     * The three state-changing actions used to be GET links, so they travelled
     * without a CSRF token: a third-party page opened in the same browser only
     * had to redirect to /devices/add for the subscriber to be billed.
     *
     * Laravel exempts the test runner from CSRF verification, so what is pinned
     * here is the structural half of the fix — the actions are unreachable by
     * GET at all, which is what makes the token mandatory in the browser.
     */
    #[Test]
    public function the_money_spending_actions_cannot_be_triggered_by_a_link(): void
    {
        $this->fakeSola();

        $subscriber = $this->verifiedSubscriber();

        // The old URL shapes are gone outright.
        $subscriber->get('/tariffs/connect/5/now')->assertNotFound();

        // And the new ones refuse anything but POST.
        $subscriber->get('/tariffs/connect')->assertMethodNotAllowed();
        $subscriber->get('/devices/add')->assertMethodNotAllowed();
        $subscriber->get('/devices/delete/77')->assertMethodNotAllowed();

        // Nothing above reached billing.
        Http::assertNotSent(fn (Request $request): bool => str_contains($request->url(), '/tariff/connect')
            || str_contains($request->url(), '/device/new')
            || str_contains($request->url(), '/device/delete'));
    }

    /**
     * "timing" used to be a route segment constrained by whereIn. Now that it
     * is a body field, the validation has to carry that weight — an unknown
     * value once fell through a switch and reached billing with an undefined
     * date.
     */
    #[Test]
    public function an_unknown_tariff_timing_never_reaches_billing(): void
    {
        $this->fakeSola();

        $this->verifiedSubscriber()
            ->from('/tariffs')
            ->post('/tariffs/connect', ['tariff' => 5, 'timing' => 'whenever'])
            ->assertSessionHasErrors('timing');

        Http::assertNotSent(fn (Request $request): bool => str_contains($request->url(), '/tariff/connect'));
    }

    #[Test]
    public function a_tariff_switch_without_a_selection_is_rejected(): void
    {
        $this->fakeSola();

        $this->verifiedSubscriber()
            ->from('/tariffs')
            ->post('/tariffs/connect', ['timing' => 'now'])
            ->assertSessionHasErrors('tariff');

        Http::assertNotSent(fn (Request $request): bool => str_contains($request->url(), '/tariff/connect'));
    }

    /**
     * The two timings are the whole reason the dialog exists, and they resolve
     * to two different dates. Getting this wrong charges the subscriber a month
     * early.
     */
    #[Test]
    public function the_chosen_timing_decides_the_connection_date_sent_to_billing(): void
    {
        Carbon::setTestNow('2026-08-08 12:00:00');
        (new TariffVisibility)->enable(9);

        foreach (['now' => '2026-08-08', 'month' => '2026-09-01'] as $timing => $expected) {
            $this->fakeSola();

            $this->verifiedSubscriber()
                ->post('/tariffs/connect', ['tariff' => 9, 'timing' => $timing])
                ->assertRedirect('/tariffs');

            Http::assertSent(fn (Request $request): bool => ! str_contains($request->url(), '/tariff/connect')
                || ($request->data()['tariff_conndate'] === $expected && $request->data()['tariff_id'] === 9));
        }
    }

    /**
     * The "permanent subscribers only" rule lived in Blade and nowhere else, so
     * it was decoration: hiding a button does not stop a POST. A temporary
     * subscriber signs in normally and can lift a CSRF token off any page they
     * are allowed to see — and a device permit is billed.
     */
    #[Test]
    public function a_temporary_subscriber_cannot_buy_a_device_permit(): void
    {
        $this->fakeSola();

        foreach ([0, 1] as $type) {
            $this->verifiedSubscriber($type)->post('/devices/add')->assertForbidden();
            $this->verifiedSubscriber($type)->post('/devices/delete/77')->assertForbidden();
        }

        Http::assertNotSent(fn (Request $request): bool => str_contains($request->url(), '/device/new')
            || str_contains($request->url(), '/device/delete'));
    }

    /**
     * Billing does not report one permanent type. It separates a legal entity
     * from an individual, and those codes sit above 2 — so the gate asks
     * "is this one of the restricted kinds", never "does this equal 2".
     *
     * The bug this pins: with a strict === 2 the whole of one of those groups
     * was refused their own devices and tariff while x-account-type, which had
     * always used a trailing default, printed "Doimiy" beside their name. The
     * badge said full subscriber, the page said no.
     */
    #[Test]
    public function a_permanent_subscriber_is_not_only_type_two(): void
    {
        $this->fakeSola();
        (new TariffVisibility)->enable(9);

        foreach ([2, 3, 4] as $type) {
            $this->verifiedSubscriber($type)
                ->post('/tariffs/connect', ['tariff' => 9, 'timing' => 'now'])
                ->assertRedirect();

            $this->verifiedSubscriber($type)->post('/devices/add')->assertRedirect();
        }
    }

    #[Test]
    public function a_temporary_subscriber_with_a_tariff_cannot_switch_it(): void
    {
        $this->fakeSola();

        $this->verifiedSubscriber(1)
            ->post('/tariffs/connect', ['tariff' => 9, 'timing' => 'now'])
            ->assertForbidden();

        Http::assertNotSent(fn (Request $request): bool => str_contains($request->url(), '/tariff/connect'));
    }

    /**
     * Whether an account may hold a given plan is billing's business, and this
     * cabinet cannot infer it — so the only safe rule is that the id has to
     * come from the list billing offered for this very account.
     */
    #[Test]
    public function a_tariff_that_was_never_offered_to_this_account_is_refused(): void
    {
        $this->fakeSola();

        // 9 is the only id the fake offers; 4242 exists nowhere. Enabling 9
        // isolates the assertion to "billing never offered it" rather than
        // letting it pass merely because nothing is admin-enabled.
        (new TariffVisibility)->enable(9);

        $this->verifiedSubscriber()
            ->post('/tariffs/connect', ['tariff' => 4242, 'timing' => 'now'])
            ->assertForbidden();

        Http::assertNotSent(fn (Request $request): bool => str_contains($request->url(), '/tariff/connect'));
    }

    /**
     * A legal entity (yuridik shaxs) is not offered the tariff section at
     * all — not merely the switch action, the whole page. Confirmed by the
     * client on 2026-08-18: `legal` carries "Физическое лицо" or 0 for an
     * individual, anything else is a legal entity.
     */
    #[Test]
    public function a_legal_entity_cannot_reach_the_tariff_page(): void
    {
        $this->fakeSola([
            '*/abonent/info' => Http::response([
                'name' => 'Tester Testov',
                'saldo' => 125000,
                'curr_tariff_name' => 'Home 100',
                'legal' => 'Юридическое лицо',
            ]),
        ]);

        $this->verifiedSubscriber()->get('/tariffs')->assertForbidden();
    }

    /**
     * Hiding the page is not a control by itself — the connect endpoint has
     * to refuse the same account, or a CSRF-valid POST straight to it would
     * still switch the tariff.
     */
    #[Test]
    public function a_legal_entity_cannot_connect_a_tariff(): void
    {
        $this->fakeSola([
            '*/abonent/info' => Http::response([
                'name' => 'Tester Testov',
                'saldo' => 125000,
                'curr_tariff_name' => 'Home 100',
                'legal' => 'Юридическое лицо',
            ]),
        ]);

        $this->verifiedSubscriber()
            ->post('/tariffs/connect', ['tariff' => 9, 'timing' => 'now'])
            ->assertForbidden();

        Http::assertNotSent(fn (Request $request): bool => str_contains($request->url(), '/tariff/connect'));
    }

    /**
     * Both the top navigation and the dashboard's tariff card are built from
     * $profile, which every cabinet controller already loads — no extra API
     * call is needed to keep them in step with the server-side gate above.
     */
    #[Test]
    public function the_tariff_link_and_dashboard_card_are_hidden_for_a_legal_entity(): void
    {
        $this->fakeSola([
            '*/abonent/info' => Http::response([
                'name' => 'Tester Testov',
                'saldo' => 125000,
                'curr_tariff_name' => 'Home 100',
                'legal' => 'Юридическое лицо',
            ]),
        ]);

        $this->verifiedSubscriber()->get('/')
            ->assertOk()
            ->assertDontSee(trans('app.nav.tariff'))
            ->assertDontSee(trans('app.dash.current_tariff'));
    }

    /**
     * A tariff no admin has ever opted in (app/Support/TariffVisibility.php)
     * has to be refused the same way one billing never offered this account
     * is — otherwise the whitelist is cosmetic, and any id billing knows
     * about stays reachable by posting it directly with a valid CSRF token.
     */
    #[Test]
    public function a_tariff_never_enabled_by_the_admin_is_refused_even_though_billing_offers_it(): void
    {
        $this->fakeSola();

        // 9 is the one id the fake's */tariff/available response offers, but
        // no admin has ever enabled it — the whitelist starts empty.
        $this->verifiedSubscriber()
            ->post('/tariffs/connect', ['tariff' => 9, 'timing' => 'now'])
            ->assertForbidden();

        Http::assertNotSent(fn (Request $request): bool => str_contains($request->url(), '/tariff/connect'));
    }

    /**
     * Once enabled, explicitly disabling a tariff again has to withdraw it —
     * proving disable() actually removes the whitelist row rather than the
     * refusal above simply being the empty-whitelist default.
     */
    #[Test]
    public function a_tariff_the_admin_disabled_is_refused_even_though_billing_offers_it(): void
    {
        $this->fakeSola();

        // 9 is the one id the fake's */tariff/available response offers.
        $visibility = new TariffVisibility;
        $visibility->enable(9);
        $visibility->disable(9);

        $this->verifiedSubscriber()
            ->post('/tariffs/connect', ['tariff' => 9, 'timing' => 'now'])
            ->assertForbidden();

        Http::assertNotSent(fn (Request $request): bool => str_contains($request->url(), '/tariff/connect'));
    }

    #[Test]
    public function a_device_permit_is_released_and_the_subscriber_is_told(): void
    {
        $this->fakeSola([
            '*/device/delete' => Http::response(['code' => 0]),
        ]);

        $this->verifiedSubscriber()
            ->post('/devices/delete/77')
            ->assertRedirect('/devices')
            ->assertSessionHas('info', trans('app.header.success_deleted'));

        Http::assertSent(fn (Request $request): bool => ! str_contains($request->url(), '/device/delete')
            || $request->data()['permit_id'] === '77');
    }

    #[Test]
    public function an_unreachable_api_answers_503_instead_of_a_stack_trace(): void
    {
        Http::fake(fn () => throw new ConnectionException('cURL error 7'));

        $this->verifiedSubscriber()
            ->get('/')
            ->assertStatus(503);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function fakeSola(array $overrides = []): void
    {
        Http::fake($overrides + [
            '*/identify' => Http::response([
                'accs' => [
                    ['accId' => 1001, 'abonType' => 2, 'abonName' => 'Tester Testov'],
                ],
            ]),
            '*/abonent/info' => Http::response([
                'name' => 'Tester Testov',
                'email' => 'tester@sola.uz',
                'phone' => '998901234567',
                'address' => 'Tashkent',
                'contract_date' => '2019-09-05',
                'status' => 'active',
                'saldo' => 125000,
                'curr_tariff_name' => 'Home 100',
            ]),
            '*/device/list' => Http::response([
                'devices' => [
                    [
                        'mac' => 'AA:BB:CC:DD:EE:FF',
                        'ip' => '10.0.0.5',
                        'connect_date' => '2026-01-15',
                        'readonly' => false,
                        'permit_id' => '77',
                    ],
                ],
            ]),
            '*/acct/payments' => Http::response(['payments' => []]),
            '*/traffic/detail' => Http::response(['detail' => []]),
            // A tariff switch is now checked against the plans billing offers
            // THIS account, so the default fake has to offer at least one.
            '*/tariff/available' => Http::response([
                'tariffs' => [
                    ['tariff_id' => '9', 'tariff_name' => 'Paket 30 kun', 'cost' => 2500000, 'tspd' => '500', 'spdu' => 'Mbps', 'tprd' => '30', 'prdu' => 'DAY', 'vol' => '0'],
                ],
            ]),
            // Billing sends a timestamp in date_begin, not a date, and one row
            // per tariff in force — the shape the live API returned.
            '*/tariff/connected' => Http::response([
                'tariffs' => [
                    ['tariff_id' => '839', 'tariff_name' => 'Home 100', 'date_begin' => '2026-08-10 16:34:27', 'date_end' => null, 'tariff_isoff' => '0'],
                ],
            ]),
            '*/tariff/connect' => Http::response(['code' => 0]),
            '*/device/new' => Http::response(['code' => 0]),
            '*' => Http::response([], 200),
        ]);
    }

    /**
     * @param  int  $type  0 temporary, 1 one-off, 2 permanent
     */
    private function verifiedSubscriber(int $type = 2): self
    {
        return $this->withCookies([
            'verify' => '1',
            'account' => self::ACCOUNT_ID,
            'login' => '998901234567',
            'phone' => '998901234567',
            'data' => json_encode(['type' => $type]),
        ]);
    }
}
