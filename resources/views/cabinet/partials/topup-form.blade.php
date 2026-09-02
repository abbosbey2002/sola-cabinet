{{-- Shared top-up form: /topup page and the quick-top-up modal (Figma: hisobni-toldirish-modal). --}}

@php
    $compact = $compact ?? false;
    $showHeadline = ($showHeadline ?? true) && ! $compact;
@endphp

<div class="u-topup">
    @if ($showHeadline)
        <header class="u-topup-hero">
            <p class="text-sm font-semibold text-ink">@lang('app.topup.headline')</p>
        </header>
    @endif

    {{-- Checkout is iWon; cards are chosen on their page. One quiet row, no method picker. --}}
    <div @class(['u-topup-iwon', 'mt-3' => $showHeadline])>
        <span class="u-topup-iwon-logo-wrap" aria-hidden="true">
            <img src="{{ asset('img/iwon-logo.svg') }}" alt="" class="u-topup-iwon-logo" width="91" height="32">
        </span>
        <div class="min-w-0">
            <p class="u-topup-iwon-title">@lang('app.topup.iwon_via')</p>
        </div>
    </div>

    <form action="{{ route('topup.store') }}" method="post" target="_blank" rel="noopener" data-topup-form class="u-topup-form">
        @csrf

        <div>
            <div class="u-topup-amount-head">
                <label for="amount" class="u-topup-amount-label">@lang('app.topup.amount_label')</label>
                @unless ($errors->has('amount'))
                    <span id="amount-hint" class="u-topup-amount-hint">@lang('app.topup.amount_hint')</span>
                @endunless
            </div>

            <div class="u-topup-amount relative">
                <input type="text" id="amount" name="amount" required
                       inputmode="numeric" autocomplete="off" data-amount-mask
                       value="{{ old('amount') }}"
                       placeholder="100 000"
                       aria-describedby="amount-hint"
                       @error('amount') aria-invalid="true" aria-errormessage="amount-error" @enderror
                       class="u-topup-amount-field @error('amount') u-topup-amount-field-error @enderror">
                <span class="u-topup-currency">@lang('app.ye')</span>
            </div>

            @error('amount')
                <p id="amount-error" class="mt-1.5 flex items-start gap-2 text-sm" style="color: var(--c-danger)">
                    <x-icon name="alert" size="size-4" class="mt-0.5 shrink-0"/>{{ $message }}
                </p>
            @enderror

            <div class="u-topup-presets" role="group" aria-label="{{ __('app.topup.amount_presets') }}">
                @foreach ([100000, 250000, 500000, 1000000] as $preset)
                    @php
                        $formatted = number_format($preset, 0, '', ' ');
                        $isPopular = $preset === 500000;
                    @endphp
                    <button type="button" data-amount-preset="{{ $preset }}" aria-pressed="false"
                        @if ($isPopular) aria-label="{{ __('app.topup.preset_popular').': '.$formatted.' '.__('app.ye') }}" @endif
                        @class([
                            'u-choice u-topup-preset',
                            'u-topup-preset-popular' => $isPopular,
                        ])>
                        {{ $formatted }}
                    </button>
                @endforeach
            </div>
        </div>

        <button type="submit" class="u-btn-primary u-topup-submit w-full">
            @lang('app.topup.submit')
        </button>

        <p class="u-topup-footnote">
            @lang('app.topup.redirect_note')
        </p>
    </form>

    <div data-topup-opened hidden role="status" class="u-topup-opened">
        <span class="u-topup-opened-icon">
            <x-icon name="check" size="size-4"/>
        </span>
        <span class="min-w-0 flex-1">
            <span class="block font-semibold text-ink">@lang('app.topup.opened_in_new_tab')</span>
            <a href="{{ route('cabinet') }}" class="u-btn-ghost u-btn-sm mt-2 inline-flex">@lang('app.nav.home')</a>
        </span>
    </div>
</div>
