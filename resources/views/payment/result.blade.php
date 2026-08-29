@php
    use App\Support\BillingHistory;

    // Spec §6. Statuses arrive as free text from billing, so the tone is
    // matched on the text and anything unrecognised keeps its own label.
    // Colour, icon and word move together — the pill never relies on hue alone.
    $tones = [
        'ok' => ['class' => 'u-pill-ok', 'icon' => 'check'],
        'pending' => ['class' => 'u-pill-warn', 'icon' => 'clock'],
        'error' => ['class' => 'u-pill-off', 'icon' => 'alert'],
        'cancelled' => ['class' => 'u-pill-neutral', 'icon' => 'minus'],
        'neutral' => ['class' => 'u-pill-neutral', 'icon' => 'minus'],
    ];

    // The receipt number is what a subscriber quotes when a payment has to be
    // traced, so it earns a column — but only when billing actually sends it.
    $hasId = collect($payments['rows'])->contains(fn (array $row): bool => filled($row['payment_id'] ?? null));

    // A true minus sign, not a hyphen. Billing sends corrections as negative
    // amounts, and in this column's tabular figures a hyphen reads as a dash
    // between two numbers rather than a sign.
    $money = fn (float $value): string => str_replace('-', '−', number_format($value, 0, '', ' '));
@endphp

<x-period-note :incomplete="$payments['incomplete']"/>

{{-- Labelled by its own visible caption: an sr-only "Total" heading above a
     line that already begins with "Total from … to …" is the same words twice. --}}
<section class="u-card u-rise" aria-labelledby="total-title">
    <div class="flex flex-wrap items-baseline gap-x-3 gap-y-2">
        <p id="total-title" class="u-label">
            @lang('app.payment.total_between', [
                'start' => $period->start->format('d.m'),
                'end' => $period->end->format('d.m'),
            ])
        </p>
        <p class="u-figure text-2xl text-ink">{{ $money($payments['total']) }}</p>
        <p class="text-base text-muted">@lang('app.ye')</p>
        <span class="u-pill-neutral ml-auto">
            {{ trans_choice('app.payment.count', count($payments['rows']), ['count' => count($payments['rows'])]) }}
        </span>
    </div>
</section>

@if ($payments['rows'])
    {{-- Nearly all table, so the card padding drops to a hairline: at the full
         card padding the rows lose a quarter of their width on a phone. --}}
    <section data-table data-page-size="10" class="u-card u-rise mt-4 !p-2 sm:!p-3" style="--i:1" aria-labelledby="history-title">
        <h2 id="history-title" class="sr-only">@lang('app.payment.pays')</h2>

        <x-table-toolbar search print/>

        <div class="u-table-wrap u-scroll">
            <table class="u-table u-table-cards">
                <thead>
                    <tr>
                        <th scope="col" data-sort="date">@lang('app.payment.date')</th>
                        @if ($hasId)
                            <th scope="col" data-sort="number">@lang('app.payment.id')</th>
                        @endif
                        <th scope="col" data-sort="text">@lang('app.payment.payments')</th>
                        <th scope="col" data-sort="number" class="text-right">@lang('app.payment.amount')</th>
                        <th scope="col" data-sort="text">@lang('app.payment.status')</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($payments['rows'] as $payment)
                        @php
                            // Billing sends corrections as negative amounts (see
                            // $money above) under the same free-text status as an
                            // ordinary payment — "оплачено" on a charge reads as
                            // billing's mistake, not the subscriber's. The sign
                            // overrides the text rather than the other way round.
                            $isCharge = ((float) ($payment['amount'] ?? 0)) < 0;
                            $tone = $isCharge ? $tones['neutral'] : $tones[BillingHistory::paymentTone($payment['payment_status'] ?? null)];
                            $label = match (true) {
                                $isCharge => __('app.payment.charge'),
                                filled($payment['payment_status'] ?? null) => $payment['payment_status'],
                                default => __('app.dash.unknown'),
                            };

                            // Appended, not swapped in: the payment is still
                            // whatever payment_status says, on credit is
                            // additional information about how it was paid.
                            if (BillingHistory::isCreditNote($payment['note'] ?? null)) {
                                $label .= ' · '.__('app.payment.credit');
                            }
                        @endphp

                        <tr>
                            <td data-label="{{ __('app.payment.date') }}" data-value="{{ $payment['payment_date'] }}">
                                {{ \Carbon\Carbon::parse($payment['payment_date'])->format('d.m.Y H:i') }}
                            </td>

                            @if ($hasId)
                                <td data-label="{{ __('app.payment.id') }}" data-value="{{ $payment['payment_id'] ?? '' }}" class="text-muted">
                                    {{ $payment['payment_id'] ?? '—' }}
                                </td>
                            @endif

                            <td data-label="{{ __('app.payment.payments') }}">{{ $payment['payment_system'] }}</td>

                            <td data-label="{{ __('app.payment.amount') }}" data-value="{{ $payment['amount'] }}" class="text-right font-semibold">
                                {{ $money($payment['amount'] / 100) }}
                            </td>

                            <td data-label="{{ __('app.payment.status') }}">
                                <span class="{{ $tone['class'] }}">
                                    <x-icon :name="$tone['icon']" size="size-4"/>{{ $label }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <x-table-nav :label="__('app.payment.pays')"/>
    </section>
@else
    <section class="u-card u-rise mt-4" style="--i:1">
        <x-empty icon="receipt" :title="__('app.empty.payments')" :hint="__('app.empty.payments_hint')"/>
    </section>
@endif
