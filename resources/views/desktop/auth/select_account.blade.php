<!DOCTYPE html>
<html lang="">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">

    <title>
        @lang('app.accounts.choose') - Sola
    </title>

    <link rel="stylesheet" href="/vendor/desktop/css/style.css">
    <link rel="stylesheet" href="/vendor/desktop/css/media.css">
    <link rel="stylesheet" href="/vendor/desktop/fonts/museosanscyrl/museosanscyrl.css">
    <link rel="stylesheet" href="/vendor/desktop/libs/bootstrap/css/bootstrap.min.css">
    <link rel="shortcut icon" href="/vendor/desktop/images/favicon.png" type="image/x-icon">
    <link rel='stylesheet prefetch'
          href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.css'>

</head>

<body>

<header>

    <div class="container-fluid h-100 px-0">
        <div class="main-logo">
            <div class="container">
                <img src="/vendor/desktop/images/main-logo.png" alt="Main Logo">
            </div>
        </div>
        <div class="container py-5">
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
        </div>
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





<script src="/vendor/desktop/js/jquery.js"></script>
<script src="/vendor/desktop/libs/bootstrap/js/bootstrap.min.js"></script>
<script src="/vendor/desktop/libs/bootstrap/js/popper.min.js"></script>
<script src="/vendor/desktop/js/smoothscroll.min.js"></script>
<script src="/vendor/desktop/js/chart.min.js"></script>
<script src="/vendor/desktop/libs/swiper-slider/swiper.min.js"></script>
<script src="/vendor/desktop/libs/touch-sideswipe-master/touch-sideswipe.min.js"></script>

<script src="/vendor/desktop/js/main.js"></script>
</body>

</html>