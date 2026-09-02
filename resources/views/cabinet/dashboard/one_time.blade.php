{{-- One-off subscriber (abonType 1): balance, then the same metric cards as
     everyone else. No tariff column and no extra aside — that block duplicated
     last payment / devices. --}}
<x-dashboard.balance-card :dash="$dash" :profile="$profile"/>

<x-dashboard.metrics
    class="mt-4"
    :dash="$dash"
    :profile="$profile"
    :total-devices="$totalDevices"
    :device-metric-hint="$deviceMetricHint"
    :last-payment="$lastPayment"/>
