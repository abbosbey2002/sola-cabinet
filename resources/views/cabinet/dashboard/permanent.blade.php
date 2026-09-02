{{-- Permanent individual: full balance + tariff hero and clickable tariff metric. --}}
<div class="grid gap-4 lg:grid-cols-2 lg:gap-5">
    <x-dashboard.balance-card :dash="$dash" :profile="$profile"/>

    <x-dashboard.tariff-card :dash="$dash" :profile="$profile" :cycle="$cycle"/>
</div>

<x-dashboard.metrics
    class="mt-4"
    :dash="$dash"
    :profile="$profile"
    :total-devices="$totalDevices"
    :device-metric-hint="$deviceMetricHint"
    :last-payment="$lastPayment"/>
