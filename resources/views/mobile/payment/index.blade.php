@extends('mobile.layouts.app')
@section('content')
    <div class="top-div">
        <a href="#">
            <img src="/vendor/mobile/images/logo.png" class="main-logo responsive-logo img-width logo-img" alt="logo">
        </a>
        <a href="{{ route('logout') }}" class="exit_link">
            <img src="/vendor/mobile/images/exit.png" class="img-width" alt="">
        </a>
    </div>

    <!-- MOBILE MENU -->

    @include('mobile.include.menu')


    <div class="nav_noner">

    </div>


    @include('mobile.include.info', ['info' => $info, 'tariff' => true, 'date' => true])

    <section class="dark_bg section section2">

        <div class="mycontainer">

            <h2 class="mb-4">Финансовая статистика</h2>
            <h3 class="mb-3">Оплаты</h3>

            <table class="table mytable table-sm">
                <thead>
                <tr>
                    <th scope="col">ID</th>
                    <th scope="col">Дата</th>
                    <th scope="col">Платежная система</th>
                    <th scope="col">Сумма</th>
                    <th scope="col">Статус</th>

                </tr>
                </thead>
                <tbody>
                @foreach($payments['body']['payments'] as $payment)
                    <tr>
                        <td>{{ $payment['payment_id'] }}</td>
                        <td>{{ date('H:i, d.m', strtotime($payment['payment_date'])) }}</td>
                        <td>{{ $payment['payment_system'] }}</td>
                        <td>{{ number_format($payment['amount'], 0, '', ' ') }} UZS</td>
                        <td>
                            @switch($payment['payment_status'])
                                @case(0)
                                    Не оплачено
                                    @break
                                @case(1)
                                    Оплчено
                                    @break
                                @case(2)
                                    Отказано
                                    @break
                                @default
                                    Не оплачено
                                    @break
                            @endswitch
                        </td>
                    </tr>
                @endforeach

                </tbody>
            </table>

            <br>




{{--            <h3 class="mb-3">Снятия</h3>--}}

{{--            <table class="table mytable table-sm">--}}
{{--                <thead>--}}
{{--                <tr>--}}
{{--                    <th scope="col">Дата</th>--}}
{{--                    <th scope="col">Описание</th>--}}
{{--                    <th scope="col">Сумма</th>--}}

{{--                </tr>--}}
{{--                </thead>--}}
{{--                <tbody>--}}
{{--                <tr>--}}
{{--                    <td>2019-08-09 17:32:45</td>--}}
{{--                    <td>Month fee 155806</td>--}}
{{--                    <td>16 000 000 UZS</td>--}}

{{--                </tr>--}}

{{--                </tbody>--}}
{{--            </table>--}}

        </div> <!-- mycontainer -->



    </section> <!-- dark_bg -->

    @include('mobile.include.footer')
@endsection

@push('js')
    <!-- Statics Tarif Modal -->
    <div class="modal fade" id="statics-modal">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">

                <!-- Modal Header -->
                <div class="modal-header">
                    <h4 class="modal-title">Выберите месяц</h4>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>

                <!-- Modal body -->
                <div class="modal-body">
                    <form action="form.php" method="post">
                        <select name="month" id="month" required>
                            <option value="val1"><span>Январь</span></option>
                            <option value="val2">Февраль</option>
                            <option value="val3">Март</option>
                            <option value="val4">Апрель</option>
                            <option value="val5">Май</option>
                            <option value="val6">Июнь</option>
                            <option value="val7">Июль</option>
                            <option value="val8">Август</option>
                            <option value="val9">Сентябрь</option>
                            <option value="val10">Октябрь</option>
                            <option value="val11">Ноябрь</option>
                            <option value="val12">Декабрь</option>
                        </select>

                        <button type="submit" class="mybtn mt-4">выбрать</button>
                    </form>
                </div>

            </div>
        </div>
    </div>
@endpush
