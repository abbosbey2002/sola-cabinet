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

    <nav id="touchSideSwipe" class="touch-side-swipe responsive-nav">

        <div class="container p-0">
            <div id="menu">
                <div class="logo-div text-center">




                </div>
                <a href="#" class="main_color text-center pb-4">Узбекский язык</a>

                <div class="mycontainer2">
                    <ul class="main-ul">
                        <li><a href="#section01" class="link"><img src="/vendor/mobile/images/link1.png" class="mr-3" alt="">ДАННЫЕ</a></li>
                        <li><a href="#section02" class="link"><img src="/vendor/mobile/images/link2.png" class="mr-3" alt="">СТАТИСТИКА</a></li>
                        <li><a href="#section04" class="link"><img src="/vendor/mobile/images/link3.png" class="mr-3" alt="">ФИНАНСОВАЯ СТАТИСТИКА</a></li>
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


    <div class="nav_noner">

    </div>


    @include('mobile.include.info', ['info' => $info, 'tariff' => false])



    <section class="dark_bg section section2">

        <!-- Swiper -->
        <div class="swiper-container">
            <div class="swiper-wrapper">
                @foreach($tariffs['body']['tariffs'] as $tariff)
                <div class="swiper-slide">
                    <div class="rate_card">
                        <div class="rate_cart_title">
                            <h3 class="mb-0">{{ $tariff['tariff_name'] }}</h3>
                        </div> <!-- rate_cart_title -->
                        <div class="rate_card_info">
                            <div class="d-flex align-items-center py-2 myborder_bottom">
                                <img src="/vendor/mobile/images/rate_img1.png" alt="">
                                <div class="d-flex flex-column">
                                    <span>Скорость</span>
                                    <span class="fw-bold">до 4 Мбит/сек</span>
                                </div> <!-- d-flex -->
                            </div> <!-- d-flex -->
                            <div class="d-flex align-items-center py-2 myborder_bottom">
                                <img src="/vendor/mobile/images/rate_img2.png" alt="">
                                <div class="d-flex flex-column">
                                    <span>Срок действия </span>
                                    <span class="fw-bold">1 месяц</span>
                                </div> <!-- d-flex -->
                            </div> <!-- d-flex -->
                            <div class="d-flex align-items-center py-2 myborder_bottom">
                                <img src="/vendor/mobile/images/rate_img3.png" alt="">
                                <div class="d-flex flex-column">
                                    <span>Трафик</span>
                                    <span class="fw-bold">Безлимитный</span>
                                </div> <!-- d-flex -->
                            </div> <!-- d-flex -->
                            <div class="d-flex align-items-center py-2 myborder_bottom">
                                <img src="/vendor/mobile/images/rate_img4.png" alt="">
                                <div class="d-flex flex-column">
                                    <span>Стоимость </span>
                                    <span class="fw-bold">120 000 UZS</span>
                                </div> <!-- d-flex -->
                            </div> <!-- d-flex -->
                            <a href="{{ route('tariff.connect', $tariff['tariff_id']) }}" class="mybtn py-2 mt-3 green_shadow">Подключить</a>
                        </div> <!-- rate_card_info -->
                    </div> <!-- rate_card -->
                </div> <!-- swiper-slide -->
                @endforeach
            </div>
        </div>

    </section> <!-- dark_bg -->




    @include('mobile.include.footer')
@endsection