<!DOCTYPE html>
<html lang="">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">

    <title>@yield('title'){{ env('APP_NAME') }}</title>

    <link rel="stylesheet" href="/vendor/desktop/css/style.css">
    <link rel="stylesheet" href="/vendor/desktop/css/media.css">
    <link rel="stylesheet" href="/vendor/desktop/fonts/museosanscyrl/museosanscyrl.css">
    <link rel="stylesheet" href="/vendor/desktop/libs/bootstrap/css/bootstrap.min.css">
    <link rel="shortcut icon" href="/vendor/mobile/images/bird.png" type="image/x-icon">

    <link rel="stylesheet" href="/vendor/toastr/build/toastr.min.css">

    @stack('css')
</head>

<body>

<nav class="touch-side-swipe responsive-nav py-3">

    <div class="container p-0 position-relative">
        <div id="menu">
            <div class="mr-4">
                <a href="https://sola.uz">
                    <img src="/vendor/desktop/images/main-logo-h.png" alt="{{ env('APP_NAME') }}">
                </a>
            </div>

            <div class="mycontainer2">
                <ul class="main-ul">
                    <li class="{{ request()->routeIs('cabinet') ? 'active' : '' }}">
                        <a href="{{ route('cabinet') }}" class="link">
                            <div class="mr-2 h_icon"></div>
                            <span>@lang('app.menu.data')</span>
                        </a>
                    </li>

{{--                    @if(SetCookie::getTypee() ==  2)--}}
{{--                        <li class="{{ request()->routeIs('tariffs') ? 'active' : '' }}">--}}
{{--                            <a href="{{ route('tariffs') }}" class="link">--}}
{{--                                <div class="mr-2 h_icon"></div>--}}
{{--                                <span>@lang('app.menu.tariff')</span>--}}
{{--                            </a>--}}
{{--                        </li>--}}
{{--                    @endif--}}

                    <li class="{{ request()->routeIs('traffic') ? 'active' : '' }}">
                        <a href="{{ route('traffic') }}" class="link">
                            <div class="mr-2 h_icon"></div>
                            <span>@lang('app.menu.traffic')</span>
                        </a>
                    </li>
                    <li class="{{ request()->routeIs('payment') ? 'active' : '' }}">
                        <a href="{{ route('payment') }}" class="link">
                            <div class="mr-2 h_icon"></div>
                            <span>@lang('app.menu.payments')</span>
                        </a>
                    </li>


<li class="">
                        <a href="https://sola.speedtestcustom.com/" target="_blank" class="link">
                            <div class="mr-2 h_icon"></div>
                            <span>Speed Test</span>
                        </a>
                    </li>

{{--                    <li class="{{ request()->routeIs('services') ? 'active' : '' }}">--}}
{{--                        <a href="{{ route('services') }}" class="link">--}}
{{--                            <div class="mr-2 h_icon"></div>--}}
{{--                            <span>@lang('app.menu.services')</span>--}}
{{--                        </a>--}}
{{--                    </li>--}}
                    <li class="">
                        <a href="#section05" class="link"></a>
                    </li>
                </ul> <!-- main-ul -->

            </div> <!-- mycontainer -->

            <div class="lang">
                <div class="lang-content">
                    @if(App::isLocale('uz'))
                        <a href="#uz" class="lang-dropbtn">UZ</a>
                        <div class="lang-dropdown">
                            <a href="{{ route('change.lang', ['ru']) }}">RU</a>
                            <a href="{{ route('change.lang', ['en']) }}">En</a>
                        </div>
                    @elseif(App::isLocale('ru'))
                        <a href="#ru" class="lang-dropbtn">RU</a>
                        <div class="lang-dropdown">
                            <a href="{{ route('change.lang', ['uz']) }}">UZ</a>
                            <a href="{{ route('change.lang', ['en']) }}">En</a>
                        </div>
                    @else
                        <a href="#en" class="lang-dropbtn">EN</a>
                        <div class="lang-dropdown">
                            <a href="{{ route('change.lang', ['ru']) }}">RU</a>
                            <a href="{{ route('change.lang', ['uz']) }}">UZ</a>
                        </div>
                    @endif
                </div>
            </div>

