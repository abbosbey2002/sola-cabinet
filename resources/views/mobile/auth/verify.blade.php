<!DOCTYPE html>
<html lang="">
<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">

    <title>{{ env('APP_NAME') }}</title>

    <link rel="stylesheet" href="/vendor/mobile/css//style.css">
    <link rel="stylesheet" href="/vendor/mobile/css//media.css">
    <link rel="stylesheet" href="/vendor/mobile/fonts//museosanscyrl/museosanscyrl.css">
    <link rel="stylesheet" href="/vendor/mobile/libs//swiper-slider/swiper.min.css">
    <link rel="stylesheet" href="/vendor/mobile/libs//bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="/vendor/mobile/libs//touch-sideswipe-master/touch-sideswipe.min.css">
    <link rel="shortcut icon" href="/vendor/mobile/images/bird.png" type="image/x-icon">
    <link rel='stylesheet prefetch' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.css'>
    <link rel="stylesheet" href="/vendor/toastr/build/toastr.min.css">

</head>
<body>

<header>

    <div class="container h-100">
        <form action="{{ route('verify') }}" method="post" class="begin_form">
            @csrf
            <img src="/vendor/mobile/images/bird.png" alt="">

            <p>
                @lang('app.auth.send_phone', ['phone' => substr(session()->get('phone'), 7,12)])
            </p>

            <input type="tel" minlength="4" maxlength="4" placeholder="@lang('app.auth.code')" name="code" class="text-center"  required="">

            <button type="submit">@lang('app.auth.login')</button>

        </form> <!-- begin_form -->
    </div> <!-- container -->

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
<script src="/vendor/mobile/libs//bootstrap/js/bootstrap.min.js"></script>
<script src="/vendor/mobile/libs//bootstrap/js/popper.min.js"></script>
<script src="/vendor/mobile/js/smoothscroll.min.js"></script>
<script src="/vendor/mobile/js/chart.min.js"></script>
<script src="/vendor/mobile/libs//swiper-slider/swiper.min.js"></script>
<script src="/vendor/mobile/libs//touch-sideswipe-master/touch-sideswipe.min.js"></script>

<script src="/vendor/mobile/js/main.js"></script>
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
</body>
</html>