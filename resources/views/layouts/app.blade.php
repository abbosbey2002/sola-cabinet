<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      data-close-label="{{ __('app.ui.close') }}"
      data-error-label="{{ __('app.ui.error') }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    {{-- Light --c-bg. prefs.js repaints this when the theme changes, so the
         phone's own browser chrome follows the page instead of leaving a seam
         above it. --}}
    <meta name="theme-color" content="#f1f6ed">
    {{-- ajax.js reads this; without it the period forms answer 419. --}}
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title'){{ config('app.name') }}</title>

    <link rel="icon" href="/img/favicon.png" type="image/png">

    <x-view-boot/>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>

<body class="min-h-dvh">

<a href="#content"
   class="sr-only z-[80] focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:rounded-full focus:border-2 focus:border-action focus:bg-surface focus:px-5 focus:py-3 focus:text-base focus:font-semibold focus:text-ink">
    @lang('app.ui.skip')
</a>

<div class="flex min-h-dvh flex-col">

    @include('partials.topbar')

    <main id="content" class="mx-auto w-full max-w-[1240px] flex-1 px-4 pb-16 pt-7 sm:px-6">
        @hasSection('heading')
            {{-- items-end, so the period form's buttons sit on the heading's
                 baseline; wrap, because the Russian heading plus the two date
                 fields do not share a line below ~900px. --}}
            <div class="u-rise mb-5 flex flex-wrap items-end justify-between gap-5">
                <h1 class="u-display text-3xl text-ink">@yield('heading')</h1>

                @hasSection('toolbar')
                    <div class="flex flex-wrap items-end gap-3">@yield('toolbar')</div>
                @endif
            </div>
        @endif

        @yield('content')
    </main>

    <footer class="border-t-2 border-line px-4 py-8 sm:px-6">
        <p class="mx-auto max-w-[1240px] text-center text-sm text-muted">
            {!! trans('app.footer.copy', ['year' => date('Y')]) !!}
        </p>
    </footer>
</div>

<x-modal name="confirm" :label="__('app.ui.confirm')">
    <div class="text-center">
        <div class="mx-auto grid size-12 place-items-center rounded-xl"
             style="background: var(--c-warn-soft); color: var(--c-warn)">
            <x-icon name="alert" size="size-6"/>
        </div>

        <p data-confirm-question class="mt-4 text-base font-semibold leading-snug text-ink"></p>
    </div>

    <div class="mt-6 flex flex-wrap gap-3">
        <button type="button" data-modal-close class="u-btn-ghost flex-1">@lang('app.no')</button>
        <button type="button" data-confirm-accept class="u-btn-primary flex-1">@lang('app.yes')</button>
    </div>
</x-modal>

@if ($errors->any())
    <template data-toast data-tone="error">{{ $errors->first() }}</template>
@endif

@if (session()->has('danger'))
    <template data-toast data-tone="error">{{ session()->pull('danger') }}</template>
@endif

@if (session()->has('info'))
    <template data-toast data-tone="info">{{ session()->pull('info') }}</template>
@endif

@stack('js')

{{-- Support chat, when the provider has been configured (spec §10). --}}
@if (config('sola.chat.script'))
    <script src="{{ config('sola.chat.script') }}" async></script>
@endif
</body>
</html>
