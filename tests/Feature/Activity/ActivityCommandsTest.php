<?php

declare(strict_types=1);

namespace Tests\Feature\Activity;

use App\Support\Activity\ActivityStats;
use App\Support\Activity\ReportPeriod;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\UsesActivityDatabase;
use Tests\TestCase;

/**
 * activity:rollup and activity:prune — the nightly jobs.
 */
final class ActivityCommandsTest extends TestCase
{
    use UsesActivityDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        CarbonImmutable::setTestNow('2026-09-25 12:00:00');
        $this->setUpActivityDatabase();
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    #[Test]
    public function the_rollup_counts_uniques_per_bucket_and_is_safe_to_run_twice(): void
    {
        // Account A on both days, account B on the second only.
        $this->event('2026-09-23', 'page.home', 'A');
        $this->event('2026-09-23', 'page.home', 'A');
        $this->event('2026-09-24', 'page.home', 'A');
        $this->event('2026-09-24', 'page.home', 'B');
        $this->event('2026-09-24', 'tariff.connect', 'B', 'fail');

        $this->artisan('activity:rollup', ['--days' => 2])->assertSuccessful();
        $this->artisan('activity:rollup', ['--days' => 2])->assertSuccessful();

        $day1 = $this->activity()->table('activity_daily')->where('day', '2026-09-23')->where('event', 'page.home')->sole();
        $this->assertSame([2, 1], [(int) $day1->hits, (int) $day1->uniques]);

        $day2 = $this->activity()->table('activity_daily')->where('day', '2026-09-24')->where('event', 'page.home')->sole();
        $this->assertSame([2, 2], [(int) $day2->hits, (int) $day2->uniques]);

        // The month is counted from the raw rows: A once, B once — not 1 + 2.
        $month = $this->activity()->table('activity_monthly')->where('month', '2026-09-01')->where('event', 'page.home')->sole();
        $this->assertSame([4, 2], [(int) $month->hits, (int) $month->uniques]);

        $connect = $this->activity()->table('activity_daily')->where('event', 'tariff.connect')->sole();
        $this->assertSame([0, 1], [(int) $connect->ok, (int) $connect->fail]);

        // Twice run, still one row per bucket.
        $this->assertSame(3, $this->activity()->table('activity_daily')->count());
    }

    /**
     * Regression: counted step by step, "opened home" included everyone whose
     * cookie was still valid, and the funnel read 200% at step three.
     */
    #[Test]
    public function the_sign_in_funnel_only_counts_phones_that_passed_the_step_before(): void
    {
        foreach (['auth.sms_requested', 'auth.verified', 'page.home'] as $event) {
            $this->eventForPhone('2026-09-24', $event, '998900000001');
        }

        // Signed in days ago, only opened home in this range.
        $this->eventForPhone('2026-09-24', 'page.home', '998900000002');
        // Asked for an SMS and never typed the code.
        $this->eventForPhone('2026-09-24', 'auth.sms_requested', '998900000003');

        $funnel = app(ActivityStats::class)->loginFunnel(ReportPeriod::between(null, null));

        $this->assertSame([2, 1, 1], array_column($funnel, 'count'));
    }

    #[Test]
    public function the_rollup_rejects_a_malformed_date(): void
    {
        $this->artisan('activity:rollup', ['--date' => '25.09.2026'])->assertFailed();
    }

    #[Test]
    public function prune_applies_each_tables_own_retention(): void
    {
        $this->event('2025-09-01', 'page.home', 'old');      // 12+ months → gone
        $this->event('2025-11-01', 'page.home', 'recent');   // inside 12 months → kept

        $this->change('2025-09-01 10:00:00', 'success');     // a year old — money audit keeps 3 years
        $this->change('2022-09-01 10:00:00', 'success');     // past 3 years → gone
        $this->audit('2022-09-01 10:00:00');
        $this->audit('2026-01-01 10:00:00');

        $this->artisan('activity:prune')->assertSuccessful();

        $this->assertSame(['recent'], $this->activity()->table('activity_events')->pluck('account_id')->all());
        $this->assertSame(1, $this->activity()->table('tariff_changes')->count());
        $this->assertSame('2025-09-01 10:00:00', $this->activity()->table('tariff_changes')->value('created_at'));
        $this->assertSame(1, $this->activity()->table('admin_audit')->count());
    }

    #[Test]
    public function a_tariff_change_left_pending_by_a_dead_request_becomes_unknown(): void
    {
        $stale = $this->change('2026-09-25 11:30:00', 'pending');   // 30 minutes
        $fresh = $this->change('2026-09-25 11:55:00', 'pending');   // 5 minutes — still in flight

        $this->artisan('activity:prune')->assertSuccessful();

        $this->assertSame('unknown', $this->activity()->table('tariff_changes')->where('id', $stale)->value('result'));
        $this->assertSame('pending', $this->activity()->table('tariff_changes')->where('id', $fresh)->value('result'));
    }

    private function event(string $day, string $event, string $account, string $outcome = 'ok'): void
    {
        $this->activity()->table('activity_events')->insert([
            'occurred_at' => $day.' 10:00:00',
            'occurred_on' => $day,
            'event' => $event,
            'outcome' => $outcome,
            'account_id' => $account,
        ]);
    }

    private function eventForPhone(string $day, string $event, string $phone): void
    {
        $this->activity()->table('activity_events')->insert([
            'occurred_at' => $day.' 10:00:00',
            'occurred_on' => $day,
            'event' => $event,
            'outcome' => 'ok',
            'account_id' => '1001',
            'phone' => $phone,
        ]);
    }

    private function change(string $at, string $result): int
    {
        return (int) $this->activity()->table('tariff_changes')->insertGetId([
            'created_at' => $at,
            'updated_at' => $at,
            'account_id' => '1001',
            'new_tariff_id' => '9',
            'result' => $result,
        ]);
    }

    private function audit(string $at): void
    {
        $this->activity()->table('admin_audit')->insert([
            'created_at' => $at,
            'admin_id' => 1,
            'admin_username' => 'a',
            'admin_role' => 'admin',
            'action' => 'admin.stats',
            'method' => 'GET',
            'path' => '/admin/stats',
        ]);
    }
}
