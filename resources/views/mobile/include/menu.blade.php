<nav id="touchSideSwipe" class="touch-side-swipe responsive-nav">

    <div class="container p-0">
        <div id="menu">
            <div class="logo-div text-center">

            </div>
            <a href="#" class="main_color text-center pb-4">Узбекский язык</a>

            <div class="mycontainer2">
                <ul class="main-ul">
                    <li>
                        <a href="{{ route('cabinet') }}" class="link {{ request()->routeIs('cabinet') ? 'active' : '' }}">
                            <img src="/vendor/mobile/images/link1.png" class="mr-3" alt="">
                            ДАННЫЕ
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('tariffs') }}" class="link {{ request()->routeIs('tariffs') ? 'active' : '' }}">
                            <img src="/vendor/mobile/images/tag.png" class="mr-3" alt="">
                            ТАРИФЫ
                        </a>
                    </li>
                    <li>
                        <a href="#section02" class="link">
                            <img src="/vendor/mobile/images/link2.png" class="mr-3" alt="">
                            СТАТИСТИКА
                        </a>
                    </li>
                    <li><a href="{{ route('payment') }}" class="link {{ request()->routeIs('payment') ? 'active' : '' }}">
                            <img src="/vendor/mobile/images/link3.png" class="mr-3" alt="">
                            ФИНАНСОВАЯ СТАТИСТИКА
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('services') }}" class="link {{ request()->routeIs('services') ? 'active' : '' }}">
                            <img src="/vendor/mobile/images/link4.png" class="mr-3" alt="">
                            ДОП. УСЛУГИ
                        </a>
                    </li>
                    <li><a href="#section04" class="link"></a></li>
                </ul> <!-- main-ul -->


                <p class="menu_p">
                    Есть вопросы? Позвоните: <br>
                    <a href="tel: 1130">1130</a>
                    или
                    <a href="tel: 712070806">+998 71 207-08-06</a>
                </p>
            </div> <!-- mycontainer -->

        </div> <!-- #menu -->

    </div> <!-- container -->
    <p class="menu_bottom_p">Личный кабинет Sola©2019. Все права защищены. <br>
        Разработка и дизайн Usoft</p>
</nav> <!-- touch-side-swipe responsive-nav -->