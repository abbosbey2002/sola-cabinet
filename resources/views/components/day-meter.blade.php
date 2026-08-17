@props(['cycle'])

{{--
    THE SIGNATURE ELEMENT: one tick per day of the billing cycle, spent days in
    the logo's own green, today in white and taller, the charge day in amber and
    taller still. Height carries the distinction as well as colour, so the two
    marked days are still distinguishable without colour vision.

    Rendered server-side as a plain loop rather than built in JavaScript: it is
    the first thing on the page and it must be there in the first paint.

    aria-hidden, because it is a picture of the sentence printed directly under
    it ("24 days until the charge"). Reading out thirty-one empty spans would be
    noise, not information.
--}}

<div {{ $attributes->merge(['class' => 'u-meter']) }} aria-hidden="true">
    @for ($day = 1; $day <= $cycle->totalDays; $day++)
        @php
            $tone = match (true) {
                $day === $cycle->chargeDay() => 'charge',
                $day === $cycle->currentDay => 'today',
                $day < $cycle->currentDay => 'past',
                default => null,
            };
        @endphp

        <span class="u-tick" @if ($tone) data-t="{{ $tone }}" @endif></span>
    @endfor
</div>
