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
{{--    <a href="{{ route('logout') }}" class="exit_link">--}}
{{--        <img src="/vendor/mobile/images/exit.png" class="img-width" alt="">--}}
{{--    </a>--}}

    <div class="drop-user">

        <div class="drop">
            <div class="drop-btn">
                <i class="fa fa-user text-white fa-3x"></i>
            </div>
            <div class="drop-down">
                <div class="user">x
                    @foreach($accounts['body']['accs'] as $account)
                            <div class="user__item @if($account['accId'] == request()->cookie('account')) active @endif">
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
                <div class="sign-out">
                    <a href="{{ route('logout') }}" class="my-btn main_color px-0 mx-0">
                        @lang('app.menu.logout')
                    </a>
                </div>
            </div>
        </div>
    </div>

</div>
