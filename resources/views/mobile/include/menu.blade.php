<nav id="touchSideSwipe" class="touch-side-swipe responsive-nav">

    <div class="container p-0">
        <div id="menu">
            <div class="logo-div text-center">

            </div>

            <div class="mycontainer2">
                <ul class="main-ul">
                    <li>
                        <a href="{{ route('cabinet') }}" class="link {{ request()->routeIs('cabinet') ? 'active' : '' }}">
                            <img src="/vendor/mobile/images/link1.png" class="mr-3" alt="">
                            @lang('app.menu.data')
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('tariffs') }}" class="link {{ request()->routeIs('tariffs') ? 'active' : '' }}">
                            <img src="/vendor/mobile/images/tag.png" class="mr-3" alt="">
                            @lang('app.menu.tariff')
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('traffic') }}" class="link {{ request()->routeIs('traffic') ? 'active' : '' }}">
                            <img src="/vendor/mobile/images/link2.png" class="mr-3" alt="">
                            @lang('app.menu.traffic')
                        </a>
                    </li>
                    <li><a href="{{ route('payment') }}" class="link {{ request()->routeIs('payment') ? 'active' : '' }}">
                            <img src="/vendor/mobile/images/link3.png" class="mr-3" alt="">
                            @lang('app.menu.payments')
                        </a>
                    </li>
{{--                    <li>--}}
{{--                        <a href="{{ route('services') }}" class="link {{ request()->routeIs('services') ? 'active' : '' }}">--}}
{{--                            <img src="/vendor/mobile/images/link4.png" class="mr-3" alt="">--}}
{{--                            @lang('app.menu.services')--}}
{{--                        </a>--}}
{{--                    </li>--}}
                    <li><a href="#section04" class="link"></a></li>
                </ul> <!-- main-ul -->


                <p class="menu_p">
                    @lang('app.menu.call') <br>
                    <a href="tel:{{ env('CALL_CENTER') }}">{{ env('CALL_CENTER') }}</a>
                    @lang('app.menu.or')
                    <a href="tel:{{ env('CALL_PHONE') }}">{{ env('CALL_PHONE') }}</a>
                </p>
            </div> <!-- mycontainer -->

        </div> <!-- #menu -->

    </div> <!-- container -->
    <p class="menu_bottom_p">
        @lang('app.footer.copy', ['year' => date('Y', time())])
    </p>
</nav> <!-- touch-side-swipe responsive-nav -->