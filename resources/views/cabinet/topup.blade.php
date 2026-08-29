@extends('layouts.app')
@section('title', trans('app.topup.title').' - ')
@section('heading', trans('app.topup.title'))

@section('content')
    <section class="u-card u-card-hero u-rise" style="--i:1">
        {{-- The real iWon mark, not a generic wallet icon or the name in
             plain text — a subscriber about to enter card details trusts a
             recognised payment brand's own logo the way a plain-text label
             never earns. public/img/iwon-logo.svg is iWon's own asset,
             black wordmark only (no light-on-dark variant supplied), so it
             sits on a fixed white chip rather than the theme's own
             --c-surface — the same reasoning .u-logo applies to the SOLA
             mark, just with a literal white instead of a second asset,
             since iWon gave us only the one. --}}
        <div class="flex flex-wrap items-start gap-3">
            <span class="inline-flex shrink-0 items-center rounded-xl bg-white px-3 py-2">
                <img src="{{ asset('img/iwon-logo.svg') }}" alt="iWon" class="h-5 w-auto sm:h-6">
            </span>
            <p class="min-w-0 flex-1 pt-1 text-base text-muted">@lang('app.topup.intro')</p>
        </div>

        {{-- target="_blank": iWon's card form opens in its own tab rather
             than navigating the subscriber away from the cabinet mid-flow.
             This tab is left showing the form, so the submit handler in
             resources/js/modules/topup.js swaps it for the banner below,
             which no longer offers a way back to a visible form on this
             tab — only /topup.return or a fresh page load. rel="noopener":
             the new tab is an external origin (iWon) and must not keep a
             window.opener handle back into the cabinet, matching every
             other external-target link in this app (services.blade.php,
             topbar.blade.php). --}}
        <form action="{{ route('topup.store') }}" method="post" target="_blank" rel="noopener" data-topup-form class="mt-4 space-y-3">
            @csrf

            <div>
                {{-- Label and the minimum-amount hint share one row instead of
                     the hint sitting in its own line below the field — one
                     less row of height, and the hint still describes the
                     input via aria-describedby regardless of where it sits
                     visually. --}}
                <div class="mb-2 flex flex-wrap items-baseline justify-between gap-x-3">
                    <label for="amount" class="u-label">@lang('app.topup.amount_label')</label>
                    @unless ($errors->has('amount'))
                        <span id="amount-hint" class="text-xs text-muted">@lang('app.topup.amount_hint')</span>
                    @endunless
                </div>

                <div class="flex items-baseline gap-3">
                    {{-- type="text", not "number": a native number input
                         rejects the space thousands-separators
                         amount-mask.js inserts as the subscriber types.
                         TopUpRequest strips them back out before validating. --}}
                    <input type="text" id="amount" name="amount" required
                           inputmode="numeric" autocomplete="off" data-amount-mask
                           value="{{ old('amount') }}"
                           placeholder="10 000"
                           aria-describedby="amount-hint"
                           @error('amount') aria-invalid="true" aria-errormessage="amount-error" @enderror
                           class="u-field u-figure text-xl @error('amount') !border-[var(--c-danger)] @enderror">
                    <span class="text-lg font-semibold text-muted">@lang('app.ye')</span>
                </div>

                @error('amount')
                    <p id="amount-error" class="mt-2 flex items-start gap-2 text-sm" style="color: var(--c-danger)">
                        <x-icon name="alert" size="size-4" class="mt-1"/>{{ $message }}
                    </p>
                @enderror

                {{-- Quick amounts: a tap instead of typing, the fastest way
                     through the form when a round figure already covers what
                     the subscriber came here for. Pure client convenience —
                     resources/js/modules/amount-presets.js only sets the same
                     field amount-mask.js already owns, TopUpRequest validates
                     the submitted value exactly as if it had been typed. --}}
                <div class="mt-2 flex flex-wrap gap-2" role="group" aria-label="{{ __('app.topup.amount_presets') }}">
                    @foreach ([10000, 20000, 50000, 100000] as $preset)
                        <button type="button" data-amount-preset="{{ $preset }}" aria-pressed="false" class="u-choice">
                            {{ number_format($preset, 0, '', ' ') }}
                        </button>
                    @endforeach
                </div>
            </div>

            <button type="submit" class="u-btn-primary w-full">
                <x-icon name="wallet"/>@lang('app.topup.submit')
            </button>

            <p class="flex items-start gap-2 text-sm text-muted">
                <x-icon name="id" size="size-4" class="mt-0.5 shrink-0"/>
                @lang('app.topup.redirect_note')
            </p>
        </form>

        {{-- Hidden until the form above is submitted (topup.js). Without JS
             this never shows, but target="_blank" still opens iWon in a new
             tab on its own — the subscriber can still go to /topup/return
             by hand either way. role="status": this tab's content changes
             out from under the subscriber the instant they submit, and a
             screen reader needs to be told, the same way toast.js and every
             copy-button's data-copy-text span already are in this app. --}}
        <div data-topup-opened hidden role="status" class="mt-6 flex items-start gap-3.5 rounded-xl px-4 py-3.5 text-base text-ink" style="background: var(--c-action-soft)">
            <span class="grid size-9 shrink-0 place-items-center rounded-lg bg-surface" style="color: var(--c-action)">
                <x-icon name="check" size="size-4"/>
            </span>
            <span class="min-w-0 flex-1">
                <span class="block">@lang('app.topup.opened_in_new_tab')</span>
                <a href="{{ route('topup.return') }}" class="u-btn-ghost u-btn-sm mt-3 inline-flex">@lang('app.topup.check_status')</a>
            </span>
        </div>
    </section>
@endsection
