<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      data-theme="light"
      data-app="admin"
      data-close-label="{{ __('app.ui.close') }}"
      data-error-label="{{ __('app.ui.error') }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#ffffff">

    <title>@yield('title'){{ config('app.name') }} Admin</title>

    <link rel="icon" href="/img/favicon.png" type="image/png">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>

<body class="min-h-dvh">

<a href="#content"
   class="sr-only z-[80] focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:rounded-full focus:border-2 focus:border-action focus:bg-surface focus:px-5 focus:py-3 focus:text-base focus:font-semibold focus:text-ink">
    @lang('app.ui.skip')
</a>

<div class="mx-auto flex min-h-dvh w-full max-w-[26rem] flex-col justify-center px-4 py-10">
    <div class="u-rise relative z-20 mb-6 flex items-center gap-2.5">
        <x-logo height="h-10"/>
        <span class="u-label rounded-full bg-surface-2 px-2.5 py-1 text-ink">Admin</span>
    </div>

    <main id="content">
        @yield('content')
    </main>
</div>

@if ($errors->any())
    <template data-toast data-tone="error">{{ $errors->first() }}</template>
@endif

@stack('js')
</body>
</html>
