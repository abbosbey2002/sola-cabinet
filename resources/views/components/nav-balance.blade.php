@props(['profile'])

{{-- Always in the top row, including on a phone: settings and language sit
     after this so the amount stays visible when those controls compete for
     width. On phones the currency word is dropped (aria-label keeps it) and
     a long figure truncates so the row stays one line. The full balance card
     lives on home; this is the same figure. --}}
@php
    $som = $profile->balance();
    $amount = str_replace('-', '−', number_format($som, 0, '', ' '));
@endphp

<a href="{{ route('cabinet') }}"
   data-nav-balance
   {{ $attributes->merge(['class' => 'flex min-h-[3rem] min-w-0 items-center gap-1 rounded-full px-2 py-2 no-underline transition-colors hover:bg-surface-2 sm:gap-2 sm:px-4']) }}
   aria-label="{{ __('app.header.balance') }}: {{ $amount }} {{ __('app.ye') }}">
    <x-icon name="wallet" size="size-5" class="shrink-0" style="color: var(--c-action)"/>
    <span class="u-figure max-w-[6.5rem] truncate text-sm font-semibold sm:max-w-none sm:text-base lg:text-lg"
          @if ($som < 0) style="color: var(--c-danger)" @endif>{{ $amount }}</span>
    <span class="hidden shrink-0 text-sm font-semibold text-muted sm:inline">@lang('app.ye')</span>
</a>
