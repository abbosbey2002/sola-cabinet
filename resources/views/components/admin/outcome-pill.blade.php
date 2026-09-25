{{-- An activity event's outcome, on the same status contract as result-pill. --}}
@props(['outcome'])

@php
    [$class, $icon] = match ($outcome) {
        'ok' => ['u-pill-ok', 'check'],
        'fail' => ['u-pill-off', 'alert'],
        'denied' => ['u-pill-neutral', 'shield'],
        default => ['u-pill-warn', 'minus'],
    };
@endphp

<span {{ $attributes->merge(['class' => $class.' whitespace-nowrap']) }}>
    <x-icon :name="$icon" size="size-4"/>{{ __('admin.outcomes.'.$outcome) }}
</span>
