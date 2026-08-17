<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      data-close-label="{{ __('app.ui.close') }}"
      data-error-label="{{ __('app.ui.error') }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f1f6ed">

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

<div class="mx-auto flex min-h-dvh w-full max-w-[28rem] flex-col justify-center px-4 py-10">

    {{-- relative z-20: u-rise animates opacity and transform, so this row opens
         its own stacking context and the view panel's z-50 cannot escape it. If
         the context had no z-index of its own, the card below — also u-rise, so
         also its own context — would paint over the open panel. --}}
    <div class="u-rise relative z-20 mb-6 flex flex-wrap items-center justify-between gap-3">
        <x-logo :height="'h-10'"/>

        {{-- The language switch stays on the guest screens even though the
             prototype, being Uzbek-only, has no need of it: a Russian-speaking
             subscriber has to be able to change it BEFORE reading the login
             form, not after. --}}
        <div class="flex items-center gap-2">
            <x-view-settings/>
            <x-lang-switch/>
        </div>
    </div>

    <main id="content">
        @yield('content')
    </main>
</div>

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
</body>
</html>
