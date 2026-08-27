@extends('layouts.app')
@section('title', trans('app.nav.tariff').' - ')
@section('heading', trans('app.nav.tariff'))

@section('content')
    @php
        // Spec §3.2. Only permanent subscribers may switch a tariff; for
        // everyone else the page is read-only. $isPermanent arrives from the
        // view composer — see the note in devices.blade.php.
        $current = $profile->currentTariff();
        $next = $profile->nextTariff();
        $canSwitch = $isPermanent || blank($current);

        // Locate a tariff in the switchable list. Prefer the id /abonent/info
        // reports: billing pads some names with a trailing space ("Paket 5 soat "),
        // so matching on the name alone silently loses the row. The name is only
        // a fallback — the next tariff arrives without an id.
        $find = function (?string $id, ?string $name) use ($tariffs): ?array {
            foreach ($tariffs as $tariff) {
                $matches = filled($id)
                    ? (string) $tariff['tariff_id'] === $id
                    : filled($name) && trim((string) $tariff['tariff_name']) === trim($name);

                if ($matches) {
                    return $tariff;
                }
            }

            return null;
        };

        $speed = fn (array $t): string => $t['tspd'].' '.($t['spdu'] === 'Mbps' ? __('app.tariff.unit_mb') : __('app.tariff.unit_kb'));

        $validity = fn (array $t): string => $t['tprd'].' '.match ($t['prdu']) {
            'HOUR' => __('app.tariff.hour'),
            'MIN' => __('app.tariff.minut'),
            default => __('app.tariff.day'),
        };

        $volume = fn (array $t): string => (int) $t['vol'] === 0
            ? __('app.tariff.no_limit')
            : number_format((float) $t['vol'], 0, '', ' ').' '.__('app.traffic.mb');

        $currentMatch = $find($profile->currentTariffId(), $current);
        $cost = $profile->currentTariffCost();

        // /abonent/info gives a tariff's name but none of its terms. When the
        // same tariff also appears in the switchable list its speed and
        // validity are right there, so they are shown rather than left blank.
        $terms = array_values(array_filter([
            $currentMatch !== null ? $speed($currentMatch) : null,
            $currentMatch !== null ? $validity($currentMatch) : null,
            $currentMatch !== null ? $volume($currentMatch) : null,
            $cost !== null ? number_format($cost, 0, '', ' ').' '.__('app.ye') : null,
        ]));
    @endphp

    <section class="u-card u-rise" aria-labelledby="current-title">
        <h2 id="current-title" class="u-label">@lang('app.tariff.current')</h2>

        @if (filled($current))
            <p class="mt-1.5 text-2xl font-bold text-ink">{{ $current }}</p>

            @if ($terms)
                <p class="mt-1 text-base text-muted">{{ implode(' · ', $terms) }}</p>
            @endif

            {{-- From /tariff/connected, not /abonent/info: the profile carries
                 the tariff's name but never the day it started. --}}
            @if ($startedAt !== null)
                <p class="mt-1 text-base text-muted">@lang('app.tariff.started_at', ['date' => $startedAt->format('d.m.Y')])</p>
            @endif
        @else
            <p class="mt-1.5 text-2xl font-bold text-muted">@lang('app.header.no_tariff')</p>
            <p class="mt-1 text-base text-muted">@lang('app.cabinet.no_tariff_hint')</p>
        @endif

        <p class="mt-4 flex items-start gap-3 rounded-xl bg-surface-2 px-4 py-3.5 text-base text-ink">
            <x-icon name="clock" class="mt-1 text-muted"/>
            <span>
                @if (filled($next))
                    @lang('app.tariff.next_is', ['tariff' => $next])
                @elseif (filled($current))
                    @lang('app.tariff.next_continues', ['tariff' => $current])
                @else
                    @lang('app.dash.not_selected')
                @endif
            </span>
        </p>
    </section>

    <section class="u-card u-rise mt-4" style="--i:1" aria-labelledby="switch-title">
        <h2 id="switch-title" class="text-xl font-bold text-ink">@lang('app.tariff.switch_title')</h2>

        @if (! $tariffs)
            <x-empty icon="gauge" :title="__('app.empty.tariffs')" :hint="__('app.empty.tariffs_hint')"/>
        @elseif (! $canSwitch)
            {{-- The list is still worth showing: a temporary subscriber can see
                 what is on offer even though the switch is not theirs to make. --}}
            <p class="mt-2 text-base text-muted">@lang('app.tariff.switch_locked')</p>

            <ul class="mt-4 grid gap-2.5 sm:grid-cols-2">
                @foreach ($tariffs as $tariff)
                    {{-- Name and price share a wrapping line rather than sitting in
                         fixed columns. A tariff called "SSID - 56 000 000 sum" beside
                         a seven-figure price left the name ~130px on a 360px screen
                         and broke it one word per row; now the price drops to its own
                         line exactly when it stops fitting, at any width. --}}
                    <li class="rounded-xl border-2 border-line px-4 py-3.5">
                        <span class="flex flex-wrap items-baseline justify-between gap-x-3">
                            <span class="text-base font-semibold text-ink">{{ trim((string) $tariff['tariff_name']) }}</span>
                            <span class="text-base font-semibold text-ink">
                                {{ number_format($tariff['cost'] / 100, 0, '', ' ') }}
                                <span class="text-sm font-normal text-muted">@lang('app.ye')</span>
                            </span>
                        </span>
                        <span class="mt-0.5 block text-sm text-muted">{{ $speed($tariff) }} · {{ $validity($tariff) }}</span>
                    </li>
                @endforeach
            </ul>
        @else
            {{-- POST, and the timing travels in the body. This form spends the
                 subscriber's money, so it needs a CSRF token; as a GET link it
                 had none. Without JavaScript it still submits, using the hidden
                 default below — no dialog, but no dead button either. --}}
            <form method="post" action="{{ route('tariff.connect') }}"
                  data-tariff-flow data-choose-timing="{{ $isPermanent ? '1' : '0' }}">
                @csrf

                {{-- The conservative default: next billing period, so a submit
                     without the dialog never triggers an immediate charge. --}}
                <input type="hidden" name="timing" data-tariff-timing-field
                       value="{{ $isPermanent ? 'month' : 'now' }}">

                <fieldset class="mt-4 border-0 p-0">
                    <legend class="u-label mb-2.5">@lang('app.dash.available')</legend>

                    <div class="grid gap-2.5 sm:grid-cols-2">
                        @foreach ($tariffs as $tariff)
                            @php
                                $isCurrent = $currentMatch !== null && $tariff['tariff_id'] === $currentMatch['tariff_id'];
                                $name = trim((string) $tariff['tariff_name']);
                            @endphp

                            {{-- items-start, not items-center: the specs line runs to
                                 two or three rows on a phone, and a radio centred
                                 against that block drifts away from the name it
                                 selects. Name and price share a wrapping line — see
                                 the note on the read-only list above. --}}
                            <label class="flex cursor-pointer items-start gap-3.5 rounded-xl border-2 border-line px-4 py-3.5 transition-colors hover:border-action hover:bg-surface-2 has-[:checked]:border-action has-[:checked]:bg-surface-2 has-[:disabled]:cursor-default has-[:disabled]:opacity-60 has-[:disabled]:hover:border-line has-[:disabled]:hover:bg-transparent">
                                <input type="radio" name="tariff" value="{{ $tariff['tariff_id'] }}"
                                       data-tariff-option data-tariff-name="{{ $name }}"
                                       @disabled($isCurrent)
                                       class="mt-0.5 size-5 shrink-0" style="accent-color: var(--c-action)">

                                <span class="min-w-0 flex-1">
                                    <span class="flex flex-wrap items-baseline justify-between gap-x-3">
                                        <span class="text-base font-semibold text-ink">{{ $name }}</span>
                                        <span class="text-base font-semibold text-ink">
                                            {{ number_format($tariff['cost'] / 100, 0, '', ' ') }}
                                            <span class="text-sm font-normal text-muted">@lang('app.ye')</span>
                                        </span>
                                    </span>
                                    <span class="mt-0.5 block text-sm text-muted">
                                        {{ $speed($tariff) }} · {{ $validity($tariff) }}
                                        @if ((int) $tariff['vol'] !== 0)
                                            · {{ $volume($tariff) }}
                                        @endif
                                        @if ($isCurrent)
                                            · @lang('app.tariff.current_hint')
                                        @endif
                                    </span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>

                <button type="submit" data-tariff-connect disabled
                        class="u-btn-primary u-no-print mt-5 w-full disabled:pointer-events-none disabled:opacity-40 sm:w-auto">
                    @lang('app.dash.change_tariff')
                </button>
            </form>

            <p class="mt-4 text-sm text-muted">@lang('app.tariff.switch_hint')</p>
        @endif
    </section>
@endsection

@push('js')
    <x-modal name="tariff-confirm" :label="__('app.ui.confirm')">
        <div class="text-center">
            <div class="mx-auto grid size-12 place-items-center rounded-xl"
                 style="background: var(--c-warn-soft); color: var(--c-warn)">
                <x-icon name="alert" size="size-6"/>
            </div>
            <p class="mt-4 text-base font-semibold leading-snug text-ink">@lang('app.modal.are_you_sure')</p>
            <p data-tariff-name-slot class="mt-1 text-base font-semibold" style="color: var(--c-action)"></p>
        </div>

        <div class="mt-6 flex flex-wrap gap-3">
            <button type="button" data-modal-close class="u-btn-ghost flex-1">@lang('app.no')</button>
            <button type="button" data-tariff-accept class="u-btn-primary flex-1">@lang('app.yes')</button>
        </div>
    </x-modal>

    @if ($isPermanent)
        <x-modal name="tariff-timing" :title="__('app.modal.to_connect')">
            <p data-tariff-name-slot class="-mt-2 mb-4 text-base font-semibold" style="color: var(--c-action)"></p>

            <div class="space-y-2.5">
                @foreach ([
                    ['timing' => 'now', 'icon' => 'speed', 'title' => __('app.modal.now'), 'hint' => __('app.modal.now_hint')],
                    ['timing' => 'month', 'icon' => 'calendar', 'title' => __('app.modal.month'), 'hint' => __('app.modal.month_hint', ['date' => $nextPeriodStart->format('d.m.Y')])],
                ] as $choice)
                    <button type="button" data-tariff-timing="{{ $choice['timing'] }}"
                            class="flex w-full items-center gap-3.5 rounded-xl border-2 border-line p-4 text-left transition-colors hover:border-action hover:bg-surface-2">
                        <span class="grid size-11 shrink-0 place-items-center rounded-xl"
                              style="background: var(--c-action-soft); color: var(--c-action)">
                            <x-icon :name="$choice['icon']"/>
                        </span>
                        <span>
                            <span class="block text-base font-semibold text-ink">{{ $choice['title'] }}</span>
                            <span class="block text-sm text-muted">{{ $choice['hint'] }}</span>
                        </span>
                    </button>
                @endforeach
            </div>
        </x-modal>
    @endif
@endpush
