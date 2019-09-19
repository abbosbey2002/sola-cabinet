<!DOCTYPE html>
<html lang="">
<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">

    <title>@yield('title'){{ env('APP_NAME') }}</title>

    <link rel="stylesheet" href="/vendor/mobile/css/style.css">
    <link rel="stylesheet" href="/vendor/mobile/css/media.css">

    <link rel="stylesheet" href="/vendor/mobile/fonts/museosanscyrl/museosanscyrl.css">
    <link rel="stylesheet" href="/vendor/mobile/libs/swiper-slider/swiper.min.css">
    <link rel="stylesheet" href="/vendor/mobile/libs/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="/vendor/mobile/libs/touch-sideswipe-master/touch-sideswipe.min.css">
    <link rel="shortcut icon" href="/vendor/mobile/images/bird.png" type="image/x-icon">
    <link rel='stylesheet prefetch' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.css'>

    @if($errors->any())
        <link rel="stylesheet" href="/vendor/toastr/build/toastr.min.css">
    @endif

    @stack('css')

</head>
<body>

@yield('content')


<script src="/vendor/mobile/js/jquery.js"></script>
<script src="/vendor/mobile/libs/bootstrap/js/bootstrap.min.js"></script>
<script src="/vendor/mobile/libs/bootstrap/js/popper.min.js"></script>
<script src="/vendor/mobile/js/smoothscroll.min.js"></script>
<script src="/vendor/mobile/js/chart.min.js"></script>
<script src="/vendor/mobile/libs/swiper-slider/swiper.min.js"></script>
<script src="/vendor/mobile/libs/touch-sideswipe-master/touch-sideswipe.min.js"></script>

<script src="/vendor/mobile/js/main.js"></script>

@if($errors->any())
    <script src="/vendor/toastr/build/toastr.min.js"></script>
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