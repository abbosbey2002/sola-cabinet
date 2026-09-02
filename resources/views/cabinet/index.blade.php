@extends('layouts.app')
@section('title', trans('app.nav.home').' - ')
@section('heading', trans('app.nav.home'))

@section('content')
    @include('cabinet.dashboard.'.$dash->kind)

    @if ($dash->canTopUp)
        <x-modal name="topup-modal" variant="topup" :title="__('app.topup.title')">
            @include('cabinet.partials.topup-form', ['compact' => true])
        </x-modal>
    @endif
@endsection
