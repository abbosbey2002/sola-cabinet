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