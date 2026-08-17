@extends('layouts.app')
@section('title', trans('app.nav.statistics').' - ')
@section('heading', trans('app.nav.statistics'))

@section('toolbar')
    <x-period-form :action="route('traffic.filter')" :period="$period" target="traffic-result"/>
@endsection

@section('content')
    {{-- The region wraps both cards: the arc and the log answer the same
         period, so a refresh that replaced only one of them would leave a total
         on screen that no longer matches the rows under it. --}}
    <div id="traffic-result" data-ajax-region>
        @include('trafic.result', ['clamped' => false])
    </div>
@endsection
