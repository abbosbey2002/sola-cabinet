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

        $note = match ($state) {
            'ok' => __('app.dash.balance_ok', [
                'date' => $cycle?->end->format('d.m'),
                'amount' => $money((float) $cost),
            ]),
            'low' => __('app.dash.balance_low', [
                'date' => $cycle?->end->format('d.m'),
                'amount' => $money((float) $cost - $balance),
            ]),
            'negative' => __('app.dash.balance_negative', [
                'amount' => $money(abs($balance) + (float) ($cost ?? 0)),
            ]),
            default => null,
        };

        $active = $activeDevices;
        $total = $totalDevices;
        $offline = max(0, $total - $active);
    @endphp

    {{-- Extra padding versus the plain u-card default (p-5/p-6): this is the
         one card on the page the subscriber's whole visit is about, so it
         gets more breathing room than the list-style cards below it — the
         same "most important thing gets the most space" hierarchy the
         balance figure itself already carries. --}}
    <section class="u-card u-rise p-6 sm:p-8" aria-labelledby="hero-title">
        <h2 id="hero-title" class="sr-only">@lang('app.dash.account_state')</h2>

        <div class="flex flex-wrap items-start justify-between gap-x-8 gap-y-5">
            <div>
                <p class="u-label">@lang('app.header.balance')</p>
                <p class="mt-1.5 flex flex-wrap items-baseline gap-x-2.5">
                    <span class="u-figure text-4xl" @if ($balance < 0) style="color: var(--c-danger)" @endif>{{ $signed($balance) }}</span>
                    <span class="text-lg font-semibold text-muted">@lang('app.ye')</span>
                </p>
            </div>

            @if ($cycle !== null)
                <div class="sm:text-right">
                    <p class="u-label">@lang('app.dash.next_charge')</p>
                    <p class="mt-1.5 flex flex-wrap items-baseline gap-x-2.5 sm:justify-end">
                        @if ($cost !== null)
                            <span class="u-figure text-xl text-ink">{{ $money($cost) }}</span>
                            <span class="text-base text-muted">@lang('app.ye') · {{ $cycle->end->format('d.m.Y') }}</span>
                        @else
                            <span class="u-figure text-xl text-ink">{{ $cycle->end->format('d.m.Y') }}</span>
                        @endif
                    </p>
                </div>
            @endif
        </div>

        {{-- A rule, not just a gap: this is account metadata, not a third
             balance figure, and the divider says so before the eye even
             reads the label. --}}
        @if ($billingLogin !== '')
            <div class="mt-6 border-t border-line pt-5">
                <p class="u-label">@lang('app.cabinet.login')</p>
                <p class="mt-1.5 text-lg font-semibold text-ink">{{ $billingLogin }}</p>
            </div>
        @endif

        {{-- The icon sits on its own surface-coloured tile rather than bare
             on the tinted strip — the same "chip on a tinted ground" reading
             the pay-card and the services entry below already use, so the
             one alert this page ever shows still looks like it belongs to
             the same product instead of a plain system warning box. --}}
        @if ($note !== null)
            <div class="mt-5 flex items-center gap-3.5 rounded-xl px-4 py-3.5 text-base text-ink"
                 style="background: {{ $tone['bg'] }}">
                <span class="grid size-9 shrink-0 place-items-center rounded-lg bg-surface" style="color: {{ $tone['fg'] }}">
                    <x-icon :name="$tone['icon']" size="size-4"/>
                </span>
                <span>{{ $note }}</span>
            </div>
        @endif
    </section>

    {{-- The one actionable thing when the balance needs attention: the
         contract number, sized and copyable rather than folded into the
         sentence above. Not shown for $state === null (no tariff cost to
         judge the balance against) — that's "no verdict", not "trouble". --}}
    @if (in_array($state, ['low', 'negative'], true) && filled($contract))
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
                    'label' => __('app.dash.current_tariff'),
                    'value' => $profile->currentTariff() ?: __('app.header.no_tariff'),
                    'hint' => $currentCost !== null ? $money($currentCost).' '.__('app.ye') : null,
                    // A legal entity (yuridik shaxs) may not switch tariffs —
                    // TariffController::index()/connect() still 403s them, see
                    // AbonentProfile::isLegalEntity() — so this card stays
                    // informational only, with no link to the tariff page.
                    'clickable' => ! $profile->isLegalEntity(),
                ],
                [
                    'route' => 'devices',
                    'label' => __('app.nav.devices'),
                    'value' => __('app.dash.active_of', ['active' => $active, 'total' => $total]),
                    'hint' => $offline > 0
                        ? trans_choice('app.dash.offline_count', $offline, ['count' => $offline])
                        : __('app.dash.all_online'),
                ],
                [
                    'route' => 'payment',
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
               class="u-card u-rise flex items-start justify-between gap-4 no-underline{{ $clickable ? ' group transition-[border-color,transform] hover:-translate-y-px hover:border-action' : ' cursor-default' }}"
               style="--i:{{ $i + 1 }}">
                <span class="min-w-0">
                    <span class="u-label block">{{ $card['label'] }}</span>
                    @if ($card['value'])
                        <span class="mt-1.5 block text-xl font-semibold text-ink">{{ $card['value'] }}</span>
                    @endif
                    @if ($card['hint'])
                        <span class="mt-1.5 block text-sm text-muted">{{ $card['hint'] }}</span>
                    @endif
                </span>
                @if ($clickable)
                    <x-icon name="chevron-right" class="mt-1 text-muted transition-transform group-hover:translate-x-0.5"/>
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
