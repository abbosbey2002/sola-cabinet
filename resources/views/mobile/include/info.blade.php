<section class="section1">
    <div class="mycontainer">
        <h4>Ваш лицевой счет: {{ $info['body']['acc_id'] }} </h4>
        <div class="myborder_bottom info_block">
            <img src="/vendor/mobile/images/purse.png" alt="">
            <div class="d-flex flex-column">
                <h3>На вашем счету:</h3>
                <span class="green_span">{{ number_format($info['body']['saldo'], 0, '', ' ') }} UZS</span>
            </div> <!-- d-flex -->
        </div> <!-- info_block -->

        <div class="myborder_bottom info_block">
            <img src="/vendor/mobile/images/tairf.png" alt="">
            <div class="d-flex flex-column">
                <h3>Ваш тариф:</h3>
                <span class="green_span">
                        @if(!empty($info['body']['curr_tariff_name']))
                        {{ $info['body']['curr_tariff_name'] }}
                    @else
                        Нет тарифа
                    @endif
                    </span>
            </div> <!-- d-flex -->
        </div> <!-- info_block -->
        @if($tariff)
            <a href="{{ route('tariffs') }}" class="mybtn mt-4">СМЕНИТЬ ТАРИФ</a>
        @endif
    </div> <!-- mycontainer -->
</section> <!-- main-section -->