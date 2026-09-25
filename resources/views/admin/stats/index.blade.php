@extends('layouts.admin')
@section('title', __('admin.stats.title').' - ')
@section('heading', __('admin.stats.title'))
@section('heading-icon', 'chart')

@section('content')
    @include('admin.partials.stats-tabs')

    <section class="u-card mb-5">
        <x-admin.period-form :period="$period" :action="route('admin.stats')"/>
    </section>

    @if (! $enabled)
        @include('admin.partials.journal-off')
    @else
        @php
            $eventLabel = fn (string $event): string => __('admin.events.'.str_replace('.', '_', $event));
            $number = fn (int $value): string => number_format($value, 0, '', ' ');
            $rate = fn (object $row): ?string => ($row->ok + $row->fail) > 0
                ? number_format($row->ok * 100 / ($row->ok + $row->fail), 1, ',', '').'%'
                : null;
        @endphp

        <div class="mb-5 grid grid-cols-2 gap-3 lg:grid-cols-4">
            <x-admin.stat :label="__('admin.stats.card_accounts')" :value="$number($summary['accounts'])"
                          :hint="__('admin.stats.card_accounts_hint')"/>
            <x-admin.stat :label="__('admin.stats.card_phones')" :value="$number($summary['phones'])"/>
            <x-admin.stat :label="__('admin.stats.card_page_views')" :value="$number($summary['page_views'])"/>
            <x-admin.stat :label="__('admin.stats.card_events')" :value="$number($summary['events'])"/>
        </div>

        {{-- Daily page views: one series, so one hue and no legend box — the
             heading names it. Each bar carries its own tooltip (<title>), and the
             same numbers are one click away as a table. --}}
        @php
            $peak = max(1, max(array_column($daily, 'page_views') ?: [0]));
            $count = count($daily);
            $width = 720;
            $height = 160;
            $slot = $width / max(1, $count);
            $barWidth = max(1, $slot - 2);
        @endphp

        <section class="u-card mb-5" aria-labelledby="daily-title">
            <div class="flex flex-wrap items-baseline justify-between gap-2">
                <h2 id="daily-title" class="text-lg font-bold text-ink">@lang('admin.stats.daily_title')</h2>
                <p class="text-sm text-muted">@lang('admin.stats.daily_peak', ['count' => $number($peak)])</p>
            </div>

            @if ($summary['page_views'] === 0)
                <x-empty icon="chart" :title="__('admin.stats.empty_title')" :hint="__('admin.stats.empty_hint')"/>
            @else
                <figure class="mt-4">
                    <svg viewBox="0 0 {{ $width }} {{ $height }}" preserveAspectRatio="none" role="img"
                         aria-label="{{ __('admin.stats.daily_title') }}" class="block h-40 w-full">
                        <line x1="0" y1="{{ $height - 0.5 }}" x2="{{ $width }}" y2="{{ $height - 0.5 }}"
                              stroke="var(--c-line)" stroke-width="1" vector-effect="non-scaling-stroke"/>
                        @foreach ($daily as $i => $point)
                            @php
                                $barHeight = $point['page_views'] > 0 ? max(2, $point['page_views'] / $peak * ($height - 8)) : 0;
                                $x = $i * $slot + 1;
                                $y = $height - $barHeight;
                                $r = min(3, $barWidth / 2, $barHeight);
                            @endphp
                            <g>
                                <title>{{ \Carbon\CarbonImmutable::parse($point['day'])->format('d.m.Y') }}: {{ __('admin.stats.tooltip', ['views' => $number($point['page_views']), 'accounts' => $number($point['accounts'])]) }}</title>
                                {{-- Transparent full-height hit area: a one-view day is still easy to hover. --}}
                                <rect x="{{ $i * $slot }}" y="0" width="{{ $slot }}" height="{{ $height }}" fill="transparent"/>
                                @if ($barHeight > 0)
                                    <path fill="var(--c-action)"
                                          d="M{{ $x }},{{ $height }} V{{ $y + $r }} Q{{ $x }},{{ $y }} {{ $x + $r }},{{ $y }} H{{ $x + $barWidth - $r }} Q{{ $x + $barWidth }},{{ $y }} {{ $x + $barWidth }},{{ $y + $r }} V{{ $height }} Z"/>
                                @endif
                            </g>
                        @endforeach
                    </svg>
                    <figcaption class="mt-2 flex justify-between text-xs text-muted tabular-nums">
                        <span>{{ $period->from->format('d.m.Y') }}</span>
                        <span>{{ $period->to->format('d.m.Y') }}</span>
                    </figcaption>
                </figure>

                <details class="mt-4">
                    <summary class="cursor-pointer text-sm font-semibold text-action">@lang('admin.stats.show_table')</summary>
                    <div class="u-table-wrap u-scroll mt-3 max-h-80">
                        <table class="u-table">
                            <thead>
                                <tr>
                                    <th scope="col">@lang('admin.cols.day')</th>
                                    <th scope="col" class="text-right">@lang('admin.cols.page_views')</th>
                                    <th scope="col" class="text-right">@lang('admin.cols.accounts')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($daily as $point)
                                    <tr>
                                        <td class="tabular-nums">{{ \Carbon\CarbonImmutable::parse($point['day'])->format('d.m.Y') }}</td>
                                        <td class="text-right tabular-nums">{{ $number($point['page_views']) }}</td>
                                        <td class="text-right tabular-nums">{{ $number($point['accounts']) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </details>
            @endif
        </section>

        {{-- Stacked, not side by side: the eight-column actions table in half
             a row drops under u-table-cards' 40rem container query and turns
             into one card per action even on a wide screen. --}}
        <div class="grid gap-5">
            <section class="u-card" aria-labelledby="menu-title">
                <h2 id="menu-title" class="text-lg font-bold text-ink">@lang('admin.stats.menu_title')</h2>
                <p class="mt-1 text-sm text-muted">@lang('admin.stats.menu_hint')</p>

                @if ($menu->isEmpty())
                    <x-empty icon="menu" :title="__('admin.stats.empty_title')"/>
                @else
                    <div class="u-table-wrap u-scroll mt-4">
                        <table class="u-table u-table-cards">
                            <thead>
                                <tr>
                                    <th scope="col">@lang('admin.cols.page')</th>
                                    <th scope="col" class="text-right">@lang('admin.cols.opens')</th>
                                    <th scope="col" class="text-right">@lang('admin.cols.accounts')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($menu as $row)
                                    <tr>
                                        <td data-label="{{ __('admin.cols.page') }}" class="font-semibold text-ink">{{ $eventLabel($row->event) }}</td>
                                        <td data-label="{{ __('admin.cols.opens') }}" class="text-right tabular-nums">{{ $number($row->hits) }}</td>
                                        <td data-label="{{ __('admin.cols.accounts') }}" class="text-right tabular-nums">{{ $number($row->uniques) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>

            <section class="u-card" aria-labelledby="actions-title">
                <h2 id="actions-title" class="text-lg font-bold text-ink">@lang('admin.stats.actions_title')</h2>
                <p class="mt-1 text-sm text-muted">@lang('admin.stats.actions_hint')</p>

                @if ($actions->isEmpty())
                    <x-empty icon="gauge" :title="__('admin.stats.empty_title')"/>
                @else
                    <div class="u-table-wrap u-scroll mt-4">
                        <table class="u-table u-table-cards">
                            <thead>
                                <tr>
                                    <th scope="col">@lang('admin.cols.action')</th>
                                    <th scope="col" class="text-right">@lang('admin.cols.attempts')</th>
                                    <th scope="col" class="text-right">@lang('admin.cols.accounts')</th>
                                    <th scope="col" class="text-right">@lang('admin.outcomes.ok')</th>
                                    <th scope="col" class="text-right">@lang('admin.outcomes.fail')</th>
                                    <th scope="col" class="text-right">@lang('admin.outcomes.denied')</th>
                                    <th scope="col" class="text-right">@lang('admin.outcomes.invalid')</th>
                                    <th scope="col" class="text-right">@lang('admin.cols.success_rate')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($actions as $row)
                                    <tr>
                                        <td data-label="{{ __('admin.cols.action') }}" class="font-semibold text-ink">{{ $eventLabel($row->event) }}</td>
                                        <td data-label="{{ __('admin.cols.attempts') }}" class="text-right tabular-nums">{{ $number($row->hits) }}</td>
                                        <td data-label="{{ __('admin.cols.accounts') }}" class="text-right tabular-nums">{{ $number($row->uniques) }}</td>
                                        <td data-label="{{ __('admin.outcomes.ok') }}" class="text-right tabular-nums">{{ $number($row->ok) }}</td>
                                        <td data-label="{{ __('admin.outcomes.fail') }}" @class(['text-right tabular-nums', 'font-semibold text-danger' => $row->fail > 0])>{{ $number($row->fail) }}</td>
                                        <td data-label="{{ __('admin.outcomes.denied') }}" class="text-right tabular-nums">{{ $number($row->denied) }}</td>
                                        <td data-label="{{ __('admin.outcomes.invalid') }}" class="text-right tabular-nums">{{ $number($row->invalid) }}</td>
                                        <td data-label="{{ __('admin.cols.success_rate') }}" class="text-right tabular-nums">{{ $rate($row) ?? '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>
        </div>

        <section class="u-card mt-5" aria-labelledby="monthly-title">
            <h2 id="monthly-title" class="text-lg font-bold text-ink">@lang('admin.stats.monthly_title')</h2>
            <p class="mt-1 text-sm text-muted">@lang('admin.stats.monthly_hint')</p>

            @if ($monthly->isEmpty())
                <x-empty icon="calendar" :title="__('admin.stats.monthly_empty')" :hint="__('admin.stats.monthly_empty_hint')"/>
            @else
                <div class="u-table-wrap u-scroll mt-4">
                    <table class="u-table u-table-cards">
                        <thead>
                            <tr>
                                <th scope="col">@lang('admin.cols.month')</th>
                                <th scope="col" class="text-right">@lang('admin.cols.page_views')</th>
                                <th scope="col" class="text-right">@lang('admin.cols.sign_ins')</th>
                                <th scope="col" class="text-right">@lang('admin.cols.tariff_changes')</th>
                                <th scope="col" class="text-right">@lang('admin.cols.topups')</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($monthly as $row)
                                <tr>
                                    <td data-label="{{ __('admin.cols.month') }}" class="font-semibold tabular-nums text-ink">{{ $row->month }}</td>
                                    <td data-label="{{ __('admin.cols.page_views') }}" class="text-right tabular-nums">{{ $number($row->page_views) }}</td>
                                    <td data-label="{{ __('admin.cols.sign_ins') }}" class="text-right tabular-nums">{{ $number($row->sign_ins) }}</td>
                                    <td data-label="{{ __('admin.cols.tariff_changes') }}" class="text-right tabular-nums">{{ $number($row->tariff_changes) }}</td>
                                    <td data-label="{{ __('admin.cols.topups') }}" class="text-right tabular-nums">{{ $number($row->topups) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    @endif
@endsection
