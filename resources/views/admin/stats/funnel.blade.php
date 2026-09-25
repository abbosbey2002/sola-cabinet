@extends('layouts.admin')
@section('title', __('admin.stats.tab_funnel').' - ')
@section('heading', __('admin.stats.title'))
@section('heading-icon', 'chart')

@section('content')
    @include('admin.partials.stats-tabs')

    <section class="u-card mb-5">
        <x-admin.period-form :period="$period" :action="route('admin.stats.funnel')"/>
    </section>

    @if (! $enabled)
        @include('admin.partials.journal-off')
    @else
        @php
            $number = fn (int $value): string => number_format($value, 0, '', ' ');
            $steps = [
                'login' => ['title' => __('admin.funnel.login_title'), 'hint' => __('admin.funnel.login_hint'), 'rows' => $login],
                'tariff' => [
                    'title' => __('admin.funnel.tariff_title'),
                    'hint' => __('admin.funnel.tariff_hint'),
                    // "Closed the dialog" is beside the funnel, not a step of it.
                    'rows' => array_values(array_filter($tariff, fn (array $row): bool => $row['step'] !== 'dialog_cancelled')),
                    'aside' => collect($tariff)->firstWhere('step', 'dialog_cancelled'),
                ],
            ];
        @endphp

        <div class="grid gap-5 lg:grid-cols-2">
            @foreach ($steps as $key => $funnel)
                @php $first = max(1, $funnel['rows'][0]['count'] ?? 0); @endphp

                <section class="u-card" aria-labelledby="funnel-{{ $key }}">
                    <h2 id="funnel-{{ $key }}" class="text-lg font-bold text-ink">{{ $funnel['title'] }}</h2>
                    <p class="mt-1 text-sm text-muted">{{ $funnel['hint'] }}</p>

                    <ol class="mt-5 space-y-4">
                        @foreach ($funnel['rows'] as $i => $row)
                            @php
                                $previous = $i > 0 ? $funnel['rows'][$i - 1]['count'] : null;
                                $share = $row['count'] / $first * 100;
                            @endphp
                            <li>
                                <div class="flex items-baseline justify-between gap-3">
                                    <span class="text-sm font-semibold text-ink">{{ $i + 1 }}. {{ __('admin.funnel.steps.'.$row['step']) }}</span>
                                    <span class="u-figure text-xl text-ink">{{ $number($row['count']) }}</span>
                                </div>
                                <div class="mt-2 h-3 overflow-hidden rounded-full bg-surface-2" aria-hidden="true">
                                    <div class="h-full rounded-full bg-action" style="width: {{ number_format(max($row['count'] > 0 ? 1 : 0, $share), 2, '.', '') }}%"></div>
                                </div>
                                @if ($previous !== null)
                                    <p class="mt-1 text-xs text-muted tabular-nums">
                                        @lang('admin.funnel.of_previous', ['percent' => $previous > 0 ? number_format($row['count'] * 100 / $previous, 1, ',', '') : '—'])
                                    </p>
                                @endif
                            </li>
                        @endforeach
                    </ol>

                    @if (! empty($funnel['aside']))
                        <p class="mt-5 flex items-center gap-2 rounded-xl bg-surface-2 px-3.5 py-3 text-sm text-ink">
                            <x-icon name="close" size="size-4" class="text-muted"/>
                            @lang('admin.funnel.cancelled_aside', ['count' => $number($funnel['aside']['count'])])
                        </p>
                    @endif
                </section>
            @endforeach
        </div>
    @endif
@endsection
