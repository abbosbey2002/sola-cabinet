<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      data-close-label="{{ __('app.ui.close') }}"
      data-error-label="{{ __('app.ui.error') }}"
      data-timeout-label="{{ __('app.ui.timeout') }}"
      data-retry-label="{{ __('app.ui.retry') }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f1f6ed">

    <title>@yield('title'){{ config('app.name') }}</title>

    @include('partials.document-icons')

    <x-view-boot/>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>

<body class="u-auth-bg min-h-dvh">

<div class="u-progress u-no-print" data-progress hidden aria-hidden="true"></div>

@include('partials.offline-banner')

<a href="#content"
   class="sr-only z-[80] focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:rounded-full focus:border-2 focus:border-action focus:bg-surface focus:px-5 focus:py-3 focus:text-base focus:font-semibold focus:text-ink">
    @lang('app.ui.skip')
</a>

<div class="u-auth-shell grid min-h-dvh w-full lg:grid-cols-2">

    {{-- Brand column — half the screen on desktop, hidden on phones. --}}
    <aside class="u-auth-aside u-no-print hidden flex-col justify-between p-10 lg:flex xl:p-14"
           aria-hidden="true">
        <div>
            <x-logo :height="'h-11'"/>
            <p class="u-display mt-8 max-w-[20rem] text-[clamp(1.5rem,2.4vw,2rem)] leading-snug text-ink">
                @lang('app.auth.tagline')
            </p>
            <p class="mt-3 max-w-[22rem] text-base text-muted">@lang('app.auth.tagline_hint')</p>
        </div>

        <ul class="mt-10 max-w-[22rem] space-y-3">
            <li class="u-auth-trust">
                <span class="grid size-9 shrink-0 place-items-center rounded-lg"
                      style="background: var(--c-action-soft); color: var(--c-action)">
                    <x-icon name="phone" size="size-4"/>
                </span>
                <span>@lang('app.auth.trust_sms')</span>
            </li>
            <li class="u-auth-trust">
                <span class="grid size-9 shrink-0 place-items-center rounded-lg"
                      style="background: var(--c-action-soft); color: var(--c-action)">
                    <x-icon name="shield" size="size-4"/>
                </span>
                <span>@lang('app.auth.trust_secure')</span>
            </li>
            @if (config('sola.call_center'))
                <li class="u-auth-trust">
                    <span class="grid size-9 shrink-0 place-items-center rounded-lg"
                          style="background: var(--c-action-soft); color: var(--c-action)">
                        <x-icon name="phone" size="size-4"/>
                    </span>
                    <span>
                        @lang('app.auth.trust_support')
                        <a href="tel:{{ config('sola.call_center') }}"
                           class="mt-0.5 block font-semibold no-underline" style="color: var(--c-action)">
                            {{ config('sola.call_center') }}
                        </a>
                    </span>
                </li>
            @endif
        </ul>
    </aside>

    {{-- Form column — toolbar pinned top, card centred in the remaining height. --}}
    <div class="u-auth-main relative flex min-h-dvh flex-col px-4 py-6 sm:px-8 sm:py-8 lg:px-12 lg:py-10">

        <div class="u-auth-orbs" aria-hidden="true">
            <span class="u-auth-orb u-auth-orb-a"></span>
            <span class="u-auth-orb u-auth-orb-b"></span>
        </div>

        <div class="u-rise relative z-20 flex shrink-0 flex-wrap items-center justify-between gap-3">
            <x-logo :height="'h-10'" class="lg:hidden"/>
            <div class="ml-auto flex items-center gap-2">
                {{-- Language first so view-settings is the rightmost control:
                     its panel is right-anchored and must not spill past the
                     left edge of a phone viewport. --}}
                <x-lang-switch/>
                <x-view-settings/>
            </div>
        </div>

        <main id="content"
              class="mx-auto flex w-full max-w-[26rem] flex-1 flex-col justify-center py-6 lg:max-w-[28rem] lg:py-10">
            @yield('content')
        </main>
    </div>
</div>

@if (isset($errors) && $errors->any())
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
