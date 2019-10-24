<section class="section1">
    <div class="mycontainer">
        <h4>@lang('app.cabinet.account'): {{ request()->cookie('account') }} </h4>
        <h4>@lang('app.cabinet.status'): {{ $info['body']['status'] }} </h4>

        <div class="myborder_bottom info_block">
            <img src="/vendor/mobile/images/purse.png" alt="">
            <div class="d-flex flex-column">
                <h3>@lang('app.header.balance'):</h3>
                <span class="green_span">{{ number_format($info['body']['saldo'], 0, '', ' ') }} @lang('app.ye')</span>
            </div> <!-- d-flex -->
        </div> <!-- info_block -->

        <div class="myborder_bottom info_block">
            <img src="/vendor/mobile/images/tairf.png" alt="">
            <div class="d-flex flex-column">
                <h3>@lang('app.header.tariff'):</h3>
                <span class="green_span">
                    @if(!empty($info['body']['curr_tariff_name']))
                        {{ $info['body']['curr_tariff_name'] }}
                    @else
                        @lang('app.header.no_tariff')
                    @endif
                    </span>
            </div> <!-- d-flex -->
        </div> <!-- info_block -->
{{--        @if(SetCookie::getTypee() ==  2)--}}
{{--            @if($tariff)--}}
{{--                <a href="{{ route('tariffs') }}" class="mybtn mt-4">@lang('app.header.change_tariff')</a>--}}
{{--            @endif--}}
{{--        @endif--}}

        @if(!empty($date))
            <div class="statics-tarif">

                @if(!empty($month))
                    <p>
                        @lang('app.header.date_choose', ['Month' => $months[Carbon::parse($month)->format('n')]['name']])
                    </p>
                @else
                    <p>
                        @lang('app.header.date_choose', ['Month' => $months[Carbon::now()->format('n')]['name']])
                    </p>
                @endif

                <button type="button" class="btn" data-toggle="modal" data-target="#statics-modal">
                    @lang('app.header.choose')
                </button>
            </div>
        @endif
    </div> <!-- mycontainer -->
</section> <!-- main-section -->
