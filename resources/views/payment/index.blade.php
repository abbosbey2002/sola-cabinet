@extends('layouts.app')
@section('title', trans('app.nav.payments').' - ')
@section('heading', trans('app.nav.payments'))

@section('toolbar')
    <x-period-form :action="route('payment.filter')" :period="$period" target="payments-result"/>
@endsection

@section('content')
    {{-- Paper only. On screen the top row says whose cabinet this is; the paper
         has no top row, and a statement that names neither the subscriber nor
         the account is a table someone printed, not a document. --}}
    <div class="hidden print:mb-5 print:block">
        <p class="text-lg font-semibold text-ink">{{ config('app.name') }} — @lang('app.payment.title')</p>
        @if (filled($profile->fullName()))
            <p class="text-sm text-muted">{{ $profile->fullName() }}</p>
        @endif
        @if (filled($profile->contractNumber()))
            <p class="text-sm text-muted">@lang('app.dash.contract'): {{ $profile->contractNumber() }}</p>
        @endif
    </div>

    <div id="payments-result" data-ajax-region>
        @include('payment.result', ['clamped' => false])
    </div>

    {{-- Outside the AJAX region: the contract number does not change with the
         period, and it is the one thing on this page a subscriber copies out.
         The payment systems are named in plain text on purpose — an
         approximated Payme or Click mark, drawn rather than taken from the
         brand's own press kit, is what a phishing page looks like. --}}
    @if (filled($profile->contractNumber()))
        <p class="mt-4 text-sm text-muted">
            @lang('app.payment.how_to_pay')
            <span class="font-semibold text-ink">{{ $profile->contractNumber() }}</span>.
        </p>
    @endif
@endsection
