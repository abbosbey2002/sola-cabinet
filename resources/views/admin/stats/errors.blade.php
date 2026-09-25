@extends('layouts.admin')
@section('title', __('admin.stats.tab_errors').' - ')
@section('heading', __('admin.stats.title'))
@section('heading-icon', 'chart')

@section('content')
    @include('admin.partials.stats-tabs')

    <section class="u-card mb-5">
        <x-admin.period-form :period="$period" :action="route('admin.stats.errors')"/>
    </section>

    @if (! $enabled)
        @include('admin.partials.journal-off')
    @else
        @php $number = fn (int $value): string => number_format($value, 0, '', ' '); @endphp

        <section class="u-card" aria-labelledby="errors-title">
            <h2 id="errors-title" class="text-lg font-bold text-ink">@lang('admin.errors.title')</h2>
            <p class="mt-1 text-sm text-muted">@lang('admin.errors.hint')</p>

            @if ($rows->isEmpty())
                <x-empty icon="check" :title="__('admin.errors.empty')" :hint="__('admin.errors.empty_hint')"/>
            @else
                <div class="u-table-wrap u-scroll mt-4">
                    <table class="u-table u-table-cards">
                        <thead>
                            <tr>
                                <th scope="col">@lang('admin.cols.action')</th>
                                <th scope="col">@lang('admin.cols.error_code')</th>
                                <th scope="col">@lang('admin.cols.error_message')</th>
                                <th scope="col" class="text-right">@lang('admin.cols.times')</th>
                                <th scope="col" class="text-right">@lang('admin.cols.accounts')</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rows as $row)
                                <tr>
                                    <td data-label="{{ __('admin.cols.action') }}" class="font-semibold text-ink">{{ __('admin.events.'.str_replace('.', '_', $row->event)) }}</td>
                                    <td data-label="{{ __('admin.cols.error_code') }}" class="tabular-nums">
                                        <span class="u-pill-off">{{ $row->error_code ?? '—' }}</span>
                                    </td>
                                    <td data-label="{{ __('admin.cols.error_message') }}" class="max-w-md">
                                        <span class="line-clamp-2 text-sm" title="{{ $row->error_message }}">{{ $row->error_message ?? '—' }}</span>
                                    </td>
                                    <td data-label="{{ __('admin.cols.times') }}" class="text-right tabular-nums">{{ $number($row->hits) }}</td>
                                    <td data-label="{{ __('admin.cols.accounts') }}" class="text-right tabular-nums">{{ $number($row->uniques) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    @endif
@endsection
