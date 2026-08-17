@extends('layouts.guest')

@section('content')
    <div class="u-card u-rise p-6 sm:p-7" style="--i:1">
        <h1 class="u-display text-2xl text-ink">@lang('app.auth.title')</h1>
        <p class="mt-3 text-base text-muted">@lang('app.auth.intro')</p>

        <form action="{{ route('login') }}" method="post" class="mt-7 space-y-5" novalidate>
            @csrf

            <div>
                <label for="login" class="u-label mb-2 block">@lang('app.auth.phone')</label>

                <input type="tel" id="login" name="login" required
                       value="{{ old('login', '+998') }}"
                       inputmode="tel" autocomplete="tel" autofocus
                       placeholder="+998 90 123 45 67"
                       aria-describedby="login-hint"
                       @error('login') aria-invalid="true" aria-errormessage="login-error" @enderror
                       class="u-field text-lg @error('login') !border-[var(--c-danger)] @enderror">

                {{-- The error replaces the hint rather than stacking under it:
                     two lines of small print below a field the subscriber has
                     just got wrong is one line too many. --}}
                @error('login')
                    <p id="login-error" class="mt-2 flex items-start gap-2 text-sm" style="color: var(--c-danger)">
                        <x-icon name="alert" size="size-4" class="mt-1"/>{{ $message }}
                    </p>
                @else
                    <p id="login-hint" class="mt-2 text-sm text-muted">@lang('app.auth.phone_hint')</p>
                @enderror
            </div>

            <button type="submit" class="u-btn-primary w-full text-lg">@lang('app.auth.send_code')</button>
        </form>
    </div>

    @if (config('sola.call_center'))
        <p class="u-rise mt-6 text-center text-base text-muted" style="--i:2">
            @lang('app.menu.call')
            <a href="tel:{{ config('sola.call_center') }}"
               class="font-semibold no-underline" style="color: var(--c-action)">{{ config('sola.call_center') }}</a>
        </p>
    @endif
@endsection
