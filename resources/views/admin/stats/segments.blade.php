@extends('layouts.admin')
@section('title', __('admin.stats.tab_segments').' - ')
@section('heading', __('admin.stats.title'))
@section('heading-icon', 'chart')

@section('content')
    @include('admin.partials.stats-tabs')

    <section class="u-card mb-5">
        <x-admin.period-form :period="$period" :action="route('admin.stats.segments')">
            <div>
                <label for="segment-by" class="u-label mb-2 block">@lang('admin.segments.by')</label>
                <select id="segment-by" name="by" class="u-field w-48 py-2.5 text-sm">
                    @foreach ($segments as $option)
                        <option value="{{ $option }}" @selected($option === $segment)>{{ __('admin.segments.columns.'.$option) }}</option>
                    @endforeach
                </select>
            </div>
        </x-admin.period-form>
    </section>

    @if (! $enabled)
        @include('admin.partials.journal-off')
    @else
        @php
            $number = fn (int $value): string => number_format($value, 0, '', ' ');
            $total = max(1, $rows->sum('uniques'));
            $valueLabel = function (?string $value) use ($segment): string {
                if ($value === null || $value === '') {
                    return __('admin.segments.unknown');
                }

                $known = fn (string $key): string => \Illuminate\Support\Facades\Lang::has($key) ? __($key) : $value;

                return match ($segment) {
                    'abon_type' => $known('admin.abon_types.'.$value),
                    'device_type', 'theme' => $known('admin.segments.values.'.$value),
                    'locale' => strtoupper($value),
                    default => $value,
                };
            };
        @endphp

        <section class="u-card" aria-labelledby="segments-title">
            <h2 id="segments-title" class="text-lg font-bold text-ink">{{ __('admin.segments.columns.'.$segment) }}</h2>
            <p class="mt-1 text-sm text-muted">@lang('admin.segments.hint')</p>

            @if ($rows->isEmpty())
                <x-empty icon="chart" :title="__('admin.stats.empty_title')" :hint="__('admin.stats.empty_hint')"/>
            @else
                <div class="u-table-wrap u-scroll mt-4">
                    <table class="u-table u-table-cards">
                        <thead>
                            <tr>
                                <th scope="col">@lang('admin.cols.value')</th>
                                <th scope="col" class="w-2/5">@lang('admin.cols.share')</th>
                                <th scope="col" class="text-right">@lang('admin.cols.accounts')</th>
                                <th scope="col" class="text-right">@lang('admin.cols.events')</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rows as $row)
                                @php $share = $row->uniques / $total * 100; @endphp
                                <tr>
                                    <td data-label="{{ __('admin.cols.value') }}" class="font-semibold text-ink">{{ $valueLabel($row->value) }}</td>
                                    <td data-label="{{ __('admin.cols.share') }}">
                                        <div class="flex items-center gap-3">
                                            <div class="h-2.5 flex-1 overflow-hidden rounded-full bg-surface-2" aria-hidden="true">
                                                <div class="h-full rounded-full bg-action" style="width: {{ number_format($share, 2, '.', '') }}%"></div>
                                            </div>
                                            <span class="w-14 text-right text-sm tabular-nums text-muted">{{ number_format($share, 1, ',', '') }}%</span>
                                        </div>
                                    </td>
                                    <td data-label="{{ __('admin.cols.accounts') }}" class="text-right tabular-nums">{{ $number($row->uniques) }}</td>
                                    <td data-label="{{ __('admin.cols.events') }}" class="text-right tabular-nums">{{ $number($row->hits) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    @endif
@endsection
