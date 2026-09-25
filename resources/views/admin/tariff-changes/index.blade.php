@extends('layouts.admin')
@section('title', __('admin.tc.title').' - ')
@section('heading', __('admin.tc.title'))
@section('heading-icon', 'refresh')

@section('content')
    @php
        $number = fn (int $value): string => number_format($value, 0, '', ' ');
        // Soʻm, kopecks only when there are any — a balance of 896451.61 keeps them.
        $som = function (mixed $value): string {
            if ($value === null || $value === '') {
                return '—';
            }

            $amount = (float) $value;

            return number_format($amount, fmod($amount, 1.0) === 0.0 ? 0 : 2, ',', ' ');
        };
        $when = fn (string $timestamp): string => \Carbon\CarbonImmutable::parse($timestamp, 'UTC')->format('d.m.Y H:i');
        $query = $filter->toQuery();
    @endphp

    <p class="-mt-2 mb-5 max-w-3xl text-base text-muted">@lang('admin.tc.intro')</p>

    <section class="u-card mb-5" aria-label="@lang('admin.filter.label')">
        <form method="get" action="{{ route('admin.tariff-changes') }}" class="u-no-print grid gap-3 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-7">
            <div>
                <label for="tc-from" class="u-label mb-2 block">@lang('admin.period.from')</label>
                <input type="date" id="tc-from" name="from" value="{{ $filter->period->fromDate() }}" class="u-field py-2.5 text-sm">
            </div>
            <div>
                <label for="tc-to" class="u-label mb-2 block">@lang('admin.period.to')</label>
                <input type="date" id="tc-to" name="to" value="{{ $filter->period->toDate() }}" class="u-field py-2.5 text-sm">
            </div>
            <div>
                <label for="tc-result" class="u-label mb-2 block">@lang('admin.filter.result')</label>
                <select id="tc-result" name="result" class="u-field py-2.5 text-sm">
                    <option value="">@lang('app.admin.filter_all')</option>
                    @foreach ($results as $result)
                        <option value="{{ $result->value }}" @selected($filter->result === $result)>{{ __('admin.results.'.$result->value) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="tc-old" class="u-label mb-2 block">@lang('admin.filter.old_tariff')</label>
                <select id="tc-old" name="old_tariff" class="u-field py-2.5 text-sm">
                    <option value="">@lang('app.admin.filter_all')</option>
                    @foreach ($knownTariffs as $id => $name)
                        <option value="{{ $id }}" @selected($filter->oldTariffId === (string) $id)>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="tc-new" class="u-label mb-2 block">@lang('admin.filter.new_tariff')</label>
                <select id="tc-new" name="new_tariff" class="u-field py-2.5 text-sm">
                    <option value="">@lang('app.admin.filter_all')</option>
                    @foreach ($knownTariffs as $id => $name)
                        <option value="{{ $id }}" @selected($filter->newTariffId === (string) $id)>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="tc-type" class="u-label mb-2 block">@lang('admin.filter.abon_type')</label>
                <select id="tc-type" name="abon_type" class="u-field py-2.5 text-sm">
                    <option value="">@lang('app.admin.filter_all')</option>
                    @foreach ([0, 1, 2, 3] as $type)
                        <option value="{{ $type }}" @selected($filter->abonType === $type)>{{ __('admin.abon_types.'.$type) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="tc-q" class="u-label mb-2 block">@lang('admin.filter.search')</label>
                <input type="search" id="tc-q" name="q" value="{{ $filter->search }}" maxlength="64"
                       placeholder="{{ __('admin.filter.search_placeholder') }}" class="u-field py-2.5 text-sm">
            </div>

            <div class="flex flex-wrap items-center gap-2.5 sm:col-span-2 lg:col-span-4 xl:col-span-7">
                <button type="submit" class="u-btn-primary u-btn-sm">@lang('app.admin.apply')</button>
                <a href="{{ route('admin.tariff-changes') }}" class="u-btn-ghost u-btn-sm">@lang('admin.filter.reset')</a>

                @if ($enabled && $canExport)
                    <a href="{{ route('admin.tariff-changes.export', $query) }}" class="u-btn-outline u-btn-sm ml-auto">
                        <x-icon name="download" size="size-4"/>@lang('admin.tc.export')
                    </a>
                @endif
            </div>
        </form>
    </section>

    @if (! $enabled)
        @include('admin.partials.journal-off')
    @else
        <div class="mb-5 grid grid-cols-2 gap-3 lg:grid-cols-4">
            <x-admin.stat :label="__('admin.tc.card_attempts')" :value="$number($summary['attempts'])"/>
            <x-admin.stat :label="__('admin.tc.card_success_rate')" tone="ok"
                          :value="$summary['success_rate'] === null ? '—' : number_format($summary['success_rate'], 1, ',', '').'%'"
                          :hint="__('admin.tc.card_succeeded', ['count' => $number($summary['succeeded'])])"/>
            <x-admin.stat :label="__('admin.tc.card_insufficient')" tone="warn" :value="$number($summary['insufficient_funds'])"
                          :hint="__('admin.tc.card_insufficient_hint')"/>
            <x-admin.stat :label="__('admin.stats.card_accounts')" :value="$number($summary['accounts'])"/>
        </div>

        <section class="u-card" aria-labelledby="tc-list-title">
            <div class="flex flex-wrap items-baseline justify-between gap-2">
                <h2 id="tc-list-title" class="text-lg font-bold text-ink">@lang('admin.tc.list_title')</h2>
                @unless ($pii->isUnmasked())
                    <p class="flex items-center gap-1.5 text-sm text-muted">
                        <x-icon name="shield" size="size-4"/>@lang('admin.masked_note')
                    </p>
                @endunless
            </div>

            @if ($changes->isEmpty())
                <x-empty icon="refresh" :title="__('admin.tc.empty')" :hint="__('admin.tc.empty_hint')"/>
            @else
                <div class="u-table-wrap u-scroll mt-4">
                    <table class="u-table u-table-cards">
                        <thead>
                            <tr>
                                <th scope="col">@lang('admin.cols.when_utc')</th>
                                <th scope="col">@lang('admin.cols.account')</th>
                                <th scope="col">@lang('admin.cols.subscriber')</th>
                                <th scope="col">@lang('admin.cols.change')</th>
                                <th scope="col" class="text-right">@lang('admin.cols.price_diff')</th>
                                <th scope="col">@lang('admin.cols.timing')</th>
                                <th scope="col" class="text-right">@lang('admin.cols.balance')</th>
                                <th scope="col">@lang('admin.cols.result')</th>
                                <th scope="col"><span class="sr-only">@lang('admin.tc.open')</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($changes as $change)
                                @php
                                    $diff = $change->old_tariff_price !== null && $change->new_tariff_price !== null
                                        ? (float) $change->new_tariff_price - (float) $change->old_tariff_price
                                        : null;
                                @endphp
                                <tr>
                                    <td data-label="{{ __('admin.cols.when_utc') }}" class="whitespace-nowrap tabular-nums">{{ $when($change->created_at) }}</td>
                                    <td data-label="{{ __('admin.cols.account') }}">
                                        @if ($canOpenAccount)
                                            <a href="{{ route('admin.accounts.show', $change->account_id) }}" class="font-semibold text-action underline-offset-4 hover:underline">{{ $change->account_id }}</a>
                                        @else
                                            <span class="font-semibold text-ink">{{ $change->account_id }}</span>
                                        @endif
                                        @if ($change->billing_login)
                                            <span class="block whitespace-nowrap text-xs text-muted">{{ $pii->login($change->billing_login) }}</span>
                                        @endif
                                    </td>
                                    <td data-label="{{ __('admin.cols.subscriber') }}">
                                        <span class="block max-w-[16rem] truncate text-ink" title="{{ $pii->name($change->full_name) }}">{{ $pii->name($change->full_name) ?? '—' }}</span>
                                        <span class="block whitespace-nowrap text-xs tabular-nums text-muted">{{ $pii->phone($change->phone) ?? '—' }}</span>
                                    </td>
                                    <td data-label="{{ __('admin.cols.change') }}">
                                        <span class="whitespace-nowrap text-muted">{{ $change->old_tariff_name ?? '—' }}</span>
                                        <x-icon name="chevron-right" size="size-4" class="inline text-muted"/>
                                        <span class="whitespace-nowrap font-semibold text-ink">{{ $change->new_tariff_name ?? '#'.$change->new_tariff_id }}</span>
                                    </td>
                                    <td data-label="{{ __('admin.cols.price_diff') }}" class="whitespace-nowrap text-right tabular-nums">
                                        @if ($diff === null)
                                            —
                                        @else
                                            {{ $diff > 0 ? '+' : '' }}{{ $som($diff) }}
                                        @endif
                                    </td>
                                    <td data-label="{{ __('admin.cols.timing') }}">{{ $change->timing ? __('admin.timing.'.$change->timing) : '—' }}</td>
                                    <td data-label="{{ __('admin.cols.balance') }}" class="whitespace-nowrap text-right tabular-nums">{{ $som($change->balance_before) }}</td>
                                    <td data-label="{{ __('admin.cols.result') }}">
                                        <x-admin.result-pill :result="$change->result"/>
                                        @if ($change->denied_reason)
                                            <span class="mt-1 block text-xs text-muted">{{ __('admin.denied.'.$change->denied_reason) }}</span>
                                        @elseif ($change->error_code)
                                            <span class="mt-1 block text-xs text-muted">@lang('admin.tc.code', ['code' => $change->error_code])</span>
                                        @endif
                                    </td>
                                    <td class="text-right">
                                        <a href="{{ route('admin.tariff-changes.show', $change->id) }}" class="u-btn-ghost u-btn-sm"
                                           aria-label="{{ __('admin.tc.open_n', ['id' => $change->id]) }}">
                                            <x-icon name="view" size="size-4"/>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <x-admin.pager :paginator="$changes"/>
            @endif
        </section>

        <div class="mt-5 grid gap-5 lg:grid-cols-2">
            <section class="u-card" aria-labelledby="tc-top-title">
                <h2 id="tc-top-title" class="text-lg font-bold text-ink">@lang('admin.tc.top_title')</h2>
                @if ($topTariffs->isEmpty())
                    <x-empty icon="tag" :title="__('admin.tc.empty')"/>
                @else
                    <div class="u-table-wrap u-scroll mt-4">
                        <table class="u-table u-table-cards">
                            <thead>
                                <tr>
                                    <th scope="col">@lang('admin.cols.tariff')</th>
                                    <th scope="col" class="text-right">@lang('admin.cols.attempts')</th>
                                    <th scope="col" class="text-right">@lang('admin.results.success')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($topTariffs as $row)
                                    <tr>
                                        <td data-label="{{ __('admin.cols.tariff') }}" class="font-semibold text-ink">{{ $row->new_tariff_name ?? '#'.$row->new_tariff_id }}</td>
                                        <td data-label="{{ __('admin.cols.attempts') }}" class="text-right tabular-nums">{{ $number($row->attempts) }}</td>
                                        <td data-label="{{ __('admin.results.success') }}" class="text-right tabular-nums">{{ $number($row->succeeded) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>

            <section class="u-card" aria-labelledby="tc-moves-title">
                <h2 id="tc-moves-title" class="text-lg font-bold text-ink">@lang('admin.tc.moves_title')</h2>
                @if ($transitions->isEmpty())
                    <x-empty icon="refresh" :title="__('admin.tc.empty')"/>
                @else
                    <div class="u-table-wrap u-scroll mt-4">
                        <table class="u-table u-table-cards">
                            <thead>
                                <tr>
                                    <th scope="col">@lang('admin.cols.from_tariff')</th>
                                    <th scope="col">@lang('admin.cols.to_tariff')</th>
                                    <th scope="col" class="text-right">@lang('admin.cols.attempts')</th>
                                    <th scope="col" class="text-right">@lang('admin.results.success')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($transitions as $row)
                                    <tr>
                                        <td data-label="{{ __('admin.cols.from_tariff') }}" class="text-muted">{{ $row->old_tariff_name ?? '—' }}</td>
                                        <td data-label="{{ __('admin.cols.to_tariff') }}" class="font-semibold text-ink">{{ $row->new_tariff_name ?? '—' }}</td>
                                        <td data-label="{{ __('admin.cols.attempts') }}" class="text-right tabular-nums">{{ $number($row->attempts) }}</td>
                                        <td data-label="{{ __('admin.results.success') }}" class="text-right tabular-nums">{{ $number($row->succeeded) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>
        </div>
    @endif
@endsection
