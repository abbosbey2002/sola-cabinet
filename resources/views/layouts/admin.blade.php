<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      data-theme="light"
      data-app="admin"
      data-close-label="{{ __('app.ui.close') }}"
      data-error-label="{{ __('app.ui.error') }}"
      data-timeout-label="{{ __('app.ui.timeout') }}"
      data-retry-label="{{ __('app.ui.retry') }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    {{-- Always white — the admin panel doesn't follow the subscriber's theme
         choice or the system preference, see the data-theme attribute above. --}}
    <meta name="theme-color" content="#ffffff">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">

    <title>@yield('title'){{ config('app.name') }} Admin</title>

    @include('partials.document-icons')

    {{-- No <x-view-boot/>: that component restores a STORED theme/text-size
         choice, and this page never lets one be made. --}}

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>

<body class="min-h-dvh">

<div class="u-progress u-no-print" data-progress hidden aria-hidden="true"></div>

@include('partials.offline-banner')

<a href="#content"
   class="sr-only z-[80] focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:rounded-full focus:border-2 focus:border-action focus:bg-surface focus:px-5 focus:py-3 focus:text-base focus:font-semibold focus:text-ink">
    @lang('app.ui.skip')
</a>

@php
    // One entry today (Tariffs) — a list rather than one hard-coded link so
    // the next admin screen slots in without restructuring the shell.
    $navItems = [
        ['route' => 'admin.tariffs', 'icon' => 'tag', 'label' => trans('app.admin.tariffs_title')],
    ];
@endphp

<div class="flex min-h-dvh">
    {{-- Desktop: a fixed sidebar, the shape an admin panel is expected to
         have — distinct from the subscriber cabinet's mobile-first top nav,
         on purpose (see the design note in resources/css/app.css). --}}
    <aside class="sticky top-0 hidden h-dvh w-64 shrink-0 flex-col border-r-2 border-line bg-surface px-4 py-6 sm:flex">
        <div class="flex items-center gap-2.5 px-1">
            <x-logo height="h-8"/>
            <span class="u-label rounded-full bg-surface-2 px-2.5 py-1 text-ink">Admin</span>
        </div>

        <nav class="mt-8 flex flex-1 flex-col gap-1" aria-label="@lang('app.admin.tariffs_title')">
            @foreach ($navItems as $item)
                <a href="{{ route($item['route']) }}" class="u-sidebar-link"
                   @if (request()->routeIs($item['route'])) aria-current="page" @endif>
                    <x-icon :name="$item['icon']" size="size-5"/>{{ $item['label'] }}
                </a>
            @endforeach
        </nav>

        <form method="post" action="{{ route('admin.logout') }}">
            @csrf
            <button type="submit" class="u-sidebar-link w-full">
                <x-icon name="logout" size="size-5"/>@lang('app.admin.logout')
            </button>
        </form>
    </aside>

    <div class="flex min-w-0 flex-1 flex-col">
        {{-- Mobile: the sidebar collapses to this bar — one nav item does not
             earn a drawer/hamburger of its own. --}}
        <header class="u-no-print flex min-h-[4rem] items-center justify-between gap-3 border-b-2 border-line px-4 sm:hidden">
            <div class="flex items-center gap-2.5">
                <x-logo height="h-8"/>
                <span class="u-label rounded-full bg-surface-2 px-2.5 py-1 text-ink">Admin</span>
            </div>

            <form method="post" action="{{ route('admin.logout') }}">
                @csrf
                <button type="submit" class="u-btn-ghost u-btn-sm" aria-label="@lang('app.admin.logout')">
                    <x-icon name="logout" size="size-4"/>
                </button>
            </form>
        </header>

        <main id="content" class="w-full flex-1 px-4 pb-16 pt-7 sm:px-8">
            @hasSection('heading')
                <x-page-heading :icon="trim($__env->yieldContent('heading-icon')) ?: null">
                    <x-slot:title>@yield('heading')</x-slot:title>
                </x-page-heading>
            @endif

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
