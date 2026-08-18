@extends('layouts.admin')
@section('title', trans('app.admin.tariffs_title').' - ')
@section('heading', trans('app.admin.tariffs_title'))

@section('content')
    @php
        $speed = fn (array $t): string => $t['tspd'].' '.($t['spdu'] === 'Mbps' ? __('app.tariff.unit_mb') : __('app.tariff.unit_kb'));

        $validity = fn (array $t): string => $t['tprd'].' '.match ($t['prdu']) {
            'HOUR' => __('app.tariff.hour'),
            'MIN' => __('app.tariff.minut'),
            default => __('app.tariff.day'),
        };

        $volume = fn (array $t): string => (int) $t['vol'] === 0
            ? __('app.tariff.no_limit')
            : number_format((float) $t['vol'], 0, '', ' ').' '.__('app.traffic.mb');
    @endphp

    <section class="u-card u-rise" aria-labelledby="tariffs-title" data-table data-page-size="50" data-bulk-select>
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h2 id="tariffs-title" class="text-xl font-bold text-ink">@lang('app.admin.tariffs_title')</h2>
                <p class="mt-1 text-base text-muted">@lang('app.admin.tariffs_intro')</p>
            </div>

            <form method="get" action="{{ route('admin.tariffs') }}" class="flex items-end gap-2.5">
                <div>
                    <label for="acc_id" class="u-label mb-2 block">@lang('app.admin.account_label')</label>
                    <input type="text" id="acc_id" name="acc_id" value="{{ $accountId }}"
                           inputmode="numeric" class="u-field w-40">
                </div>
                <button type="submit" class="u-btn-ghost u-btn-sm">@lang('app.admin.apply')</button>
            </form>
        </div>

        @if (! $tariffs)
            <x-empty icon="tag" :title="__('app.admin.no_tariffs')"/>
        @else
            {{-- Search / status filter / bulk-action bar — all three narrow or
                 act on the same row set, so they share one row above the table. --}}
            <div class="u-no-print mt-5 flex flex-wrap items-center gap-3 border-b-2 border-line pb-4">
                <label class="relative min-w-0 flex-1 sm:max-w-xs">
                    <span class="sr-only">@lang('app.dash.search')</span>
                    <input type="search" data-table-search placeholder="{{ __('app.dash.search') }}"
                           class="u-field py-2.5 pl-11 text-sm">
                    <x-icon name="search" class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-muted"/>
                </label>

                <div class="flex flex-wrap gap-2" role="group" aria-label="{{ __('app.header.status') }}">
                    <button type="button" class="u-choice" data-filter-value="" aria-pressed="true">@lang('app.admin.filter_all')</button>
                    <button type="button" class="u-choice" data-filter-value="enabled" aria-pressed="false">@lang('app.admin.filter_enabled')</button>
                    <button type="button" class="u-choice" data-filter-value="disabled" aria-pressed="false">@lang('app.admin.filter_disabled')</button>
                </div>

                {{-- JS shows this only once something is checked (bulk-select.js);
                     without JS it stays on screen and both buttons still work,
                     since every checkbox and button here already carries the
                     name/value/form the bulk-toggle form at the bottom needs. --}}
                <div data-bulk-bar class="ml-auto flex flex-wrap items-center gap-2.5 rounded-xl bg-surface-2 px-3.5 py-2">
                    <span data-bulk-count
                          data-template="{{ __('app.admin.bulk_selected', ['count' => '{count}']) }}"
                          class="text-sm font-semibold text-ink"></span>
                    <button type="submit" form="bulk-toggle-form" name="bulk_action" value="disable" class="u-btn-danger u-btn-sm">
                        @lang('app.admin.bulk_disable')
                    </button>
                    <button type="submit" form="bulk-toggle-form" name="bulk_action" value="enable" class="u-btn-primary u-btn-sm">
                        @lang('app.admin.bulk_enable')
                    </button>
                </div>
            </div>

            <div class="u-table-wrap u-scroll mt-4">
                <table class="u-table u-table-cards">
                    <thead>
                        <tr>
                            <th scope="col" class="w-10">
                                <input type="checkbox" data-bulk-select-all class="size-4"
                                       style="accent-color: var(--c-action)"
                                       aria-label="{{ __('app.admin.select_all') }}">
                            </th>
                            <th scope="col" data-sort="text">@lang('app.tariff.switch_title')</th>
                            <th scope="col">@lang('app.dash.available')</th>
                            <th scope="col" class="text-right" data-sort="number">@lang('app.ye')</th>
                            <th scope="col">@lang('app.header.status')</th>
                            <th scope="col" class="text-right">@lang('app.actions')</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($tariffs as $tariff)
                            @php
                                $tariffId = (int) $tariff['tariff_id'];
                                $enabled = ! in_array($tariffId, $disabled, true);
                                $name = trim((string) $tariff['tariff_name']);
                            @endphp
                            <tr data-status="{{ $enabled ? 'enabled' : 'disabled' }}">
                                <td>
                                    <input type="checkbox" data-bulk-item form="bulk-toggle-form"
                                           name="tariff_ids[]" value="{{ $tariffId }}"
                                           class="size-4" style="accent-color: var(--c-action)"
                                           aria-label="{{ $name }}">
                                </td>

                                <td data-label="{{ __('app.tariff.switch_title') }}" data-value="{{ $name }}">
                                    <span class="font-semibold text-ink">{{ $name }}</span>
                                </td>

                                <td data-label="{{ __('app.dash.available') }}">
                                    <span class="text-sm text-muted">
                                        {{ $speed($tariff) }} · {{ $validity($tariff) }}
                                        @if ((int) $tariff['vol'] !== 0)
                                            · {{ $volume($tariff) }}
                                        @endif
                                    </span>
                                </td>

                                <td data-label="{{ __('app.ye') }}" class="text-right" data-value="{{ $tariff['cost'] }}">
                                    {{ number_format($tariff['cost'] / 100, 0, '', ' ') }}
                                </td>

                                <td data-label="{{ __('app.header.status') }}">
                                    @if ($enabled)
                                        <span class="u-pill-ok">
                                            <x-icon name="check" size="size-4"/>@lang('app.admin.enabled')
                                        </span>
                                    @else
                                        <span class="u-pill-off">
                                            <x-icon name="minus" size="size-4"/>@lang('app.admin.disabled')
                                        </span>
                                    @endif
                                </td>

                                <td data-label="{{ __('app.actions') }}" class="text-right">
                                    <form method="post" action="{{ route('admin.tariffs.toggle', $tariffId) }}">
                                        @csrf
                                        <input type="hidden" name="acc_id" value="{{ $accountId }}">

                                        @if ($enabled)
                                            <button type="submit" class="u-btn-danger u-btn-sm">@lang('app.admin.disable')</button>
                                        @else
                                            <button type="submit" class="u-btn-primary u-btn-sm">@lang('app.admin.enable')</button>
                                        @endif
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <x-table-nav :label="__('app.admin.tariffs_title')"/>

            {{-- Owns no fields of its own — every input the bulk buttons need is
                 elsewhere in the page and points here via the "form" attribute,
                 so this can sit outside the table without nesting a <form>
                 inside the per-row toggle forms above. --}}
            <form id="bulk-toggle-form" method="post" action="{{ route('admin.tariffs.bulk-toggle') }}">
                @csrf
                <input type="hidden" name="acc_id" value="{{ $accountId }}">
            </form>
        @endif
    </section>
@endsection
