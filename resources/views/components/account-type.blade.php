@props(['type', 'as' => 'pill'])

@php
    // 0 temporary, 1 one-off, 2 permanent — the three subscriber kinds the
    // billing API reports. Anything unknown is treated as permanent, which is
    // what the old templates did with their trailing @else.
    //
    // Each pill is colour AND icon AND word: a subscriber who cannot separate
    // the colours still has the glyph, one who does not read the glyph still
    // has the label.
    [$key, $class, $icon] = match ((int) $type) {
        0 => ['tempary', 'u-pill-warn', 'clock'],
        1 => ['one_time', 'u-pill-neutral', 'clock'],
        default => ['current', 'u-pill-ok', 'check'],
    };
@endphp

@if ($as === 'text')
    <span {{ $attributes }}>@lang("app.accounts.$key")</span>
@else
    <span {{ $attributes->merge(['class' => $class]) }}>
        <x-icon :name="$icon" size="size-4"/>@lang("app.accounts.$key")
    </span>
@endif
