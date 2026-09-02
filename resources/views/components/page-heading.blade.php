@props([
    'icon' => null,
])

{{--
    Page identity strip. The section icon sits on the TITLE row only — the
    lead, when there is one, starts under the words, not under the chip. That
    is the placement that reads as "this page, this section", rather than a
    stacked card header.

    Icon names follow the same sprite as the top nav, so a subscriber who
    tapped "Qurilmalar" lands under the same mark. Unknown names fall back
    to the route map; a name that is in neither is omitted rather than
    rendering the alert glyph (icon.blade.php's unknown-name fallback).
--}}

@php
    $fromRoute = [
        'cabinet' => 'home',
        'tariff' => 'tag',
        'devices' => 'router',
        'traffic' => 'chart',
        'traffic.filter' => 'chart',
        'payment' => 'receipt',
        'payment.filter' => 'receipt',
        'services' => 'gift',
        'topup' => 'wallet',
        'admin.tariffs' => 'tag',
    ];

    $allowed = ['home', 'tag', 'router', 'chart', 'receipt', 'gift', 'wallet', 'speed', 'shield', 'phone', 'id'];
    $resolved = is_string($icon) && $icon !== '' ? $icon : ($fromRoute[request()->route()?->getName()] ?? null);
    $resolved = in_array($resolved, $allowed, true) ? $resolved : null;
    $hasLead = isset($lead) && ! $lead->isEmpty();
@endphp

<div {{ $attributes->merge(['class' => 'u-page-head u-rise']) }}>
    <div @class(['u-page-head__identity', 'u-page-head__identity--plain' => $resolved === null])>
        @if ($resolved !== null)
            <span class="u-page-head__icon u-no-print" aria-hidden="true">
                <x-icon :name="$resolved" size="size-6"/>
            </span>
        @endif

        <h1 class="u-page-head__title">{{ $title }}</h1>

        @if ($hasLead)
            <p class="u-page-head__lead">{{ $lead }}</p>
        @endif
    </div>

    @isset($toolbar)
        <div class="u-page-head__toolbar">{{ $toolbar }}</div>
    @endisset
</div>
