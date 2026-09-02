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
        @include('payment.result')
    </div>
@endsection
