@props(['profile'])

{{-- Always in the top row, including on a phone: settings and language sit
     after this so the amount stays visible when those controls compete for
     width. The full balance card lives on home; this is the same figure. --}}
@php
    $som = $profile->balance();
    $amount = str_replace('-', '−', number_format($som, 0, '', ' '));
@endphp

<a href="{{ route('cabinet') }}"
   data-nav-balance
   {{ $attributes->merge(['class' => 'flex min-h-[3rem] shrink-0 items-center gap-1.5 rounded-full px-2.5 py-2 no-underline transition-colors hover:bg-surface-2 sm:gap-2 sm:px-4']) }}
   aria-label="{{ __('app.header.balance') }}: {{ $amount }} {{ __('app.ye') }}">
    <x-icon name="wallet" size="size-5" style="color: var(--c-action)"/>
    <span class="u-figure text-base font-semibold sm:text-lg"
          @if ($som < 0) style="color: var(--c-danger)" @endif>{{ $amount }}</span>
    <span class="text-sm font-semibold text-muted">@lang('app.ye')</span>
</a>
