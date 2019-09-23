@extends('mobile.layouts.app')
@section('title', trans('app.services.title'). ' - ')
@section('content')
    @include('mobile.include.header')

    <!-- MOBILE MENU -->

    @include('mobile.include.menu')


    <div class="nav_noner">

    </div>


    @include('mobile.include.info', ['info' => $info, 'tariff' => true])

    <section class="dark_bg section section2">
        <div class="mycontainer">
            <h2 class="mb-4">@lang('app.services.devices')</h2>
            <table class="table mytable table-sm ">
                <thead>
                <tr>
                    <th scope="col">@lang('app.services.device')</th>
                    <th scope="col">@lang('app.services.status')</th>
                    <th scope="col">@lang('app.services.last_online')</th>
                </tr>
                </thead>
                <tbody>
                    @if(count($devices['body']['devices']) == 0)
                        <tr>
                            <td colspan="3">
                                @lang('app.no_data')
                            </td>
                        </tr>
                    @endif
                    @foreach($devices['body']['devices'] as $device)
                        <tr>
                            <td>{{ $device['mac'] }}</td>
                            <td>
                                @if($device['status'] == 0)
                                    <img class="mr-1" src="/vendor/mobile/images/red-icon.png" alt="On">Offline
                                @else
                                    <div>
                                        <img class="mr-1" src="/vendor/mobile/images/green-icon.png" alt="On">Online
                                    </div>
                                    <div>IP {{ $device['ip'] }}</div>
                                @endif

                            </td>
                            <td>22:10 14/06/2019 </td>

                        </tr>
                    @endforeach
                </tbody>
            </table>

{{--            @if($type == 2)--}}
{{--                <a href="{{ route('services.device.new') }}" class="mybtn mt-4 w-75 mx-auto">--}}
{{--                    @lang('app.services.add_devices')--}}
{{--                </a>--}}
{{--            @endif--}}
        </div> <!-- mycontainer -->
    </section> <!-- dark_bg -->

    @include('mobile.include.footer')
@endsection

@push('js')
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
                        <h4 class="modal-title mt-4 text-center">
                            @lang('app.services.add_success')
                        </h4>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>

                    <!-- Modal body -->
                    <div class="modal-body">

                    </div>

                </div>
            </div>
        </div>
    @endif
@endpush