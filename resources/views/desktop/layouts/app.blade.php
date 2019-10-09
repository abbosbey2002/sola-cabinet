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
    <link rel='stylesheet prefetch'
          href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.css'>

    <link rel="stylesheet" href="/vendor/toastr/build/toastr.min.css">

    @stack('css')
</head>

<body>

<nav class="touch-side-swipe responsive-nav py-3">

    <div class="container p-0">
        <div id="menu">
            <div class="mr-4">
                <a href="{{ route('cabinet') }}">
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

                    <li class="{{ request()->routeIs('tariffs') ? 'active' : '' }}">
                        <a href="{{ route('tariffs') }}" class="link">
                            <div class="mr-2 h_icon"></div>
                            <span>@lang('app.menu.tariff')</span>
                        </a>
                    </li>

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

            <span class="text-white pt-1 mt-2 mx-2">|</span>
            <a href="{{ route('logout') }}" class="main_color  px-0 mx-0">@lang('app.menu.logout')</a>


        </div> <!-- #menu -->

    </div> <!-- container -->

</nav> <!-- touch-side-swipe responsive-nav -->

<section class="section1">
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
                    @if(!empty($tariff))
                        <a href="{{ route('tariffs') }}" class="mybtn mt-2">
                            @lang('app.header.change_tariff')
                        </a>
                    @endif
                @endif
            </div>
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