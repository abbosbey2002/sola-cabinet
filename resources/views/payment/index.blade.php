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

    {{-- Above the history, not below it: "how do I pay" is why a subscriber
         with a low or negative balance opens this page at all, and a muted
         footnote under a full table was answering that question last. The
         contract number does not change with the period, so this sits
         outside the AJAX region. The payment systems are named in plain text
         on purpose — an approximated Payme or Click mark, drawn rather than
         taken from the brand's own press kit, is what a phishing page
         looks like. --}}
    @if (config('iwon.active') && ! $profile->isLegalEntity())
        <x-pay-card class="mb-4"/>
    @endif

    <div id="payments-result" data-ajax-region>
        @include('payment.result')
    </div>
@endsection
