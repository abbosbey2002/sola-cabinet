@extends('layouts.guest')
@section('title', trans('app.auth.code_title').' - ')

@section('content')
    <section class="u-card u-card-hero u-rise w-full overflow-hidden p-6 sm:p-8" style="--i:1" aria-labelledby="auth-verify-title">
        <span class="u-badge-network">@lang('app.auth.step_code')</span>

        <div class="u-page-head__identity mt-3">
            <span class="u-page-head__icon" aria-hidden="true">
                <x-icon name="shield" size="size-6"/>
            </span>
            <h1 id="auth-verify-title" class="u-page-head__title text-2xl">@lang('app.auth.code_title')</h1>
            <p class="u-page-head__lead">
                @lang('app.auth.send_phone', ['phone' => substr((string) session()->get('phone'), 7, 12)])
            </p>
        </div>

        <form action="{{ route('verify') }}" method="post" class="mt-8 space-y-5" novalidate>
            @csrf

            <div>
                <label for="code" class="u-label mb-2 block">@lang('app.auth.code_label')</label>

                <input type="text" id="code" name="code" required data-otp
                       maxlength="4" inputmode="numeric" pattern="[0-9]*"
                       autocomplete="one-time-code" autofocus
                       placeholder="0000"
                       @error('code') aria-invalid="true" aria-errormessage="code-error" @enderror
                       class="u-field text-center text-3xl font-bold tracking-[0.4em] @error('code') !border-[var(--c-danger)] @enderror">

                @error('code')
                    <p id="code-error" class="mt-2 flex items-start gap-2 text-sm" style="color: var(--c-danger)">
                        <x-icon name="alert" size="size-4" class="mt-0.5 shrink-0"/>{{ $message }}
                    </p>
                @enderror
            </div>

            <button type="submit" class="u-btn-primary w-full text-lg">
                <x-icon name="check" size="size-5"/>@lang('app.auth.login')
            </button>
        </form>

        <a href="{{ route('login') }}"
           class="mt-4 flex min-h-[3rem] w-full items-center justify-center gap-2 rounded-full text-base font-semibold no-underline transition-colors hover:bg-surface-2"
           style="color: var(--c-action)">
            <x-icon name="chevron-left" size="size-4"/>@lang('app.auth.change_number')
        </a>
    </section>
@endsection
