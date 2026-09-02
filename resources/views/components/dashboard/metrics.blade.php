@props([
    'dash',
    'profile',
    'totalDevices',
    'deviceMetricHint' => null,
    'lastPayment' => null,
])

<div {{ $attributes->merge(['class' => 'grid gap-4 sm:grid-cols-2 '.($dash->metricColumnCount() === 4 ? 'lg:grid-cols-4' : 'lg:grid-cols-3')]) }}>
    @if ($dash->showTariff)
        <x-dash-metric-card
            icon="tag"
            :label="__('app.dash.current_tariff')"
            :value="$dash->hasTariff ? ($profile->currentTariffDisplayName() ?: __('app.header.no_tariff')) : __('app.dash.no_active_plan')"
            :hint="$dash->hasTariff && $dash->currentCost !== null ? $dash->formatMoney($dash->currentCost).' '.__('app.ye') : null"
            :href="$dash->canSwitchTariff ? route('tariff') : null"
            :clickable="$dash->canSwitchTariff"
            style="--i:1"/>
    @endif

    <x-dash-metric-card
        icon="router"
        :label="__('app.nav.devices')"
        :value="trans_choice('app.dash.devices_total', $totalDevices, ['count' => $totalDevices])"
        :hint="$deviceMetricHint"
        :href="route('devices')"
        style="--i:2"/>

    <x-dash-metric-card
        icon="receipt"
        :label="__('app.dash.last_payment')"
        :value="$lastPayment !== null ? $dash->formatMoney((float) $lastPayment['amount'] / 100).' '.__('app.ye') : null"
        :hint="$lastPayment !== null
            ? \Carbon\Carbon::parse($lastPayment['payment_date'])->format('d.m.Y').' · '.$lastPayment['payment_system']
            : __('app.dash.empty_payment_year')"
        :href="route('payment')"
        style="--i:3"/>

    <x-dash-metric-card
        icon="gift"
        :label="__('app.services.entry_title')"
        :value="__('app.services.entry_text')"
        :href="route('services')"
        style="--i:4"/>
</div>
