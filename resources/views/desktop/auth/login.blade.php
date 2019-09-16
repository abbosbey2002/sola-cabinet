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
            <form action="{{ route('login') }}" class="begin_form" method="post">
                @csrf
                <input type="text" placeholder="@lang('app.auth.phone')" name="login" required="">
                <button type="submit">@lang('app.auth.next')</button>
            </form> <!-- begin_form -->

        </div>
    </div> <!-- container -->

    @if(App::isLocale('uz'))
        <a href="{{ route('change.lang', ['ru']) }}" class="lang_link">@lang('app.lang')</a>
    @elseif(App::isLocale('ru'))
        <a href="{{ route('change.lang', ['ru']) }}" class="lang_link">@lang('app.lang')</a>
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