{{--            <span class="text-white pt-1 mt-2 mx-2">|</span>--}}
{{--            <a href="{{ route('logout') }}" class="main_color  px-0 mx-0">@lang('app.menu.logout')</a>--}}
            @if(count($accounts['body']['accs']) != 1 )
                <div class="drop">
                <div class="drop-btn">
                    <div class="user__item active">
                        <a href="#">
                            <div class="user__info">
                                <div class="user__full-name">
                                    {{ request()->cookie('full_name') }}
                                </div>
                                <div class="user__email">
                                    @lang('app.accounts.personal'): {{ request()->cookie('account') }}
                                </div>
                            </div>
                            <div class="user__type">
                                @if(SetCookie::getTypee() == 0)
                                    @lang('app.accounts.tempary')
                                @elseif(SetCookie::getTypee() == 1)
                                    @lang('app.accounts.one_time')
                                @else
                                    @lang('app.accounts.current')
                                @endif
                            </div>
                        </a>
                    </div>
                </div>
                <div class="drop-down">
                    <div class="user">

                        @foreach($accounts['body']['accs'] as $account)
                            @if($account['accId'] != request()->cookie('account'))
                                <div class="user__item">
                                    <a href="{{ route('set.account', [$account['accId'], $account['abonType']]) }}">
                                        <div class="user__info">
                                            <div class="user__full-name">
                                                @if($account['abonType'] == 0)
                                                    (@lang('app.accounts.tempary'))
                                                @elseif($account['abonType'] == 1)
                                                    (@lang('app.accounts.one_time'))
                                                @else
                                                    {{ $account['abonName'] }}
                                                @endif
                                            </div>
                                            <div class="user__email">
                                                @lang('app.accounts.personal'): {{ $account['accId'] }}
                                            </div>
                                        </div>
                                        <div class="user__type">
                                            @if($account['abonType'] == 0)
                                                @lang('app.accounts.tempary')
                                            @elseif($account['abonType'] == 1)
                                                @lang('app.accounts.one_time')
                                            @else
                                                @lang('app.accounts.current')
                                            @endif
                                        </div>
                                    </a>
                                </div>
                            @endif
                        @endforeach
                    </div>
                    <div class="sign-out">
                        <a href="{{ route('logout') }}" class="my-btn main_color px-0 mx-0">
                            @lang('app.menu.logout')
                        </a>
                    </div>

                </div>
            </div>
            @else
                <span class="text-white pt-1 mt-2 mx-2">|</span>
                <a href="{{ route('logout') }}" class="main_color  px-0 mx-0">@lang('app.menu.logout')</a>
            @endif

        </div> <!-- #menu -->

    </div> <!-- container -->

</nav> <!-- touch-side-swipe responsive-nav -->

