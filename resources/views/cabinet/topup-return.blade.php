@extends('layouts.app')
@section('title', trans('app.topup.title').' - ')
@section('heading', trans('app.topup.title'))

{{-- No JS polling module: a server-rendered meta-refresh checks the same way
     every other page here already loads — works on the same flaky networks
     this whole cabinet is built for, no fetch/JS dependency, no separate
     failure mode to design for. Only refreshes while still genuinely
     checking; a credited or timed-out result is a dead end, not a loop. --}}
@if (! $credited && ! $timedOut)
    @push('head')
        <meta http-equiv="refresh" content="4">
    @endpush
@endif

@section('content')
    <section class="u-card u-card-hero u-rise text-center" style="--i:1">
        @if ($credited)
            <span class="mx-auto grid size-14 place-items-center rounded-full" style="background: var(--c-action-soft); color: var(--c-action)">
                <x-icon name="check" size="size-6"/>
            </span>
            <h2 class="u-display mt-4 text-xl text-ink">@lang('app.topup.success_title')</h2>
            <p class="mt-2 text-base text-muted">
                {{ __('app.topup.success_text', ['amount' => number_format($amount, 0, '', ' '), 'currency' => trans('app.ye')]) }}
            </p>
            <a href="{{ route('cabinet') }}" class="u-btn-primary mt-6 inline-flex">@lang('app.nav.home')</a>
        @elseif ($timedOut)
            <span class="mx-auto grid size-14 place-items-center rounded-full" style="background: var(--c-warn-soft); color: var(--c-warn)">
                <x-icon name="clock" size="size-6"/>
            </span>
            <h2 class="u-display mt-4 text-xl text-ink">@lang('app.topup.timeout_title')</h2>
            <p class="mt-2 text-base text-muted">@lang('app.topup.timeout_text')</p>
            <div class="mt-6 flex flex-wrap justify-center gap-3">
                <a href="{{ route('topup.return') }}" class="u-btn-ghost">@lang('app.topup.check_again')</a>
                <a href="{{ route('payment') }}" class="u-btn-primary">@lang('app.nav.payments')</a>
            </div>
        @else
            <span class="mx-auto grid size-14 place-items-center rounded-full" style="background: var(--c-action-soft); color: var(--c-action)">
                <x-icon name="clock" size="size-6"/>
            </span>
            <h2 class="u-display mt-4 text-xl text-ink">@lang('app.topup.checking_title')</h2>
            <p class="mt-2 text-base text-muted">@lang('app.topup.checking_text')</p>
        @endif
    </section>
@endsection
