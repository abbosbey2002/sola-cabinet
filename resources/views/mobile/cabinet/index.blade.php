@extends('mobile.layouts.app')
@section('title', trans('app.cabinet.title'). ' - ')
@section('content')

    @include('mobile.include.header')

    <!-- MOBILE MENU -->

    @include('mobile.include.menu')

    <div class="nav_noner">
    </div>


    @include('mobile.include.info', ['info' => $info, 'tariff' => true])


    <section class="dark_bg section section2">
        <div class="mycontainer">
            <div class="myborder_bottom2 py-3">

                <h3>@lang('app.cabinet.personal_data'):</h3>

                <div class="d-flex">
                    <div class="minw-50">
                        <span>@lang('app.cabinet.account'):</span>
                    </div> <!-- minw-50 -->
                    <div>
                        <span>{{ request()->cookie('account') }}</span>
                    </div>
                </div> <!-- d-flex -->

                <div class="d-flex">
                    <div class="minw-50">
                        <span>@lang('app.cabinet.login'):</span>
                    </div> <!-- minw-50 -->
                    <div>
                        <span>{{ request()->cookie('login') }} </span>
                    </div>
                </div> <!-- d-flex -->

                <div class="d-flex">
                    <div class="minw-50">
                        <span>@lang('app.cabinet.date_reg'):</span>
                    </div> <!-- minw-50 -->
                    <div>
                        <span>{{ date('d.m.Y', strtotime($info['body']['contract_date'])) }} </span>
                    </div>
                </div> <!-- d-flex -->
                @if(!empty($info['body']['name']))
                    <div class="d-flex">
                        <div class="minw-50">
                            <span>@lang('app.cabinet.fio'):</span>
                        </div> <!-- minw-50 -->
                        <div>
                            <span>
                                {{ $info['body']['name'] }}
                            </span>
                        </div>
                    </div> <!-- d-flex -->
                @endif

                @if(!empty($info['body']['email']))
                <div class="d-flex">
                    <div class="minw-50">
                        <span>@lang('app.cabinet.email'):</span>
                    </div> <!-- minw-50 -->
                    <div>
                        <span>{{ $info['body']['email'] }}</span>
                    </div>
                </div> <!-- d-flex -->
                @endif

                @if(!empty($info['body']['phone']))
                <div class="d-flex">
                    <div class="minw-50">
                        <span>@lang('app.cabinet.phone'):</span>
                    </div> <!-- minw-50 -->
                    <div>
                        <span>{{ $info['body']['phone'] }}</span>
                    </div>
                </div> <!-- d-flex -->
                @endif

                @if(!empty($info['body']['address']))
                    <div class="d-flex">
                        <div class="minw-50">
                            <span>@lang('app.cabinet.connected'):</span>
                        </div> <!-- minw-50 -->
                        <div>
                            <span>{{ $info['body']['address'] }}</span>
                        </div>
                    </div> <!-- d-flex -->
                @endif
                <div class="d-flex">
                    <div class="minw-50">
                        <span>@lang('app.cabinet.tariff'):</span>
                    </div> <!-- minw-50 -->
                    <div>
                        <span>
                            @if(!empty($info['body']['curr_tariff_name']))
                                {{ $info['body']['curr_tariff_name'] }}
                            @else
                                @lang('app.cabinet.no_tariff')
                            @endif
                        </span>
                    </div>
                </div> <!-- d-flex -->

            </div> <!-- myborder_bottom -->

            <div class="myborder_bottom2 py-3">

                <h3>@lang('app.cabinet.services'):</h3>
                @if(!empty($info['body']['device_count']))
                    <div class="d-flex">
                        <div class="minw-50">
                            <span>@lang('app.cabinet.services_count'): </span>
                        </div> <!-- minw-50 -->
                        <div>
                            <span>{{ $info['body']['device_count'] }}</span>
                        </div>
                    </div> <!-- d-flex -->
                @endif

                <div class="d-flex">
                    <div class="minw-50">
                        <span>@lang('app.cabinet.active_count'): </span>
                    </div> <!-- minw-50 -->
                    <div>
                        <span>
                            @if(!empty($info['body']['device_active_count']))
                                {{ $info['body']['device_active_count'] }}
                            @else
                                0
                            @endif
                        </span>
                    </div>
                </div> <!-- d-flex -->

            </div> <!-- myborder_bottom -->


            <div class="py-3">

                <h3>@lang('app.cabinet.balance'):</h3>
                <div class="d-flex">
                    <div class="minw-50">
                        <span>@lang('app.cabinet.amount'): </span>
                    </div> <!-- minw-50 -->
                    <div>
                        <span>{{ number_format($info['body']['saldo'], 0, '', ' ') }} @lang('app.ye')  </span>
                    </div>
                </div> <!-- d-flex -->

            </div> <!-- myborder_bottom -->
        </div> <!-- mycontainer -->


    </section> <!-- dark_bg -->



    @include('mobile.include.footer')
@endsection