{{--
    The one status contract for a tariff change result — same colour, icon and
    words in the list, the detail page and the account history. Colour is never
    the only signal: every pill carries an icon and a label.
--}}
@props(['result'])

@php
    [$class, $icon] = match ($result) {
        'success' => ['u-pill-ok', 'check'],
        'insufficient_funds' => ['u-pill-warn', 'wallet'],
        'billing_error' => ['u-pill-off', 'alert'],
        'unavailable' => ['u-pill-off', 'clock'],
        'denied' => ['u-pill-neutral', 'shield'],
        default => ['u-pill-neutral', 'clock'],
    };
@endphp

<span {{ $attributes->merge(['class' => $class.' whitespace-nowrap']) }}>
    <x-icon :name="$icon" size="size-4"/>{{ __('admin.results.'.$result) }}
</span>
