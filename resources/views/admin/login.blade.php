@extends('layouts.admin-guest')
@section('title', trans('app.admin.login_title').' - ')

@section('content')
    <div class="u-card u-rise p-6 sm:p-7" style="--i:1">
        <h1 class="u-display text-2xl text-ink">@lang('app.admin.login_title')</h1>

        <form action="{{ route('admin.login') }}" method="post" class="mt-7 space-y-5" novalidate>
            @csrf

            <div>
                <label for="username" class="u-label mb-2 block">@lang('app.admin.username')</label>

                <input type="text" id="username" name="username" required
                       value="{{ old('username') }}"
                       autocomplete="username" autofocus
                       aria-describedby="username-error"
                       @error('username') aria-invalid="true" aria-errormessage="username-error" @enderror
                       class="u-field text-lg @error('username') !border-[var(--c-danger)] @enderror">
            </div>

            <div>
                <label for="password" class="u-label mb-2 block">@lang('app.admin.password')</label>

                <input type="password" id="password" name="password" required
                       autocomplete="current-password"
                       class="u-field text-lg @error('username') !border-[var(--c-danger)] @enderror">
            </div>

            @error('username')
                <p id="username-error" class="flex items-start gap-2 text-sm" style="color: var(--c-danger)">
                    <x-icon name="alert" size="size-4" class="mt-1"/>{{ $message }}
                </p>
            @enderror

            <button type="submit" class="u-btn-primary w-full text-lg">@lang('app.admin.sign_in')</button>
        </form>
    </div>
@endsection
