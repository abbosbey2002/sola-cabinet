@extends('layouts.app')
@section('title', trans('app.nav.home').' - ')
@section('heading', trans('app.nav.home'))

@section('content')
    @php
        // The subscriber asks one question on this page and the whole card is
        // built to answer it: will the balance cover the next charge?
        //
        // Three states, and all three must look designed — a layout that only
        // works when everything is fine is not a design. Each one changes
        // colour AND icon AND wording together, so the difference survives
        // without colour vision.
        $balance = $profile->balance();
        $contract = $profile->contractNumber();

        // What actually comes off the balance at the next charge: the tariff
        // already queued to take over, when a switch is genuinely pending —
        // gated on nextTariff() (the name) rather than trusting
        // nextTariffCost() alone, in case billing ever leaves a stale cost
        // behind with no real switch queued. The current tariff's price
        // otherwise. The "current tariff" card lower on the page always
        // shows currentTariffCost(), regardless: that card is about the
        // tariff in force today, not what is billed next.
        $cost = $profile->nextTariff() !== null
            ? ($profile->nextTariffCost() ?? $profile->currentTariffCost())
            : $profile->currentTariffCost();

        $state = match (true) {
            $balance < 0 => 'negative',
            $cost !== null && $balance < $cost => 'low',
            $cost !== null => 'ok',
            // No tariff cost from billing means no verdict can honestly be
            // given. The balance is still shown; the sentence is not invented.
            default => null,
        };

        $tone = match ($state) {
            'ok' => ['bg' => 'var(--c-action-soft)', 'fg' => 'var(--c-action)', 'icon' => 'check'],
            'low' => ['bg' => 'var(--c-warn-soft)', 'fg' => 'var(--c-warn)', 'icon' => 'alert'],
            'negative' => ['bg' => 'var(--c-danger-soft)', 'fg' => 'var(--c-danger)', 'icon' => 'alert'],
            default => null,
        };

        $money = fn (float $value): string => number_format($value, 0, '', ' ');
        // A true minus sign, not a hyphen: at these sizes and in tabular
        // figures a hyphen reads as a dash between two numbers.
        $signed = fn (float $value): string => str_replace('-', '−', $money($value));

        // The cycle ring: how much of the billing month is left, read as a
        // share so it can share x-arc's geometry with the traffic gauge —
        // the one other place a number in this cabinet is a fraction of
        // something. Colour follows $tone so a subscriber who is short on
        // balance sees the same amber/red on the ring as on the balance
        // figure, not two unrelated signals.
        // totalDays is always >= 1 (ChargeCycle::endingAt clamps it), so no
        // division-by-zero guard is needed beyond the null check.
        $ringFraction = $cycle !== null
            ? $cycle->daysLeft / $cycle->totalDays
            : 0.0;
        $ringColor = $tone['fg'] ?? 'var(--c-action)';

        $note = match ($state) {
            // Full dd.mm.yyyy, matching the "Следующее списание" row above —
            // that row and this sentence state the same date, and showing it
            // two different ways in one card read as a typo (QA finding F-1,
            // 2026-08-29).
            'ok' => __('app.dash.balance_ok', [
                'date' => $cycle?->end->format('d.m.Y'),
                'amount' => $money((float) $cost),
            ]),
            'low' => __('app.dash.balance_low', [
                'date' => $cycle?->end->format('d.m.Y'),
                'amount' => $money((float) $cost - $balance),
            ]),
            'negative' => __('app.dash.balance_negative', [
                'amount' => $money(abs($balance) + (float) ($cost ?? 0)),
            ]),
            default => null,
        };

        $total = $totalDevices;
    @endphp

    {{-- Extra padding versus the plain u-card default (p-5/p-6): this is the
         one card on the page the subscriber's whole visit is about, so it
         gets more breathing room than the list-style cards below it — the
         same "most important thing gets the most space" hierarchy the
         balance figure itself already carries. --}}
    <section class="u-card u-card-hero u-rise p-6 sm:p-8" aria-labelledby="hero-title">
        <h2 id="hero-title" class="sr-only">@lang('app.dash.account_state')</h2>

        {{-- The balance is the one figure the subscriber's whole visit is
             about, so it gets its own top zone at full hero size rather than
             sharing a row with two other facts — the ring beside it answers
             the very next question ("when") in the same glance, coloured to
             match so the two signals read as one verdict, not two separate
             widgets. --}}
        <div class="flex flex-col gap-6 sm:flex-row sm:items-center sm:justify-between">
            <div class="min-w-0">
                <p class="u-label flex items-center gap-2">
                    <x-icon name="wallet" size="size-4"/>
                    @lang('app.header.balance')
                </p>
                <p class="mt-2 flex flex-wrap items-baseline gap-x-2.5">
                    <span class="u-figure text-4xl" @if ($balance < 0) style="color: var(--c-danger)" @endif>{{ $signed($balance) }}</span>
                    <span class="text-lg font-semibold text-muted">@lang('app.ye')</span>
                </p>
            </div>

            @if ($cycle !== null)
                <div class="flex shrink-0 justify-center" role="img"
                     aria-label="{{ $cycle->isChargeDay() ? __('app.dash.charge_today') : ($cycle->isOverdue() ? __('app.dash.charge_passed') : trans_choice('app.dash.days_left', $cycle->daysLeft, ['days' => $cycle->daysLeft])) }}">
                    <x-arc class="w-40 sm:w-44" :segments="[['fraction' => $ringFraction, 'color' => $ringColor]]" aria-hidden="true">
                        @if ($cycle->isChargeDay())
                            <span class="block text-base font-semibold text-ink">@lang('app.dash.charge_today')</span>
                        @elseif ($cycle->isOverdue())
                            <span class="block text-base font-semibold text-ink">@lang('app.dash.charge_passed')</span>
                        @else
                            <span class="u-figure block text-3xl text-ink">{{ $cycle->daysLeft }}</span>
                            <span class="block text-sm font-semibold text-muted">{{ trans_choice('app.dash.days_left_unit', $cycle->daysLeft) }}</span>
                        @endif
                    </x-arc>
                </div>
            @endif
        </div>

        {{-- Next charge and login stay as a compact secondary strip below the
             hero zone — same recessed-panel treatment as before, just no
             longer carrying the balance row too. --}}
        <div class="mt-5 space-y-2.5 border-t border-line pt-5">
            @if ($cycle !== null)
                <div class="flex flex-wrap items-center justify-between gap-x-6 gap-y-1 rounded-xl p-4" style="background: var(--c-bg)">
                    <p class="u-label">@lang('app.dash.next_charge')</p>
                    <p class="flex flex-wrap items-baseline gap-x-2">
                        @if ($cost !== null)
                            <span class="u-figure text-base text-ink">{{ $money($cost) }}</span>
                            <span class="text-sm text-muted">@lang('app.ye') · {{ $cycle->end->format('d.m.Y') }}</span>
                        @else
                            <span class="u-figure text-base text-ink">{{ $cycle->end->format('d.m.Y') }}</span>
                        @endif
                    </p>
                </div>
            @endif

            {{-- text-sm/text-muted, not the text-base/text-ink weight the
                 money row above uses: this is a reference id to copy
                 elsewhere, not a figure the subscriber came here to read —
                 giving it the same weight as the next-charge row made it
                 compete with the number that actually matters. --}}
            @if ($billingLogin !== '')
                <div class="flex flex-wrap items-center justify-between gap-x-6 gap-y-1 rounded-xl p-4" style="background: var(--c-bg)">
                    <p class="u-label">@lang('app.cabinet.login')</p>
                    <span class="flex items-center gap-2">
                        <span class="text-sm font-medium text-muted">{{ $billingLogin }}</span>
                        <button type="button" data-copy="{{ $billingLogin }}" data-copy-done="@lang('app.ui.copied')"
                                class="u-no-print grid size-11 shrink-0 place-items-center rounded-full text-muted transition-colors hover:bg-surface-2 hover:text-ink">
                            <span data-copy-icon-default><x-icon name="copy" size="size-4"/></span>
                            <span data-copy-icon-done hidden style="color: var(--c-action)"><x-icon name="check" size="size-4"/></span>
                            <span data-copy-text role="status" class="sr-only">@lang('app.ui.copy')</span>
                        </button>
                    </span>
                </div>
            @endif
        </div>

        {{-- The icon sits on its own surface-coloured tile rather than bare
             on the tinted strip — the same "chip on a tinted ground" reading
             x-pay-card already uses, so the one alert this page ever shows
             still looks like it belongs to the same product instead of a
             plain system warning box. --}}
        @if ($note !== null)
            <div class="mt-4 flex items-center gap-3.5 rounded-xl px-4 py-3.5 text-base text-ink"
                 style="background: {{ $tone['bg'] }}">
                <span class="grid size-9 shrink-0 place-items-center rounded-lg bg-surface" style="color: {{ $tone['fg'] }}">
                    <x-icon :name="$tone['icon']" size="size-4"/>
                </span>
                <span>{{ $note }}</span>
            </div>
        @endif

        {{-- $state === null means no cost to judge the balance against, so
             the "will it last" note above never fires — but a subscriber
             with literally no tariff yet still needs a next step, not
             silence. Not shown for a legal entity: TariffController 403s
             them on /tariffs the same way the "current tariff" card below
             already declines to link there for them. --}}
        @if ($note === null && $profile->currentTariff() === null && ! $profile->isLegalEntity())
            <div class="mt-4 flex flex-wrap items-center gap-3.5 rounded-xl px-4 py-3.5 text-base text-ink" style="background: var(--c-action-soft)">
                <span class="grid size-9 shrink-0 place-items-center rounded-lg bg-surface" style="color: var(--c-action)">
                    <x-icon name="tag" size="size-4"/>
                </span>
                <span class="min-w-0 flex-1">@lang('app.cabinet.no_tariff_hint')</span>
                <a href="{{ route('tariff') }}" class="u-btn-ghost u-btn-sm shrink-0">@lang('app.dash.choose_tariff')</a>
            </div>
        @endif
    </section>

    {{-- The one actionable thing when the balance needs attention: the
         contract number, sized and copyable rather than folded into the
         sentence above, and/or the iWon top-up button. Not shown for
         $state === null (no tariff cost to judge the balance against) —
         that's "no verdict", not "trouble". Shown even without a contract
         number (billing does not send one for every account) as long as
         iWon is active — pay-card itself skips the contract half when
         $contract is blank, but the top-up button never depends on it. --}}
    @if (in_array($state, ['low', 'negative'], true) && (filled($contract) || config('iwon.active')))
        <x-pay-card :contract="$contract" :tone="$state === 'negative' ? 'danger' : 'warn'" class="mt-4"/>
    @endif

    {{-- Three questions, three links. Each one leads to its own page, which is
         why no detail is kept here. --}}
    <div class="mt-4 grid gap-4 md:grid-cols-3">
        @php
            // This card is the tariff in force today, so its price is always
            // currentTariffCost() — never $cost above, which prefers the
            // queued tariff's price for the "will my balance last" verdict.
            $currentCost = $profile->currentTariffCost();
        @endphp

        @php
            $cards = [
                [
                    'route' => 'tariff',
                    'icon' => 'tag',
                    'label' => __('app.dash.current_tariff'),
                    'value' => $profile->currentTariffDisplayName() ?: __('app.header.no_tariff'),
                    'hint' => $currentCost !== null ? $money($currentCost).' '.__('app.ye') : null,
                    // A legal entity (yuridik shaxs) may not switch tariffs —
                    // TariffController::index()/connect() still 403s them, see
                    // AbonentProfile::isLegalEntity() — so this card stays
                    // informational only, with no link to the tariff page.
                    'clickable' => ! $profile->isLegalEntity(),
                ],
                [
                    'route' => 'devices',
                    'icon' => 'router',
                    'label' => __('app.nav.devices'),
                    // Just the count now, no active/offline breakdown — a
                    // per-device online status is no longer shown anywhere
                    // in the cabinet (dropped at the user's request,
                    // 2026-08-28, alongside the status column on /devices).
                    'value' => trans_choice('app.dash.devices_total', $total, ['count' => $total]),
                    'hint' => null,
                ],
                [
                    'route' => 'payment',
                    'icon' => 'receipt',
                    'label' => __('app.dash.last_payment'),
                    // A "no payments this month" sentence is not a value, so it
                    // is not set at value size: three cards side by side, one
                    // of them a headline-sized sentence, read as an error.
                    'value' => $lastPayment !== null
                        ? $money((float) $lastPayment['amount'] / 100).' '.__('app.ye')
                        : null,
                    'hint' => $lastPayment !== null
                        ? \Carbon\Carbon::parse($lastPayment['payment_date'])->format('d.m.Y').' · '.$lastPayment['payment_system']
                        : __('app.empty.payments'),
                ],
            ];
        @endphp

        @foreach ($cards as $i => $card)
            @php($clickable = $card['clickable'] ?? true)
            <{{ $clickable ? 'a' : 'div' }}
               @if ($clickable) href="{{ route($card['route']) }}" @endif
               class="u-card u-rise flex items-start gap-4 no-underline{{ $clickable ? ' group transition-[border-color,transform,box-shadow] hover:-translate-y-0.5 hover:border-action' : ' cursor-default' }}"
               style="--i:{{ $i + 1 }}">
                <span class="grid size-11 shrink-0 place-items-center rounded-xl" style="background: var(--c-action-soft); color: var(--c-action)">
                    <x-icon :name="$card['icon']" size="size-5"/>
                </span>
                <span class="min-w-0 flex-1">
                    <span class="u-label block">{{ $card['label'] }}</span>
                    @if ($card['value'])
                        <span class="mt-1.5 block text-xl font-semibold text-ink">{{ $card['value'] }}</span>
                    @endif
                    @if ($card['hint'])
                        <span class="mt-1.5 block text-sm text-muted">{{ $card['hint'] }}</span>
                    @endif
                </span>
                @if ($clickable)
                    <x-icon name="chevron-right" class="mt-1 shrink-0 text-muted transition-transform group-hover:translate-x-0.5"/>
                @endif
            </{{ $clickable ? 'a' : 'div' }}>
        @endforeach
    </div>

    <a href="{{ route('services') }}"
       class="u-card u-rise mt-4 flex items-center justify-between gap-4 no-underline transition-[border-color] hover:border-action"
       style="--i:4">
        <span class="flex min-w-0 items-center gap-4">
            <span class="grid size-12 shrink-0 place-items-center rounded-xl"
                  style="background: var(--c-action-soft); color: var(--c-action)">
                <x-icon name="gift" size="size-6"/>
            </span>
            <span class="min-w-0">
                <span class="block text-lg font-semibold text-ink">@lang('app.services.entry_title')</span>
                <span class="mt-1 block text-sm text-muted">@lang('app.services.entry_text')</span>
            </span>
        </span>
        <x-icon name="chevron-right" class="shrink-0 text-muted"/>
    </a>
@endsection
