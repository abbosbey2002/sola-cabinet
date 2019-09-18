@extends('desktop.layouts.app', [ 'info' => $info, 'date' => true, 'months' => $months, 'tariff' => true ])
@section('title', trans('app.payment.title').' - ')
@section('content')
    <section class="dark_bg section section2">
        <div class="container">
            <h2 class="mb-4 text-center">@lang('app.payment.title')</h2>
            <h3 class="mb-3">@lang('app.payment.pays')</h3>
            <table class="table mytable table-sm">
                <thead>
                <tr>
                    <th scope="col">@lang('app.payment.date')</th>
                    <th scope="col">@lang('app.payment.payments')</th>
                    <th scope="col">@lang('app.payment.amount')</th>
                    <th scope="col">@lang('app.payment.status')</th>
                </tr>
                </thead>
                <tbody>
                @if(count($payments['body']['payments']) == 0)
                    <tr>
                        <td colspan="5">
                            @lang('app.no_data')
                        </td>
                    </tr>
                @endif
                @foreach($payments['body']['payments'] as $payment)
                    <tr>
                        <td>{{ date('H:i, d.m', strtotime($payment['payment_date'])) }}</td>
                        <td>{{ $payment['payment_system'] }}</td>
                        <td>{{ number_format($payment['amount'] / 100, 0, '', ' ') }} @lang('app.ye')</td>
                        <td>
                            {{ $payment['payment_status'] }}
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            <br>
        </div> <!-- mycontainer -->
    </section> <!-- dark_bg -->
@endsection

@push('js')
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
                    <form action="{{ route('payment.month') }}" method="post">
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
