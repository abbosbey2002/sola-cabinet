@props([
    'icon' => null,
])

{{--
    Page identity strip. The visible H1 title was dropped: the top nav already
    names the section. The title stays in an sr-only heading for the document
    outline. Icon + lead remain when the page has a lead; the toolbar (period
    filters) still sits on the right.
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
    $hasToolbar = isset($toolbar);
@endphp

<h1 class="sr-only">{{ $title }}</h1>

@if ($hasLead || $hasToolbar)
    <div {{ $attributes->merge(['class' => 'u-page-head u-rise']) }}>
        @if ($hasLead)
            <div @class(['u-page-head__identity', 'u-page-head__identity--plain' => $resolved === null])>
                @if ($resolved !== null)
                    <span class="u-page-head__icon u-no-print" aria-hidden="true">
                        <x-icon :name="$resolved" size="size-6"/>
                    </span>
                @endif

                <p class="u-page-head__lead">{{ $lead }}</p>
            </div>
        @endif

        @if ($hasToolbar)
            <div class="u-page-head__toolbar">{{ $toolbar }}</div>
        @endif
    </div>
@endif
