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

        @if(SetCookie::getTypee() ==  2)
            <a href="{{ route('devices.add') }}" onclick="return confirm('@lang('app.header.add_device_sure')')" class="mybtn mt-4">
                @lang('app.header.add_device')
            </a>
        @endif

        @if(!empty($devices) && SetCookie::getTypee() ==  2)
            <div class="col-lg-12">
                @if(session()->has('danger'))
                    <div class="alert alert-danger">
                        {{ session()->pull('danger') }}
                    </div>
                @endif

                @if(session()->has('info'))
                    <div class="alert alert-info">
                        {{ session()->pull('info') }}
                    </div>
                @endif

                <table class="table mytable table-sm">
                    <thead>
                    <tr>
                        <th scope="col">@lang('app.header.mac')</th>
                        <th scope="col">@lang('app.header.status')</th>
                        <th scope="col">@lang('app.header.date_connect')</th>
                        <th scope="col">@lang('app.actions')</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($devices as $device)
                        <tr>
                            <td>
                                @if($device['mac'])
                                    {{ $device['mac'] }}
                                @else
                                    @lang('app.header.no_mac')
                                @endif
                            </td>

                            <td>
                                @if ($device['ip'])
                                    @lang('app.header.online')
                                @else
                                    @lang('app.header.offline')
                                @endif
                            </td>

                            <td>
                                {{ Carbon::parse($device['connect_date'])->format('d.m.Y') }}
                            </td>

                            <td>
                                @if(!$device['readonly'])
                                    <a href="{{ route('devices.delete', $device['permit_id']) }}" onclick="return confirm(@lang('app.header.are_you_cancel'))" class="btn btn-danger btn-sm">
                                        @lang('app.detele')
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif

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
