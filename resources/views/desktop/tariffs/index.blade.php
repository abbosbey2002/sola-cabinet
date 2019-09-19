@extends('desktop.layouts.app', [ 'info' => $info, 'tariff' => false ])
@section('title', trans('app.tariff.title').' - ')
@section('content')
    <section class="dark_bg section section2" style="padding-bottom: 15px">
        <div class="container">
            <div class="row">
                @foreach($tariffs['body']['tariffs'] as $tariff)
                    <div class="col-lg-3 mb-4">
                        <div class="rate_card">
                            <div class="rate_cart_title">
                                <h3 class="mb-0">{{ $tariff['tariff_name'] }}</h3>
                            </div> <!-- rate_cart_title -->
                            <div class="rate_card_info">
                                <div class="d-flex align-items-center py-2 myborder_bottom">
                                    <img src="/vendor/desktop/images/rate_img1.png" alt="">
                                    <div class="d-flex flex-column">
                                        <span>@lang('app.tariff.speed')</span>
                                        <span class="fw-bold">
                                            @if($tariff['spdu'] == 'Mbps')
                                                @lang('app.tariff.mb', ['speed' => $tariff['tspd']])
                                            @else
                                                @lang('app.tariff.kb', ['speed' => $tariff['tspd']])
                                            @endif
                                        </span>
                                    </div> <!-- d-flex -->
                                </div> <!-- d-flex -->
                                <div class="d-flex align-items-center py-2 myborder_bottom">
                                    <img src="/vendor/desktop/images/rate_img2.png" alt="">
                                    <div class="d-flex flex-column">
                                        <span>@lang('app.tariff.validity') </span>
                                        <span class="fw-bold">
                                            @if($tariff['prdu'] == 'HOUR')
                                                {{ $tariff['tprd'] }} @lang('app.tariff.hour')
                                            @elseif($tariff['prdu'] == 'MIN')
                                                {{ $tariff['tprd'] }} @lang('app.tariff.minut')
                                            @else
                                                {{ $tariff['tprd'] }} @lang('app.tariff.day')
                                            @endif
                                        </span>
                                    </div> <!-- d-flex -->
                                </div> <!-- d-flex -->
                                <div class="d-flex align-items-center py-2 myborder_bottom">
                                    <img src="/vendor/desktop/images/rate_img3.png" alt="">
                                    <div class="d-flex flex-column">
                                        <span>@lang('app.tariff.trafic')</span>
                                        <span class="fw-bold">
                                            @if($tariff['vol'] == 0)
                                                @lang('app.tariff.no_limit')
                                            @endif
                                        </span>
                                    </div> <!-- d-flex -->
                                </div> <!-- d-flex -->
                                <div class="d-flex align-items-center py-2 myborder_bottom">
                                    <img src="/vendor/desktop/images/rate_img4.png" alt="">
                                    <div class="d-flex flex-column">
                                        <span>@lang('app.tariff.amount') </span>
                                        <span class="fw-bold">
                                            {{ number_format($tariff['cost'] / 100, 0, '', ' ') }} @lang('app.ye')</span>

                                    </div> <!-- d-flex -->
                                </div> <!-- d-flex -->
                                @if(empty($info['body']['curr_tariff_name']) || SetCookie::getTypee() == 2)
                                    <a href="#" data-id="{{ $tariff['tariff_id'] }}" class="mybtn py-2 mt-3 green_shadow connect_btn">
                                        @lang('app.tariff.connect')
                                    </a>
                                @endif
                            </div> <!-- rate_card_info -->
                        </div> <!-- rate_card -->
                    </div>
                @endforeach
            </div>
        </div>
    </section> <!-- dark_bg -->
@endsection

@push('js')
    @if(SetCookie::getTypee() == 2)
        <!-- Change Tarif Modal -->
        <div class="modal fade" id="change-modal">
            <div class="modal-dialog modal-dialog-centered modal-sm">
                <div class="modal-content">

                    <!-- Modal Header -->
                    <div class="modal-header">
                        <h4 class="modal-title">@lang('app.modal.to_connect')</h4>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>

                    <!-- Modal body -->
                    <div class="modal-body d-flex justify-content-center align-items-center flex-column">

                        <button type="button" class="mybtn type_tariff" data-type="now">
                            @lсеang('app.modal.now')
                        </button>

                        <button type="button" class="mybtn mt-4 type_tariff" data-type="month">
                            @lang('app.modal.month')
                        </button>

                    </div>

                </div>
            </div>
        </div>
    @endif

    <!-- Change Tarif Modal -->
    <div class="modal fade" id="accept-modal">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">

                <!-- Modal Header -->
                <div class="modal-header d-flex flex-column justify-content-center align-items-center">
                    <img src="/vendor/mobile/images/exclamation_mark.png" alt="Question icon" style="width: 60px; height: 60px;">
                    <h4 class="modal-title mt-4 text-center">@lang('app.modal.are_you_sure')</h4>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>

                <!-- Modal body -->
                <div class="modal-body d-flex flex-row justify-content-center align-items-center">
                    <button style="width: 100px" type="button" class="mybtn bg-danger mx-3" data-dismiss="modal">
                        @lang('app.no')
                    </button>
                    <button style="width: 100px" type="button" class="mybtn mx-3 yes">
                        @lang('app.yes')
                    </button>
                </div>

            </div>
        </div>
    </div>

    @if(session()->has('info'))
        <script>
            $(document).ready(function () {
                $('#sucess-modal').modal('show')
            });
        </script>
        <!-- Success Modal -->
        <div class="modal fade" id="sucess-modal">
            <div class="modal-dialog modal-dialog-centered modal-sm">
                <div class="modal-content">

                    <!-- Modal Header -->
                    <div class="modal-header d-flex justify-content-center flex-column align-items-center">
                        <img src="/vendor/mobile/images/check-mark.png" alt="Check icon">
                        <h4 class="modal-title mt-4">@lang('app.modal.success_tariff')</h4>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>

                    <!-- Modal body -->
                    <div class="modal-body">

                    </div>

                </div>
            </div>
        </div>
    @endif

    <script>
        var id = 0;
        @if(SetCookie::getTypee() == 2)
            var type = 'not';
        @else
           var type = 'now';
        @endif


        $('.connect_btn').on('click', function (e) {
            e.preventDefault();
            id = $(this).data('id');
            $('#accept-modal').modal('show');
            //$('#change-modal').modal('show');
        });

        $('.yes').on('click', function (e) {
            e.preventDefault();
            $('#accept-modal').modal('hide');

            @if(SetCookie::getTypee() == 2)
                $('#change-modal').modal('show');
            @else
                sendConfig();
            @endif
        });

        $('.type_tariff').on('click', function (e) {
            e.preventDefault();
            type = $(this).data('type');
            sendConfig();
        });

        function sendConfig()
        {
            window.location.replace("/tariffs/connect/" + id + "/" + type);
        }
    </script>
@endpush