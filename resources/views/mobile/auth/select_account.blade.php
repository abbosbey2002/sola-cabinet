<!DOCTYPE html>
<html lang="">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">

    <title>@lang('app.accounts.choose') - Sola</title>

    <link rel="stylesheet" href="/vendor/mobile/css/style.css">
    <link rel="stylesheet" href="/vendor/mobile/css/media.css">
    <link rel="stylesheet" href="/vendor/mobile/fonts/museosanscyrl/museosanscyrl.css">
    <link rel="stylesheet" href="/vendor/mobile/libs/swiper-slider/swiper.min.css">
    <link rel="stylesheet" href="/vendor/mobile/libs/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="/vendor/mobile/libs/touch-sideswipe-master/touch-sideswipe.min.css">
    <link rel="shortcut icon" href="/vendor/mobile/images/favicon.png" type="image/x-icon">

    <style>
    </style>
</head>

<body>
<header>
    <div class="container h-100">
        <form action="" class="begin_form" style="padding: 0 5px">

            <img src="/vendor/mobile/images/bird.png" alt="">

            <div class="user__title">@lang('app.accounts.choose')</div>
            <div class="user">
                @foreach($response['body']['accs'] as $account)
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
                @endforeach
            </div>
        </form> <!-- begin_form -->
    </div> <!-- container -->

    <!-- <a href="#" class="lang_link">Узбекский язык</a> -->
    @if(App::isLocale('uz'))
        <div class="lang lang-auth">
            <div class="lang-content">
                <div class="lang-dropdown">
                    <a href="{{ route('change.lang', ['ru']) }}">RU</a>
                    <a href="{{ route('change.lang', ['en']) }}">EN</a>
                </div>
            </div>
        </div>
    @elseif(App::isLocale('ru'))
        <div class="lang lang-auth">
            <div class="lang-content">
                <div class="lang-dropdown">
                    <a href="{{ route('change.lang', ['uz']) }}">UZ</a>
                    <a href="{{ route('change.lang', ['en']) }}">EN</a>
                </div>
            </div>
        </div>
    @else
        <div class="lang lang-auth">
            <div class="lang-content">
                <div class="lang-dropdown">
                    <a href="{{ route('change.lang', ['ru']) }}">RU</a>
                    <a href="{{ route('change.lang', ['uz']) }}">UZ</a>
                </div>
            </div>
        </div>
    @endif
</header> <!-- header -->




<section>

</section> <!-- main-section -->


<footer>

</footer> <!-- footer -->




<script src="/vendor/mobile/js/jquery.js"></script>
<script src="/vendor/mobile/libs/bootstrap/js/bootstrap.min.js"></script>
<script src="/vendor/mobile/libs/bootstrap/js/popper.min.js"></script>
<script src="/vendor/mobile/js/smoothscroll.min.js"></script>
<script src="/vendor/mobile/js/chart.min.js"></script>
<script src="/vendor/mobile/libs/swiper-slider/swiper.min.js"></script>
<script src="/vendor/mobile/libs/touch-sideswipe-master/touch-sideswipe.min.js"></script>

<script src="/vendor/mobile/js/main.js"></script>
</body>

</html>