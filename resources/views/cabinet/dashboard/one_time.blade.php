{{-- One-off subscriber (abonType 1): balance + jump list, no tariff, no
     next-charge. Metrics stay off this layout — the aside already carries
     devices, last payment and help, and repeating them as cards was the
     duplicate row on the one-time dashboard. --}}
<div class="grid items-start gap-4 lg:grid-cols-5 lg:gap-5">
    <div class="lg:col-span-3">
        <x-dashboard.balance-card :dash="$dash" :profile="$profile"/>
    </div>

    <x-dashboard.one-time-aside
        class="lg:col-span-2"
        :dash="$dash"
        :total-devices="$totalDevices"
        :device-metric-hint="$deviceMetricHint"
        :last-payment="$lastPayment"/>
</div>
