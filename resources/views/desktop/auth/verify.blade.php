<!DOCTYPE html>
<html lang="">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">

    <title>{{ env('APP_NAME') }}</title>

    <link rel="stylesheet" href="/vendor/desktop/css/style.css">
    <link rel="stylesheet" href="/vendor/desktop/css/media.css">
    <link rel="stylesheet" href="/vendor/desktop/fonts/museosanscyrl/museosanscyrl.css">
    <link rel="stylesheet" href="/vendor/desktop/libs/bootstrap/css/bootstrap.min.css">
    <link rel="shortcut icon" href="/vendor/desktop/images/favicon.png" type="image/x-icon">
    <link rel='stylesheet prefetch'
          href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.css'>

    <link rel="stylesheet" href="/vendor/toastr/build/toastr.min.css">
</head>

<body>

<header>

    <div class="container-fluid h-100 px-0">
        <div class="main-logo">
            <div class="container">
                <img src="/vendor/desktop/images/main-logo.png" alt="Main Logo">
            </div>
        </div>
        <div class="container">
            <form action="{{ route('verify') }}" method="post" class="begin_form">
                @csrf
                <p>
                   @lang('app.auth.send_phone', ['phone' => substr(session()->get('phone'), 7,12)])
                </p>
                <input type="tel" maxlength="4" name="code" placeholder="@lang('app.auth.code')" class="text-center" required="">

                <button type="submit" style="margin-top: 3.5rem;">@lang('app.auth.login')</button>
            </form> <!-- begin_form -->

        </div>
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

</body>

</html>