@props(['cycle'])

{{--
    THE SIGNATURE ELEMENT: a radial "days left" ring beside a calendar-page
    chip for the charge date. Replaces the earlier scrubber-style track
    (2026-08): that track borrowed slider affordance — a track, a fill, a
    draggable-looking handle — for something that was never draggable, which
    read as a broken control rather than a picture. A ring reads as a clock
    or a countdown, never as something to drag, so it needed no fake handle
    at all — and no separate "charge day" marker either: unlike the track,
    the ring's own 0%/100% endpoints ARE the cycle's start and the charge
    day, so a second marker would only repeat what the ring already shows.

    Still a picture only: aria-hidden, because the sentence printed directly
    under it in index.blade.php ("24 days until the charge") already says
    the same thing in words — the count inside the ring is a visual echo of
    that sentence's number, not a second source of truth.

    Rendered server-side rather than built in JavaScript: it is the first
    thing on the page and it must be there in the first paint.
--}}

@php
    $span = $cycle->totalDays - 1;
    $pct = $cycle->currentDay > 0
        ? ($span > 0 ? max(0, min(100, ($cycle->currentDay - 1) / $span * 100)) : 100)
        : 0;

    // Stroke drawn from 12 o'clock (via the rotate(-90deg) on the <svg>),
    // clockwise, empty past 100% — the standard radial-progress reading.
    $radius = 34;
    $circumference = round(2 * M_PI * $radius, 2);
    $offset = round($circumference * (1 - $pct / 100), 2);

    // No separate abbreviation list: the chip truncates the same translated
    // month name MonthList already builds pickers from (app.months.*),
    // rather than inventing a second one to keep in sync. Four characters,
    // not three — Uzbek's "Iyun"/"Iyul" (June/July) only tell apart at the
    // fourth letter.
    $chargeMonth = mb_strtoupper(mb_substr(trans('app.months.'.$cycle->end->month), 0, 4));
@endphp

<div {{ $attributes->merge(['class' => 'u-meter']) }} aria-hidden="true">
    <div class="u-ring-row">
        <div class="u-ring">
            <svg width="84" height="84" viewBox="0 0 84 84">
                <circle class="u-ring-track" cx="42" cy="42" r="{{ $radius }}" stroke-width="7" fill="none"/>
                <circle class="u-ring-fill" cx="42" cy="42" r="{{ $radius }}" stroke-width="7" fill="none"
                        stroke-dasharray="{{ $circumference }}" stroke-dashoffset="{{ $offset }}"/>
            </svg>
            <span class="u-ring-count">
                <b>{{ $cycle->daysLeft }}</b>
                <span>{{ trans_choice('app.dash.days_left_unit', $cycle->daysLeft) }}</span>
            </span>
        </div>

        <div class="u-cal-chip">
            <span class="u-cal-chip-month">{{ $chargeMonth }}</span>
            <span class="u-cal-chip-day">{{ $cycle->end->format('d') }}</span>
        </div>
    </div>
</div>
