@extends('layouts.admin')
@section('title', __('admin.accounts.title').' - ')
@section('heading', __('admin.accounts.title'))
@section('heading-icon', 'user')

@section('content')
    <p class="-mt-2 mb-5 max-w-3xl text-base text-muted">@lang('admin.accounts.intro')</p>

    <section class="u-card mb-5">
        <form method="get" action="{{ route('admin.accounts') }}" role="search" class="flex flex-wrap items-end gap-3">
            <div class="min-w-0 flex-1 sm:max-w-md">
                <label for="account-q" class="u-label mb-2 block">@lang('admin.accounts.search_label')</label>
                <div class="relative">
                    <input type="search" id="account-q" name="q" value="{{ $search }}" maxlength="64" autofocus
                           placeholder="{{ __('admin.filter.search_placeholder') }}" class="u-field py-2.5 pl-11 text-sm">
                    <x-icon name="search" class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-muted"/>
                </div>
            </div>
            <button type="submit" class="u-btn-primary u-btn-sm">@lang('admin.accounts.search')</button>
        </form>
    </section>

    @if (! $enabled)
        @include('admin.partials.journal-off')
    @elseif ($search === null)
        <section class="u-card">
            <x-empty icon="search" :title="__('admin.accounts.start')" :hint="__('admin.accounts.start_hint')"/>
        </section>
    @elseif ($accounts->isEmpty())
        <section class="u-card">
            <x-empty icon="user" :title="__('admin.accounts.none', ['q' => $search])" :hint="__('admin.accounts.none_hint')"/>
        </section>
    @else
        <section class="u-card" aria-labelledby="accounts-title">
            <h2 id="accounts-title" class="text-lg font-bold text-ink">@lang('admin.accounts.found', ['count' => $accounts->count()])</h2>
            <div class="u-table-wrap u-scroll mt-4">
                <table class="u-table u-table-cards">
                    <thead>
                        <tr>
                            <th scope="col">@lang('admin.cols.account')</th>
                            <th scope="col">@lang('admin.cols.subscriber')</th>
                            <th scope="col" class="text-right">@lang('admin.cols.events')</th>
                            <th scope="col">@lang('admin.cols.last_seen_utc')</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($accounts as $account)
                            <tr>
                                <td data-label="{{ __('admin.cols.account') }}">
                                    <a href="{{ route('admin.accounts.show', $account->account_id) }}" class="font-semibold text-action underline-offset-4 hover:underline">{{ $account->account_id }}</a>
                                    @if ($account->billing_login)
                                        <span class="block text-xs text-muted">{{ $pii->login($account->billing_login) }}</span>
                                    @endif
                                </td>
                                <td data-label="{{ __('admin.cols.subscriber') }}">
                                    <span class="block text-ink">{{ $pii->name($account->full_name) ?? '—' }}</span>
                                    <span class="block text-xs tabular-nums text-muted">{{ $pii->phone($account->phone) ?? '—' }}</span>
                                </td>
                                <td data-label="{{ __('admin.cols.events') }}" class="text-right tabular-nums">{{ number_format($account->events, 0, '', ' ') }}</td>
                                <td data-label="{{ __('admin.cols.last_seen_utc') }}" class="whitespace-nowrap tabular-nums">{{ \Carbon\CarbonImmutable::parse($account->last_seen, 'UTC')->format('d.m.Y H:i') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endif
@endsection
