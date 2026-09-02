@extends('layouts.app')
@section('title', trans('app.nav.home').' - ')
@section('heading', trans('app.nav.home'))

@section('content')
    @include('cabinet.dashboard.'.$dash->kind)

    @if (config('web3.active'))
        <x-web3-panel class="mt-4"/>
    @endif

    @if ($dash->canTopUp)
        <x-modal name="topup-modal" variant="topup" :title="__('app.topup.title')" :subtitle="__('app.topup.subline')">
            @include('cabinet.partials.topup-form', ['compact' => true])
        </x-modal>
    @endif
@endsection
