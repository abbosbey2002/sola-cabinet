<div class="top-div">
    <div class="lang">
        <div class="lang-content">
            @if(App::isLocale('uz'))
                <a href="#uz" class="lang-dropbtn">UZ</a>
                <div class="lang-dropdown">
                    <a href="{{ route('change.lang', ['ru']) }}">RU</a>
                    <a href="{{ route('change.lang', ['en']) }}">En</a>
                </div>
            @elseif(App::isLocale('ru'))
                <a href="#ru" class="lang-dropbtn">RU</a>
                <div class="lang-dropdown">
                    <a href="{{ route('change.lang', ['uz']) }}">UZ</a>
                    <a href="{{ route('change.lang', ['en']) }}">En</a>
                </div>
            @else
                <a href="#en" class="lang-dropbtn">EN</a>
                <div class="lang-dropdown">
                    <a href="{{ route('change.lang', ['ru']) }}">RU</a>
                    <a href="{{ route('change.lang', ['uz']) }}">UZ</a>
                </div>
            @endif
        </div>
    </div>

    <a href="{{ route('cabinet') }}">
        <img src="/vendor/mobile/images/logo.png" class="main-logo responsive-logo img-width logo-img" alt="logo">
    </a>
    <a href="{{ route('logout') }}" class="exit_link">
        <img src="/vendor/mobile/images/exit.png" class="img-width" alt="">
    </a>
</div>
