@props([
    'dash',
    'profile',
    'cycle' => null,
])

<section {{ $attributes->merge(['class' => 'u-card u-rise p-6 sm:p-8', 'style' => '--i:1']) }} aria-labelledby="dash-tariff-title">
    <h2 id="dash-tariff-title" class="u-label">@lang('app.dash.active_tariff')</h2>

    @if (! $dash->hasTariff)
        <div class="mt-4 flex items-start gap-3.5 rounded-xl border-2 px-4 py-4"
            style="border-color: var(--c-warn-soft); background: var(--c-warn-soft)">
            <span class="grid size-10 shrink-0 place-items-center rounded-lg bg-surface" style="color: var(--c-warn)">
                <x-icon name="alert" size="size-5"/>
            </span>
            <span class="min-w-0 flex-1">
                <span class="block text-lg font-semibold text-ink">@lang('app.dash.no_active_plan')</span>
                <span class="mt-1 block text-sm text-muted">@lang('app.dash.no_active_plan_hint')</span>
            </span>
        </div>

        @if ($dash->canSwitchTariff)
            <a href="{{ route('tariff') }}" class="u-btn-outline u-no-print mt-5 inline-flex w-full sm:w-auto">
                @lang('app.dash.choose_tariff')
            </a>
        @endif
    @else
        <p class="mt-3 text-xl font-semibold text-ink">{{ $profile->currentTariffDisplayName() }}</p>
        @if ($dash->currentCost !== null)
            <p class="mt-1 text-sm text-muted">{{ $dash->formatMoney($dash->currentCost) }} @lang('app.ye')</p>
        @endif

        @if ($dash->showCycle && $cycle !== null)
            <div class="mt-5 flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex flex-wrap items-center justify-between gap-x-6 gap-y-1 rounded-xl p-4 w-full sm:flex-1"
                    style="background: var(--c-bg)">
                    <p class="u-label">@lang('app.dash.next_charge')</p>
                    <p class="flex flex-wrap items-baseline gap-x-2">
                        @if ($dash->cost !== null)
                            <span class="u-figure text-base text-ink">{{ $dash->formatMoney($dash->cost) }}</span>
                            <span class="text-sm text-muted">@lang('app.ye') · {{ $cycle->end->format('d.m.Y') }}</span>
                        @else
                            <span class="u-figure text-base text-ink">{{ $cycle->end->format('d.m.Y') }}</span>
                        @endif
                    </p>
                </div>

                <div class="flex shrink-0 justify-center" role="img"
                    aria-label="{{ $cycle->isChargeDay() ? __('app.dash.charge_today') : ($cycle->isOverdue() ? __('app.dash.charge_passed') : trans_choice('app.dash.days_left', $cycle->daysLeft, ['days' => $cycle->daysLeft])) }}">
                    <x-arc class="w-36 sm:w-40" :segments="[['fraction' => $dash->ringFraction, 'color' => $dash->ringColor]]" aria-hidden="true">
                        @if ($cycle->isChargeDay())
                            <span class="block text-sm font-semibold text-ink">@lang('app.dash.charge_today')</span>
                        @elseif ($cycle->isOverdue())
                            <span class="block text-sm font-semibold text-ink">@lang('app.dash.charge_passed')</span>
                        @else
                            <span class="u-figure block text-3xl text-ink" data-count-up="{{ $cycle->daysLeft }}" data-count-delay="280">{{ $cycle->daysLeft }}</span>
                            <span class="block text-sm font-semibold text-muted">{{ trans_choice('app.dash.days_left_unit', $cycle->daysLeft) }}</span>
                        @endif
                    </x-arc>
                </div>
            </div>
        @endif

        @if ($dash->note !== null && $dash->tone !== null && $dash->kind !== 'permanent')
            <div class="mt-4 flex items-center gap-3.5 rounded-xl px-4 py-3.5 text-base text-ink"
                style="background: {{ $dash->tone['bg'] }}">
                <span class="grid size-9 shrink-0 place-items-center rounded-lg bg-surface"
                    style="color: {{ $dash->tone['fg'] }}">
                    <x-icon :name="$dash->tone['icon']" size="size-4"/>
                </span>
                <span>{{ $dash->note }}</span>
            </div>
        @endif
    @endif
</section>
