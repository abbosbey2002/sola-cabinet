<?php

declare(strict_types=1);

namespace App\Support\Activity;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * The aggregate queries behind /admin/stats*.
 *
 * "Unique" always means COUNT(DISTINCT account_id) over the chosen range,
 * computed from the raw rows in one query — summing per-day uniques would
 * count an account once for every day it came back. Grouping by day uses
 * occurred_on, a plain date column, so the SQL is the same on PostgreSQL and
 * on the test suite's SQLite.
 */
final class ActivityStats
{
    /** Columns /admin/stats/segments may group by — never a user-supplied name. */
    public const SEGMENTS = ['locale', 'device_type', 'browser', 'os', 'abon_type', 'geo_city', 'theme'];

    /**
     * Totals for the header cards.
     *
     * @return array{events: int, accounts: int, phones: int, page_views: int}
     */
    public function summary(ReportPeriod $period): array
    {
        $row = $this->events($period)
            ->selectRaw('COUNT(*) AS events')
            ->selectRaw('COUNT(DISTINCT account_id) AS accounts')
            ->selectRaw('COUNT(DISTINCT phone) AS phones')
            ->selectRaw("SUM(CASE WHEN event LIKE 'page.%' THEN 1 ELSE 0 END) AS page_views")
            ->first();

        return [
            'events' => (int) ($row->events ?? 0),
            'accounts' => (int) ($row->accounts ?? 0),
            'phones' => (int) ($row->phones ?? 0),
            'page_views' => (int) ($row->page_views ?? 0),
        ];
    }

    /**
     * Per event: attempts, unique accounts, and how they ended.
     *
     * @return Collection<int, object{event: string, hits: int, uniques: int, ok: int, fail: int, denied: int, invalid: int}>
     */
    public function byEvent(ReportPeriod $period): Collection
    {
        return $this->events($period)
            ->groupBy('event')
            ->select('event')
            ->selectRaw('COUNT(*) AS hits')
            ->selectRaw('COUNT(DISTINCT account_id) AS uniques')
            ->selectRaw("SUM(CASE WHEN outcome = 'ok' THEN 1 ELSE 0 END) AS ok")
            ->selectRaw("SUM(CASE WHEN outcome = 'fail' THEN 1 ELSE 0 END) AS fail")
            ->selectRaw("SUM(CASE WHEN outcome = 'denied' THEN 1 ELSE 0 END) AS denied")
            ->selectRaw("SUM(CASE WHEN outcome = 'invalid' THEN 1 ELSE 0 END) AS invalid")
            ->orderByDesc('hits')
            ->get()
            ->map(fn (object $row): object => (object) [
                'event' => (string) $row->event,
                'hits' => (int) $row->hits,
                'uniques' => (int) $row->uniques,
                'ok' => (int) $row->ok,
                'fail' => (int) $row->fail,
                'denied' => (int) $row->denied,
                'invalid' => (int) $row->invalid,
            ]);
    }

    /**
     * One point per day of the range, zero-filled so the chart has no gaps.
     *
     * @return list<array{day: string, page_views: int, accounts: int}>
     */
    public function daily(ReportPeriod $period): array
    {
        $rows = $this->events($period)
            ->groupBy('occurred_on')
            ->select('occurred_on')
            ->selectRaw("SUM(CASE WHEN event LIKE 'page.%' THEN 1 ELSE 0 END) AS page_views")
            ->selectRaw('COUNT(DISTINCT account_id) AS accounts')
            ->get()
            ->keyBy(fn (object $row): string => substr((string) $row->occurred_on, 0, 10));

        return array_map(fn (string $day): array => [
            'day' => $day,
            'page_views' => (int) ($rows[$day]->page_views ?? 0),
            'accounts' => (int) ($rows[$day]->accounts ?? 0),
        ], $period->days());
    }

    /**
     * The long view from the monthly roll-up, which outlives the raw events.
     *
     * @return Collection<int, object{month: string, page_views: int, sign_ins: int, tariff_changes: int, topups: int}>
     */
    public function monthly(int $months = 12): Collection
    {
        return $this->db()->table('activity_monthly')
            ->groupBy('month')
            ->select('month')
            ->selectRaw("SUM(CASE WHEN event LIKE 'page.%' THEN hits ELSE 0 END) AS page_views")
            ->selectRaw("SUM(CASE WHEN event = 'auth.verified' THEN ok ELSE 0 END) AS sign_ins")
            ->selectRaw("SUM(CASE WHEN event = 'tariff.connect' THEN ok ELSE 0 END) AS tariff_changes")
            ->selectRaw("SUM(CASE WHEN event = 'topup.initiated' THEN ok ELSE 0 END) AS topups")
            ->orderByDesc('month')
            ->limit($months)
            ->get()
            ->map(fn (object $row): object => (object) [
                'month' => substr((string) $row->month, 0, 7),
                'page_views' => (int) $row->page_views,
                'sign_ins' => (int) $row->sign_ins,
                'tariff_changes' => (int) $row->tariff_changes,
                'topups' => (int) $row->topups,
            ]);
    }

