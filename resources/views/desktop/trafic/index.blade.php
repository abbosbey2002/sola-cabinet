@extends('desktop.layouts.app', [ 'info' => $info, 'date' => true, 'months' => $months ])
@section('title', trans('app.traffic.title').' - ')
@section('content')
    <section class="container-fluid">
        <div class="row">
            <div class="col-lg-12 px-0">
                <section class="section position-relative red_bg section2">
                    <!-- <div class="mycontainer">
                                <h2 class="mb-4">Статистика трафика:</h2>
                            </div> -->
                    <div class="position-relative">
                        <canvas id="myChart" width="50" height="50"></canvas>

                        <img src="/vendor/desktop/images/myshadow.png" class="myshadow" alt="">

                        <div class="recieve_info">
                            <h3> {{ round($output) }} @lang('app.traffic.mb')</h3>
                            <div class="d-flex justify-content-center align-items-center">
                                <div class="chart_color redwhite_bg mr-2"></div> <!-- chart_color -->
                                <p>@lang('app.traffic.in')</p>
                            </div> <!-- d-flex -->
                        </div> <!-- recieve_info -->
                    </div> <!-- position-relative -->

                    <div class="row justify-content-center m-0">
                        <div class="col-8">
                            <div class="chart_info">
                                <h4>@lang('app.traffic.inout')</h4>
                                <div class="d-flex justify-content-center align-items-center">
                                    <div class="chart_color red_bg mr-2"></div> <!-- chart_color -->
                                    <p>@lang('app.traffic.out') {{ round($input) }} @lang('app.traffic.mb')</p>
                                </div> <!-- d-flex -->
                            </div> <!-- chart_info -->
                        </div> <!-- col-8 -->
                    </div> <!-- row -->

                </section> <!-- section -->
            </div>
{{--            <div class="col-lg-6 px-0">--}}
{{--                <section class="section position-relative purple_bg section2">--}}
{{--                    <div class="position-relative">--}}
{{--                        <canvas id="myChart2" width="50" height="50"></canvas>--}}
{{--                        <img src="/vendor/desktop/images/myshadow.png" class="myshadow2" alt="">--}}
{{--                        <div class="recieve_info">--}}
{{--                            <h3>50632 MB</h3>--}}
{{--                            <div class="d-flex justify-content-center align-items-center">--}}
{{--                                <div class="chart_color purplewhite_bg mr-2"></div> <!-- chart_color -->--}}
{{--                                <p>internet</p>--}}
{{--                            </div> <!-- d-flex -->--}}
{{--                        </div> <!-- recieve_info -->--}}
{{--                    </div> <!-- position-relative -->--}}

{{--                    <div class="row justify-content-center m-0">--}}
{{--                        <div class="col-8">--}}
{{--                            <div class="chart_info">--}}
{{--                                <h4>подсети</h4>--}}
{{--                                <div class="d-flex justify-content-center align-items-center">--}}
{{--                                    <div class="chart_color purple_bg mr-2"></div> <!-- chart_color -->--}}
{{--                                    <p>TAS-IX 4582 MB</p>--}}
{{--                                </div> <!-- d-flex -->--}}
{{--                            </div> <!-- chart_info -->--}}
{{--                        </div> <!-- col-8 -->--}}
{{--                    </div> <!-- row -->--}}

{{--                </section> <!-- section -->--}}
{{--            </div>--}}
        </div>
    </section>


    <section class="dark_bg section section2">

        <div class="container">

            <h3 class="text-center mb-4">@lang('app.traffic.all_traffic')</h3>

        </div> <!-- mycontainer -->
        <div class="container">
            <table id="example" class="table mytable table-sm">
                <thead>
                <tr>
                    <th scope="col">@lang('app.traffic.date')</th>
                    <th scope="col">@lang('app.traffic.inn')</th>
                    <th scope="col">@lang('app.traffic.outt')</th>
                </tr>
                </thead>
                <tbody>
                @foreach($detail['body']['detail'] as $row)
                    <tr>
                        <td>{{ date('H:i, d.m', strtotime($row['event_time'])) }}</td>
                        <td>{{ number_format($row['traffic_input'] /1024 / 1024 , 2, ',', ' ')  }}</td>
                        <td>{{ number_format($row['traffic_output'] /1024 / 1024 , 2, ',', ' ')  }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </section> <!-- dark_bg -->

@endsection

@push('js')
    <script>
        var ctx = document.getElementById('myChart');
        var myChart = new Chart(ctx, {
            type: 'doughnut',
            data: {

                datasets: [{
                    label: '',
                    data: [{{ round($input) }}, {{ round($output) }}],
                    backgroundColor: [
                        '#00000000',
                        '#f6aeae'
                    ],
                    hoverBackgroundColor: [
                        '#00000000',
                        '#f6aeae'
                    ],

                    borderColor: "#00000000"
                }],

            },
            options: {
                legend: {
                    labels: {
                        fontColor: '#fff'
                    }
                },
                tooltips: {
                    enabled: false
                }
            }
        });

    </script>
    <script type="text/javascript" src="/vendor/datatables/datatables.min.js"></script>

    <script>
        $(document).ready(function() {
            $('#example').DataTable({searching: false, iDisplayLength: 20, info: false, bLengthChange: false});
        } );
    </script>

    <!-- Statics Tarif Modal -->
    <div class="modal fade" id="statics-modal">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">

                <!-- Modal Header -->
                <div class="modal-header">
                    <h4 class="modal-title">@lang('app.modal.choose_date')</h4>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>

                <!-- Modal body -->
                <div class="modal-body">
                    <form action="{{ route('traffic.month') }}" method="post">
                        @csrf
                        <select name="month" id="month" required>
                            @for ($i = 1; $i <= Carbon::now()->format('n'); $i++)
                                <option @if(Carbon::now()->format('Y-m') == $months[$i]['month']) selected @endif value="{{ $months[$i]['month'] }}">
                                    <span>{{ $months[$i]['name'] }}</span>
                                </option>
                            @endfor
                        </select>

                        <button type="submit" class="mybtn mt-4">@lang('app.modal.choose')</button>
                    </form>
                </div>

            </div>
        </div>
    </div>
@endpush


@push('css')
    <link rel="stylesheet" type="text/css" href="/vendor/datatables/datatables.min.css"/>
@endpush