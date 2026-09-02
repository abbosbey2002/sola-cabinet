@props([
    'dash',
    'profile',
])

@php
    $isPermanent = $dash->kind === 'permanent';
    $showContract = in_array($dash->kind, ['permanent', 'legal'], true);
    $contract = $showContract ? $profile->contractNumber() : null;
@endphp

<section {{ $attributes->merge(['class' => 'u-card u-card-dash-balance u-rise p-5 sm:p-6', 'style' => '--i:0']) }}
    aria-labelledby="dash-balance-title">
    <h2 id="dash-balance-title" class="sr-only">@lang('app.header.balance')</h2>

    @if ($isPermanent)
        {{-- Permanent: amount, top-up and balance note in one tight stack. --}}
        <p class="u-label flex items-center gap-2">
            <x-icon name="wallet" size="size-4"/>
            @lang('app.header.balance')
        </p>

        <div class="mt-3 flex flex-wrap items-center justify-between gap-x-4 gap-y-3">
            <p class="flex flex-wrap items-baseline gap-x-2.5">
                <span class="u-figure text-4xl"
                    data-count-up="{{ (int) round($dash->balance) }}"
                    @if ($dash->balance < 0) style="color: var(--c-danger)" @endif>{{ $dash->formatSigned($dash->balance) }}</span>
                <span class="text-lg font-semibold text-muted">@lang('app.ye')</span>
            </p>

            @if ($dash->canTopUp)
                <a href="{{ route('topup') }}" data-modal-open="topup-modal"
                    class="u-btn-primary u-no-print inline-flex w-full shrink-0 sm:w-auto">
                    <x-icon name="wallet" size="size-5"/>@lang('app.topup.pay_card_button')
                </a>
            @endif
        </div>

        @if ($dash->note !== null && $dash->tone !== null)
            <div class="mt-3 flex items-center gap-3.5 rounded-xl px-4 py-3.5 text-base text-ink"
                style="background: {{ $dash->tone['bg'] }}">
                <span class="grid size-9 shrink-0 place-items-center rounded-lg bg-surface"
                    style="color: {{ $dash->tone['fg'] }}">
                    <x-icon :name="$dash->tone['icon']" size="size-4"/>
                </span>
                <span>{{ $dash->note }}</span>
            </div>
        @endif
    @else
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div class="min-w-0">
                <p class="u-label flex items-center gap-2">
                    <x-icon name="wallet" size="size-4"/>
                    @lang('app.header.balance')
                </p>
                <p class="mt-0.5 text-sm text-muted">@lang('app.payment.balance_hint')</p>
                <p class="mt-2 flex flex-wrap items-baseline gap-x-2.5">
                    <span class="u-figure text-4xl"
                        data-count-up="{{ (int) round($dash->balance) }}"
                        @if ($dash->balance < 0) style="color: var(--c-danger)" @endif>{{ $dash->formatSigned($dash->balance) }}</span>
                    <span class="text-lg font-semibold text-muted">@lang('app.ye')</span>
                </p>
            </div>

            @if ($dash->canTopUp)
                <a href="{{ route('topup') }}" data-modal-open="topup-modal"
                    class="u-btn-primary u-no-print inline-flex w-full shrink-0 sm:w-auto">
                    <x-icon name="wallet" size="size-5"/>@lang('app.topup.pay_card_button')
                </a>
            @endif
        </div>
    @endif

    @if (filled($contract))
        <div class="mt-3 flex items-start justify-between gap-3 rounded-xl p-4" style="background: var(--c-bg)">
            <div class="min-w-0">
                <p class="u-label">@lang('app.dash.contract')</p>
                <p class="mt-1 u-figure text-xl text-ink">{{ $contract }}</p>
            </div>
            <button type="button" data-copy="{{ $contract }}" data-copy-done="@lang('app.ui.copied')"
                class="u-icon-btn u-no-print shrink-0" aria-label="@lang('app.ui.copy')">
                <span data-copy-icon-default><x-icon name="copy" size="size-4"/></span>
                <span data-copy-icon-done hidden style="color: var(--c-action)"><x-icon name="check" size="size-4"/></span>
                <span data-copy-text role="status" class="sr-only">@lang('app.ui.copy')</span>
            </button>
        </div>
    @endif
</section>
