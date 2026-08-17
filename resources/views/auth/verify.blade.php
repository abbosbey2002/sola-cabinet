@extends('layouts.guest')

@section('content')
    <div class="u-card u-rise p-6 sm:p-7" style="--i:1">
        <span class="mb-5 grid size-12 place-items-center rounded-xl"
              style="background: var(--c-action-soft); color: var(--c-action)">
            <x-icon name="phone" size="size-6"/>
        </span>

        <h1 class="u-display text-2xl text-ink">@lang('app.auth.code_title')</h1>

        <p class="mt-3 text-base text-muted">
            @lang('app.auth.send_phone', ['phone' => substr((string) session()->get('phone'), 7, 12)])
        </p>

        <form action="{{ route('verify') }}" method="post" class="mt-7 space-y-5" novalidate>
            @csrf

            <div>
                <label for="code" class="u-label mb-2 block">@lang('app.auth.code_label')</label>

                <input type="text" id="code" name="code" required
                       maxlength="4" inputmode="numeric" pattern="[0-9]*"
                       autocomplete="one-time-code" autofocus
                       placeholder="0000"
                       @error('code') aria-invalid="true" aria-errormessage="code-error" @enderror
                       class="u-field text-center text-3xl font-bold tracking-[0.4em] @error('code') !border-[var(--c-danger)] @enderror">

                @error('code')
                    <p id="code-error" class="mt-2 flex items-start gap-2 text-sm" style="color: var(--c-danger)">
                        <x-icon name="alert" size="size-4" class="mt-1"/>{{ $message }}
                    </p>
                @enderror
            </div>

            <button type="submit" class="u-btn-primary w-full text-lg">@lang('app.auth.login')</button>
        </form>

        <a href="{{ route('login') }}"
           class="mt-4 flex min-h-[3rem] w-full items-center justify-center rounded-full text-base font-semibold no-underline transition-colors hover:bg-surface-2"
           style="color: var(--c-action)">
            @lang('app.auth.change_number')
        </a>
    </div>
@endsection
