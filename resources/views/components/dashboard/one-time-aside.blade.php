@props([
    'dash',
    'totalDevices',
    'deviceMetricHint' => null,
    'lastPayment' => null,
])

<aside {{ $attributes->merge(['class' => 'u-card u-rise flex flex-col gap-4 p-5 sm:p-6', 'style' => '--i:1']) }}
    aria-labelledby="dash-one-time-aside-title">
    <h2 id="dash-one-time-aside-title" class="u-label">@lang('app.dash.account_state')</h2>

    <a href="{{ route('devices') }}"
        class="flex items-start gap-3.5 rounded-xl border-2 px-4 py-3.5 no-underline transition-[border-color,transform] hover:-translate-y-0.5 hover:border-action"
        style="border-color: var(--c-line)">
        <span class="grid size-10 shrink-0 place-items-center rounded-lg" style="background: var(--c-action-soft); color: var(--c-action)">
            <x-icon name="router" size="size-5"/>
        </span>
        <span class="min-w-0 flex-1">
            <span class="block text-sm font-semibold text-muted">@lang('app.nav.devices')</span>
            <span class="mt-0.5 block text-lg font-semibold text-ink">
                {{ trans_choice('app.dash.devices_total', $totalDevices, ['count' => $totalDevices]) }}
            </span>
            @if (filled($deviceMetricHint))
                <span class="mt-1 block text-sm text-muted">{{ $deviceMetricHint }}</span>
            @endif
        </span>
        <x-icon name="chevron-right" class="mt-2 shrink-0 text-muted"/>
    </a>

    <a href="{{ route('payment') }}"
        class="flex items-start gap-3.5 rounded-xl border-2 px-4 py-3.5 no-underline transition-[border-color,transform] hover:-translate-y-0.5 hover:border-action"
        style="border-color: var(--c-line)">
        <span class="grid size-10 shrink-0 place-items-center rounded-lg" style="background: var(--c-action-soft); color: var(--c-action)">
            <x-icon name="receipt" size="size-5"/>
        </span>
        <span class="min-w-0 flex-1">
            <span class="block text-sm font-semibold text-muted">@lang('app.dash.last_payment')</span>
            @if ($lastPayment !== null)
                <span class="mt-0.5 block text-lg font-semibold text-ink">
                    {{ $dash->formatMoney((float) $lastPayment['amount'] / 100) }} @lang('app.ye')
                </span>
                <span class="mt-1 block text-sm text-muted">
                    {{ \Carbon\Carbon::parse($lastPayment['payment_date'])->format('d.m.Y') }}
                    · {{ $lastPayment['payment_system'] }}
                </span>
            @else
                <span class="mt-0.5 block text-sm font-semibold text-ink">@lang('app.dash.empty_payment_year')</span>
            @endif
        </span>
        <x-icon name="chevron-right" class="mt-2 shrink-0 text-muted"/>
    </a>

    <a href="{{ route('services') }}"
        class="flex items-start gap-3.5 rounded-xl border-2 px-4 py-3.5 no-underline transition-[border-color,transform] hover:-translate-y-0.5 hover:border-action"
        style="border-color: var(--c-line)">
        <span class="grid size-10 shrink-0 place-items-center rounded-lg" style="background: var(--c-action-soft); color: var(--c-action)">
            <x-icon name="gift" size="size-5"/>
        </span>
        <span class="min-w-0 flex-1">
            <span class="block text-sm font-semibold text-muted">@lang('app.services.entry_title')</span>
            <span class="mt-0.5 block text-lg font-semibold text-ink">@lang('app.services.entry_text')</span>
        </span>
        <x-icon name="chevron-right" class="mt-2 shrink-0 text-muted"/>
    </a>
</aside>
