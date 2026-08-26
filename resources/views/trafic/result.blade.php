@php
    // Spec §5: the two totals, plus the per-session log the old cabinet had.
    $total = $traffic['input'] + $traffic['output'];
    $inShare = $total > 0 ? $traffic['input'] / $total : 0;
    $outShare = $total > 0 ? $traffic['output'] / $total : 0;

    $mb = fn (float $value): string => number_format($value, 0, ',', ' ');
@endphp

<x-period-note :incomplete="$traffic['incomplete']"/>

<div class="grid gap-4 lg:grid-cols-[21rem_1fr]">

    {{-- The traffic share, drawn as the same 240° arc language as the ))) in
         the logo. Incoming dwarfs outgoing on every real account, so the shape
         is doing what a pie chart cannot: showing at a glance that the split is
         lopsided, with the exact figures listed underneath. --}}
    {{-- Labelled by the caption inside the arc rather than by an sr-only
         heading of its own: the two would have said the same words, and a
         screen reader would have read the section name twice. --}}
    <section class="u-card u-rise flex min-w-0 flex-col items-center lg:self-start" aria-labelledby="share-title">
        <x-arc class="w-full max-w-[15rem]" :segments="[
            ['fraction' => $inShare, 'color' => 'var(--c-action)'],
            ['fraction' => $outShare, 'color' => 'var(--c-warn)'],
        ]">
            <p id="share-title" class="u-label">@lang('app.traffic.all_traffic')</p>
            <p class="u-figure mt-0.5 text-2xl text-ink">{{ $mb($total) }}</p>
            <p class="text-sm text-muted">@lang('app.traffic.mb')</p>
        </x-arc>

        <dl class="mt-8 grid w-full grid-cols-2 gap-4 border-t-2 border-line pt-5">
            @foreach ([
                ['label' => __('app.dash.incoming'), 'value' => $traffic['input'], 'icon' => 'in', 'color' => 'var(--c-action)'],
                ['label' => __('app.dash.outgoing'), 'value' => $traffic['output'], 'icon' => 'out', 'color' => 'var(--c-warn)'],
            ] as $series)
                <div>
                    <dt class="flex items-center gap-2 text-sm text-muted">
                        <x-icon :name="$series['icon']" size="size-4" style="color: {{ $series['color'] }}"/>{{ $series['label'] }}
                    </dt>
                    <dd class="u-figure mt-1 text-xl text-ink">{{ $mb($series['value']) }}</dd>
                </div>
            @endforeach
        </dl>
    </section>

    @if ($traffic['rows'])
        {{-- The card is nearly all table, so its padding drops to a hairline:
             at the normal card padding the rows lose a quarter of their width
             to whitespace on a phone. --}}
        <section data-table data-page-size="10" class="u-card u-rise !p-2 sm:!p-3" style="--i:1" aria-labelledby="daily-title">
            <h2 id="daily-title" class="sr-only">@lang('app.traffic.title')</h2>

            <div class="u-table-wrap u-scroll">
                <table class="u-table u-table-cards">
                    <thead>
                        <tr>
                            <th scope="col" data-sort="date">@lang('app.traffic.date')</th>
                            <th scope="col" data-sort="number" class="text-right">@lang('app.traffic.inn')</th>
                            <th scope="col" data-sort="number" class="text-right">@lang('app.traffic.outt')</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($traffic['rows'] as $row)
                            <tr>
                                <td data-label="{{ __('app.traffic.date') }}" data-value="{{ $row['event_time'] }}">
                                    {{ \Carbon\Carbon::parse($row['event_time'])->format('d.m.Y H:i') }}
                                </td>
                                <td data-label="{{ __('app.traffic.inn') }}" data-value="{{ $row['traffic_input'] }}" class="text-right font-semibold">
                                    {{ number_format($row['traffic_input'] / 1024 / 1024, 2, ',', ' ') }}
                                </td>
                                <td data-label="{{ __('app.traffic.outt') }}" data-value="{{ $row['traffic_output'] }}" class="text-right text-muted">
                                    {{ number_format($row['traffic_output'] / 1024 / 1024, 2, ',', ' ') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <x-table-nav :label="__('app.traffic.date')"/>
        </section>
    @else
        <section class="u-card u-rise" style="--i:1">
            <x-empty icon="chart" :title="__('app.empty.traffic')" :hint="__('app.empty.traffic_hint')"/>
        </section>
    @endif
</div>