    /**
     * Sign-in funnel, by distinct phone: asked for an SMS → confirmed it →
     * reached the home page.
     *
     * A cohort, not three separate counts: each step only counts phones that
     * also passed the step before it in the same range. Counted on their own,
     * "opened home" includes everyone whose cookie was still valid and never
     * signed in at all, and the funnel would read 200% at step three.
     *
     * @return list<array{step: string, count: int}>
     */
    public function loginFunnel(ReportPeriod $period): array
    {
        return $this->funnel($period, 'phone', [
            'sms_requested' => [ActivityEvent::AuthSmsRequested, Outcome::Ok],
            'verified' => [ActivityEvent::AuthVerified, Outcome::Ok],
            'home' => [ActivityEvent::PageHome, Outcome::Ok],
        ]);
    }

    /**
     * Tariff funnel, by distinct account: opened /tariffs → sent a change →
     * the change went through — a cohort like loginFunnel(). Dialogs closed
     * without confirming are counted beside it, among those who opened the page.
     *
     * @return list<array{step: string, count: int}>
     */
    public function tariffFunnel(ReportPeriod $period): array
    {
        $steps = $this->funnel($period, 'account_id', [
            'tariffs_opened' => [ActivityEvent::PageTariffs, Outcome::Ok],
            'submitted' => [ActivityEvent::TariffConnect, null],
            'succeeded' => [ActivityEvent::TariffConnect, Outcome::Ok],
        ]);

        $cancelled = (int) $this->who($period, 'account_id', ActivityEvent::UiTariffDialogCancelled, null)
            ->whereIn('account_id', $this->who($period, 'account_id', ActivityEvent::PageTariffs, Outcome::Ok))
            ->distinct()
            ->count('account_id');

        array_splice($steps, 1, 0, [['step' => 'dialog_cancelled', 'count' => $cancelled]]);

        return $steps;
    }

    /**
     * @param  array<string, array{0: ActivityEvent, 1: ?Outcome}>  $steps
     * @return list<array{step: string, count: int}>
     */
    private function funnel(ReportPeriod $period, string $column, array $steps): array
    {
        $result = [];
        $previous = [];

        foreach ($steps as $name => [$event, $outcome]) {
            $query = $this->who($period, $column, $event, $outcome);

            foreach ($previous as [$prevEvent, $prevOutcome]) {
                $query->whereIn($column, $this->who($period, $column, $prevEvent, $prevOutcome));
            }

            $result[] = ['step' => $name, 'count' => (int) $query->distinct()->count($column)];
            $previous[] = [$event, $outcome];
        }

        return $result;
    }

    /** The column values that did $event (ending $outcome, when given) in the range. */
    private function who(ReportPeriod $period, string $column, ActivityEvent $event, ?Outcome $outcome): Builder
    {
        return $this->events($period)
            ->select($column)
            ->where('event', $event->value)
            ->when($outcome !== null, fn (Builder $query) => $query->where('outcome', $outcome?->value))
            ->whereNotNull($column);
    }

    /**
     * @return Collection<int, object{value: ?string, hits: int, uniques: int}>
     */
    public function segment(ReportPeriod $period, string $column): Collection
    {
        if (! in_array($column, self::SEGMENTS, true)) {
            return collect();
        }

        return $this->events($period)
            ->groupBy($column)
            ->select($column.' AS value')
            ->selectRaw('COUNT(*) AS hits')
            ->selectRaw('COUNT(DISTINCT account_id) AS uniques')
            ->orderByDesc('uniques')
            ->limit(30)
            ->get()
            ->map(fn (object $row): object => (object) [
                'value' => $row->value === null ? null : (string) $row->value,
                'hits' => (int) $row->hits,
                'uniques' => (int) $row->uniques,
            ]);
    }

    /**
     * The most frequent failures by event and billing code.
     *
     * @return Collection<int, object{event: string, error_code: ?string, error_message: ?string, hits: int, uniques: int}>
     */
    public function errors(ReportPeriod $period): Collection
    {
        return $this->events($period)
            ->where('outcome', Outcome::Fail->value)
            ->groupBy('event', 'error_code')
            ->select('event', 'error_code')
            ->selectRaw('MAX(error_message) AS error_message')
            ->selectRaw('COUNT(*) AS hits')
            ->selectRaw('COUNT(DISTINCT account_id) AS uniques')
            ->orderByDesc('hits')
            ->limit(50)
            ->get()
            ->map(fn (object $row): object => (object) [
                'event' => (string) $row->event,
                'error_code' => $row->error_code === null ? null : (string) $row->error_code,
                'error_message' => $row->error_message === null ? null : (string) $row->error_message,
                'hits' => (int) $row->hits,
                'uniques' => (int) $row->uniques,
            ]);
    }

    private function events(ReportPeriod $period): Builder
    {
        return $this->db()->table('activity_events')
            ->whereBetween('occurred_on', [$period->fromDate(), $period->toDate()]);
    }

    private function db(): ConnectionInterface
    {
        return DB::connection(config('activity.connection'));
    }
}
