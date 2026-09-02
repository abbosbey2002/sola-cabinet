@extends('layouts.app')
@section('title', trans('app.topup.title').' - ')
@section('heading', trans('app.topup.title'))
@section('lead', trans('app.topup.subline'))

@section('content')
    {{-- The full page: reachable directly (bookmark, no JS, a fresh tab),
         and the redirect target for a validation failure no matter which
         copy of the form (this page, or x-pay-card's modal) was actually
         submitted — see partials/topup-form.blade.php and
         TopUpRequest::getRedirectUrl(). --}}
    <section class="u-card u-card-hero u-rise" style="--i:1">
        @include('cabinet.partials.topup-form', ['showHeadline' => false])
    </section>
@endsection
