{{-- One-off subscriber (abonType 1): balance + quick overview, no tariff. --}}
<div class="grid items-start gap-4 lg:grid-cols-5 lg:gap-5">
    <div class="lg:col-span-3">
        <x-dashboard.balance-card :dash="$dash" :profile="$profile"/>
    </div>

    <x-dashboard.one-time-aside
        class="lg:col-span-2"
        :dash="$dash"
        :last-payment="$lastPayment"/>
</div>

<x-dashboard.metrics
    class="mt-4"
    :dash="$dash"
    :profile="$profile"
    :total-devices="$totalDevices"
    :device-metric-hint="$deviceMetricHint"
    :last-payment="$lastPayment"/>
