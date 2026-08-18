@props(['cycle'])

{{--
    THE SIGNATURE ELEMENT, drawn as a scrubber rather than a tick row: one
    track spanning the billing cycle, filled lime up to today and capped with
    a handle — the same visual grammar as a slider, so the card reads as time
    moving along a track instead of a bare bar chart.

    It is a picture only: aria-hidden, because the sentence printed directly
    under it ("24 days until the charge") already says the same thing in
    words, and nothing here is actually draggable.

    Today and the charge day are still told apart by shape as well as colour
    — circle vs. diamond — never colour alone, because that is the one
    distinction on this card that must survive without colour vision. When
    today IS the charge day the handle itself becomes the diamond, rather
    than drawing two markers on top of one another.

    Rendered server-side rather than built in JavaScript: it is the first
    thing on the page and it must be there in the first paint.
--}}

@php
    $span = $cycle->totalDays - 1;
    $pct = fn (int $day): float => $span > 0 ? round(max(0, min(100, ($day - 1) / $span * 100)), 2) : 100;

    $chargePct = $pct($cycle->chargeDay());
    $todayPct = $cycle->currentDay > 0 ? $pct($cycle->currentDay) : null;
    $isChargeToday = $todayPct !== null && $cycle->currentDay === $cycle->chargeDay();
@endphp

<div {{ $attributes->merge(['class' => 'u-meter']) }} aria-hidden="true">
    <div class="u-meter-track">
        <div class="u-meter-fill" style="width: {{ $todayPct ?? 0 }}%"></div>

        @unless ($isChargeToday)
            <span class="u-meter-charge" style="left: {{ $chargePct }}%"></span>
        @endunless

        @if ($todayPct !== null)
            <span class="u-meter-handle" style="left: {{ $todayPct }}%" @if ($isChargeToday) data-t="charge" @endif></span>
        @endif
    </div>
</div>
