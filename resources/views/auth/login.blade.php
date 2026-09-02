@extends('layouts.guest')
@section('title', trans('app.auth.title').' - ')

@section('content')
    <section class="u-card u-card-hero u-rise w-full overflow-hidden p-6 sm:p-8" style="--i:1" aria-labelledby="auth-login-title">
        <span class="u-badge-network">@lang('app.auth.step_phone')</span>

        <div class="u-page-head__identity mt-3">
            <span class="u-page-head__icon" aria-hidden="true">
                <x-icon name="phone" size="size-6"/>
            </span>
            <h1 id="auth-login-title" class="u-page-head__title text-2xl">@lang('app.auth.title')</h1>
            <p class="u-page-head__lead">@lang('app.auth.intro')</p>
        </div>

        <form action="{{ route('login') }}" method="post" class="mt-8 space-y-5" novalidate>
            @csrf

            <div>
                <label for="login" class="u-label mb-2 block">@lang('app.auth.phone')</label>

                <input type="tel" id="login" name="login" required data-phone-mask
                       value="{{ old('login', '+998') }}"
                       inputmode="tel" autocomplete="tel" autofocus
                       placeholder="+998 90 123 45 67"
                       aria-describedby="login-hint"
                       @error('login') aria-invalid="true" aria-errormessage="login-error" @enderror
                       class="u-field text-lg @error('login') !border-[var(--c-danger)] @enderror">

                @error('login')
                    <p id="login-error" class="mt-2 flex items-start gap-2 text-sm" style="color: var(--c-danger)">
                        <x-icon name="alert" size="size-4" class="mt-0.5 shrink-0"/>{{ $message }}
                    </p>
                @else
                    <p id="login-hint" class="mt-2 text-sm text-muted">@lang('app.auth.phone_hint')</p>
                @enderror
            </div>

            <button type="submit" class="u-btn-primary w-full text-lg">
                <x-icon name="phone" size="size-5"/>@lang('app.auth.send_code')
            </button>
        </form>
    </section>

    @if (config('sola.call_center'))
        <p class="u-rise mt-5 text-center text-sm text-muted lg:hidden" style="--i:2">
            @lang('app.menu.call')
            <a href="tel:{{ config('sola.call_center') }}"
               class="font-semibold no-underline" style="color: var(--c-action)">{{ config('sola.call_center') }}</a>
        </p>
    @endif
@endsection
