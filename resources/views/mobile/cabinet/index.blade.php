@extends('mobile.layouts.app')
@section('content')
    <div class="top-div">
        <a href="#">
            <img src="/vendor/mobile/images/logo.png" class="main-logo responsive-logo img-width logo-img" alt="logo">
        </a>
        <a href="{{ route('logout') }}" class="exit_link">
            <img src="/vendor/mobile/images/exit.png" class="img-width" alt="">
        </a>
    </div>

    <!-- MOBILE MENU -->

    @include('mobile.include.menu')

    <div class="nav_noner">

    </div>


    @include('mobile.include.info', ['info' => $info, 'tariff' => true])


    <section class="dark_bg section section2">
        <div class="mycontainer">
            <div class="myborder_bottom2 py-3">

                <h3>Личные данные:</h3>

                <div class="d-flex">
                    <div class="minw-50">
                        <span>Ваш лицевой счет:</span>
                    </div> <!-- minw-50 -->
                    <div>
                        <span>{{ request()->cookie('account') }}</span>
                    </div>
                </div> <!-- d-flex -->

                <div class="d-flex">
                    <div class="minw-50">
                        <span>Логин:</span>
                    </div> <!-- minw-50 -->
                    <div>
                        <span>{{ request()->cookie('login') }} </span>
                    </div>
                </div> <!-- d-flex -->

                <div class="d-flex">
                    <div class="minw-50">
                        <span>Дата подключения:</span>
                    </div> <!-- minw-50 -->
                    <div>
                        <span>{{ date('d.m.Y', strtotime($info['body']['contract_date'])) }} </span>
                    </div>
                </div> <!-- d-flex -->
                @if(!empty($info['body']['name']))
                <div class="d-flex">
                    <div class="minw-50">
                        <span>Ф.И.О.:</span>
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
                        <span>Email:</span>
                    </div> <!-- minw-50 -->
                    <div>
                        <span>{{ $info['body']['email'] }}</span>
                    </div>
                </div> <!-- d-flex -->
                @endif

                @if(!empty($info['body']['phone']))
                <div class="d-flex">
                    <div class="minw-50">
                        <span>Телефон:</span>
                    </div> <!-- minw-50 -->
                    <div>
                        <span>{{ $info['body']['phone'] }}</span>
                    </div>
                </div> <!-- d-flex -->
                @endif

                @if(!empty($info['body']['address']))
                    <div class="d-flex">
                        <div class="minw-50">
                            <span>Адресс подключения:</span>
                        </div> <!-- minw-50 -->
                        <div>
                            <span>{{ $info['body']['address'] }}</span>
                        </div>
                    </div> <!-- d-flex -->
                @endif
                <div class="d-flex">
                    <div class="minw-50">
                        <span>Тариф:</span>
                    </div> <!-- minw-50 -->
                    <div>
                        <span>
                            @if(!empty($info['body']['curr_tariff_name']))
                                {{ $info['body']['curr_tariff_name'] }}
                            @else
                                Нет тарифа
                            @endif
                        </span>
                    </div>
                </div> <!-- d-flex -->

            </div> <!-- myborder_bottom -->

            <div class="myborder_bottom2 py-3">

                <h3>Дополнительные услуги:</h3>
                @if(!empty($info['body']['device_count']))
                    <div class="d-flex">
                        <div class="minw-50">
                            <span>Количество устройств: </span>
                        </div> <!-- minw-50 -->
                        <div>
                            <span>{{ $info['body']['device_count'] }}</span>
                        </div>
                    </div> <!-- d-flex -->
                @endif

                <div class="d-flex">
                    <div class="minw-50">
                        <span>Активных устройств: </span>
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

{{--            <div class="myborder_bottom2 py-3">--}}

{{--                <h3>Состояние трафика:</h3>--}}
{{--                <div class="d-flex">--}}
{{--                    <div class="minw-50">--}}
{{--                        <span>Входящий: </span>--}}
{{--                    </div> <!-- minw-50 -->--}}
{{--                    <div>--}}
{{--                        <span>45088.2 MB  </span>--}}
{{--                    </div>--}}
{{--                </div> <!-- d-flex -->--}}

{{--                <div class="d-flex">--}}
{{--                    <div class="minw-50">--}}
{{--                        <span>Исходящий:</span>--}}
{{--                    </div> <!-- minw-50 -->--}}
{{--                    <div>--}}
{{--                        <span>5294.6 MB </span>--}}
{{--                    </div>--}}
{{--                </div> <!-- d-flex -->--}}


{{--            </div> <!-- myborder_bottom -->--}}

            <div class="py-3">

                <h3>Баланс счета:</h3>
                <div class="d-flex">
                    <div class="minw-50">
                        <span>На счету: </span>
                    </div> <!-- minw-50 -->
                    <div>
                        <span>{{ number_format($info['body']['saldo'], 0, '', ' ') }} UZS  </span>
                    </div>
                </div> <!-- d-flex -->

            </div> <!-- myborder_bottom -->
        </div> <!-- mycontainer -->


    </section> <!-- dark_bg -->



    @include('mobile.include.footer')
@endsection