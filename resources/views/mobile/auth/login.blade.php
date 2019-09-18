@extends('mobile.layouts.app')
@section('content')
    <header>

        <div class="container h-100">
            <form action="{{ route('login') }}" method="post" class="begin_form">
                @csrf
                <img src="/vendor/mobile/images/bird.png" alt="">


                <input type="tel" placeholder="@lang('app.auth.phone')" name="login" required="">
{{--                <input type="password" placeholder="Пароль" required="">--}}

                <button type="submit">@lang('app.auth.next')</button>


            </form> <!-- begin_form -->
        </div> <!-- container -->

        @if(App::isLocale('uz'))
            <a href="{{ route('change.lang', ['ru']) }}" class="lang_link">@lang('app.lang')</a>
        @elseif(App::isLocale('ru'))
            <a href="{{ route('change.lang', ['uz']) }}" class="lang_link">@lang('app.lang')</a>
        @endif
    </header>
@endsection


@push('css')
    <link rel="stylesheet" href="/vendor/toastr/build/toastr.min.css">
@endpush

@push('js')


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
@endpush