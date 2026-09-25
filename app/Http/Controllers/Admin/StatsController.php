<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\ReportPeriodRequest;
use App\Support\Activity\ActivityRecorder;
use App\Support\Activity\ActivityStats;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Contracts\View\View;

/**
 * /admin/stats* — what subscribers do in the cabinet, in aggregate: per menu
 * page and per action, the sign-in and tariff funnels, the audience segments
 * and the most frequent failures.
 *
 * Aggregates only, no personal data, so every role may open these. With the
 * journal switched off the pages render and say so instead of querying a
 * database that may not exist yet.
 */
final class StatsController
{
    public function __construct(
        private readonly ActivityStats $stats,
        private readonly ViewFactory $view,
    ) {}

    public function index(ReportPeriodRequest $request): View
    {
        $period = $request->period();
        $enabled = ActivityRecorder::isEnabled();
        $byEvent = $enabled ? $this->stats->byEvent($period) : collect();

        return $this->view->make('admin.stats.index', [
            'period' => $period,
            'enabled' => $enabled,
            'summary' => $enabled ? $this->stats->summary($period) : null,
            'menu' => $byEvent->filter(fn (object $row): bool => str_starts_with($row->event, 'page.'))->values(),
            'actions' => $byEvent->reject(fn (object $row): bool => str_starts_with($row->event, 'page.'))->values(),
            'daily' => $enabled ? $this->stats->daily($period) : [],
            'monthly' => $enabled ? $this->stats->monthly() : collect(),
        ]);
    }

    public function funnel(ReportPeriodRequest $request): View
    {
        $period = $request->period();
        $enabled = ActivityRecorder::isEnabled();

        return $this->view->make('admin.stats.funnel', [
            'period' => $period,
            'enabled' => $enabled,
            'login' => $enabled ? $this->stats->loginFunnel($period) : [],
            'tariff' => $enabled ? $this->stats->tariffFunnel($period) : [],
        ]);
    }

    public function segments(ReportPeriodRequest $request): View
    {
        $period = $request->period();
        $segment = $request->segmentColumn();
        $enabled = ActivityRecorder::isEnabled();

        return $this->view->make('admin.stats.segments', [
            'period' => $period,
            'enabled' => $enabled,
            'segment' => $segment,
            'segments' => ActivityStats::SEGMENTS,
            'rows' => $enabled ? $this->stats->segment($period, $segment) : collect(),
        ]);
    }

    public function errors(ReportPeriodRequest $request): View
    {
        $period = $request->period();
        $enabled = ActivityRecorder::isEnabled();

        return $this->view->make('admin.stats.errors', [
            'period' => $period,
            'enabled' => $enabled,
            'rows' => $enabled ? $this->stats->errors($period) : collect(),
        ]);
    }
}