<section class="section1 pt-5 mt-4">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center bb1px">
            <p class="menu_p">@lang('app.cabinet.account'): {{ request()->cookie('account') }} | @lang('app.cabinet.status'): {{ $info['body']['status'] }} </p>
            <p class="menu_p">
                @lang('app.menu.call')
                <a href="tel:{{ env('CALL_CENTER') }}">{{ env('CALL_CENTER') }}</a>
                @lang('app.menu.or')
                <a href="tel:{{ env('CALL_PHONE') }}">{{ env('CALL_PHONE') }}</a>
            </p>
        </div>

        <div class="row py-4">
            <div class="col-lg-4">
                <div class="info_block">
                    <img src="/vendor/desktop/images/purse.png" alt="">
                    <div class="d-flex flex-column">
                        <h3>@lang('app.header.balance'):</h3>
                        <span class="green_span">{{ number_format($info['body']['saldo'], 0, '', ' ') }} @lang('app.ye')</span>
                    </div> <!-- d-flex -->
                </div> <!-- info_block -->

            </div>
            <div class="col-lg-4">
                <div class="info_block">
                    <img src="/vendor/desktop/images/tairf.png" alt="">
                    <div class="d-flex flex-column">
                        <h3>@lang('app.header.tariff'):</h3>
                        <span class="green_span">
                            @if(!empty($info['body']['curr_tariff_name']))
                                {{ $info['body']['curr_tariff_name'] }}
                            @else
                                @lang('app.header.no_tariff')
                            @endif
                        </span>
                    </div> <!-- d-flex -->
                </div> <!-- info_block -->
            </div>

            <div class="col-lg-4">
                @if(SetCookie::getTypee() ==  2)
                    <a href="{{ route('devices.add') }}" onclick="return confirm('@lang('app.header.add_device_sure')')" class="mybtn mt-2">
                        @lang('app.header.add_device')
                    </a>
                @endif
            </div>

            @if($devices)
                <div class="col-lg-12">
                        @if(session()->has('danger'))
                            <div class="alert alert-danger">
                                {{ session()->pull('danger') }}
                            </div>
                        @endif

                        @if(session()->has('info'))
                            <div class="alert alert-info">
                                {{ session()->pull('info') }}
                            </div>
                        @endif

                        <table class="table mytable table-sm">
                            <thead>
                            <tr>
                                <th scope="col">@lang('app.header.mac')</th>
                                <th scope="col">@lang('app.header.status')</th>
                                <th scope="col">@lang('app.header.date_connect')</th>
                                <th scope="col">@lang('app.actions')</th>
                            </tr>
                            </thead>
                            <tbody>
                                @foreach($devices as $device)
                                    <tr>
                                        <td>
                                            @if($device['mac'])
                                                {{ $device['mac'] }}
                                            @else
                                                @lang('app.header.no_mac')
                                            @endif
                                        </td>

                                        <td>
                                            @if ($device['ip'])
                                                @lang('app.header.online')
                                            @else
                                                @lang('app.header.offline')
                                            @endif
                                        </td>

                                        <td>
                                            {{ Carbon::parse($device['connect_date'])->format('d.m.Y') }}
                                        </td>

                                        <td>
                                            @if(!$device['readonly'])
                                                <a href="{{ route('devices.delete', $device['permit_id']) }}" onclick="return confirm(@lang('app.header.are_you_cancel'))" class="btn btn-danger btn-sm">
                                                    @lang('app.detele')
                                                </a>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                </div>
            @endif

        </div>


        @if(!empty($date))
            <div class="statics-tarif py-4">
                <p>
                    @if(!empty($month))
                        @lang('app.header.date_choose', ['Month' => $months[Carbon::parse($month)->format('n')]['name']])
                    @else
                        @lang('app.header.date_choose', ['Month' => $months[Carbon::now()->format('n')]['name']])
                    @endif
                </p>
                <button type="button" class="btn" data-toggle="modal" data-target="#statics-modal">
                    @lang('app.header.choose')
                </button>
            </div>
        @endif
    </div> <!-- mycontainer -->
</section> <!-- main-section -->


@yield('content')


<footer>
    <p>
        @lang('app.footer.copy', ['year' => date('Y', time())])
    </p>
</footer> <!-- footer -->


<script src="/vendor/desktop/js/jquery.js"></script>
<script src="/vendor/desktop/libs/bootstrap/js/bootstrap.min.js"></script>
<script src="/vendor/desktop/libs/bootstrap/js/popper.min.js"></script>
<script src="/vendor/desktop/js/smoothscroll.min.js"></script>
<script src="/vendor/desktop/js/chart.min.js"></script>
<script src="/vendor/desktop/libs/swiper-slider/swiper.min.js"></script>
<script src="/vendor/desktop/libs/touch-sideswipe-master/touch-sideswipe.min.js"></script>

<script src="/vendor/desktop/js/main.js"></script>

<script src="/vendor/toastr/build/toastr.min.js"></script>
@if($errors->any())
    <script>
        toastr.error('{{ $errors->first() }}', {
            // timeout in milliseconds
            time: 3000,
            // 'top-left', 'top-center', 'top-right', 'right-bottom', 'bottom-center', 'left-bottom'
            position: 'top-right',
        });
    </script>
@endif

@stack('js')
</body>

</html>
