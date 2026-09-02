@props(['contract' => null, 'tone' => 'action'])

{{--
    The one thing on a balance-trouble screen that is actually actionable: the
    contract number a subscriber types into Payme, Click or Uzum. It used to be
    inert text inside a sentence — a subscriber worried about their balance had
    to read to the end of a paragraph to find it, then retype it by hand. This
    puts the number in front of them at the size it needs to be read at a
    glance, with a one-tap copy so a phone number is never retyped by memory.
--}}

@php
    $toneMap = [
        'danger' => ['bg' => 'var(--c-danger-soft)', 'fg' => 'var(--c-danger)'],
        'warn' => ['bg' => 'var(--c-warn-soft)', 'fg' => 'var(--c-warn)'],
        'action' => ['bg' => 'var(--c-action-soft)', 'fg' => 'var(--c-action)'],
    ];
    $tones = $toneMap[$tone] ?? $toneMap['action'];
@endphp

<div {{ $attributes->merge(['class' => 'u-card u-rise']) }}
     style="background: {{ $tones['bg'] }}">
    {{-- Billing does not send a contract number for every account (none of
         AbonentProfile::CANDIDATE_CONTRACT matched for account 1336708,
         observed live 2026-08-28) — this half of the card is the one thing
         that actually needs it, so it is skipped rather than printing a
         blank figure. The iWon button below needs no contract number at
         all and must not disappear along with it. --}}
    @if (filled($contract))
        <div class="flex flex-wrap items-center gap-4">
            <span class="grid size-12 shrink-0 place-items-center rounded-xl bg-surface" style="color: {{ $tones['fg'] }}">
                <x-icon name="wallet" size="size-6"/>
            </span>

            <div class="min-w-0 flex-1">
                <p class="u-label">@lang('app.dash.contract')</p>
                <p class="u-figure mt-0.5 text-2xl text-ink">{{ $contract }}</p>
                <p class="mt-1 text-sm text-muted">@lang('app.dash.pay_hint')</p>
            </div>

            <button type="button" data-copy="{{ $contract }}" data-copy-done="@lang('app.ui.copied')"
                    class="u-no-print grid size-11 shrink-0 place-items-center rounded-full text-muted transition-colors hover:bg-surface-2 hover:text-ink">
                <span data-copy-icon-default><x-icon name="copy" size="size-4"/></span>
                <span data-copy-icon-done hidden style="color: var(--c-action)"><x-icon name="check" size="size-4"/></span>
                <span data-copy-text role="status" class="sr-only">@lang('app.ui.copy')</span>
            </button>
        </div>
    @elseif (config('iwon.active'))
        {{-- No contract number and nothing to copy, but the card still
             needs to read as one deliberate piece — an icon and a line of
             text, the same weight as the contract half above, rather than
             a bare button floating in a tinted box. --}}
        <div class="flex flex-wrap items-center gap-4">
            <span class="grid size-12 shrink-0 place-items-center rounded-xl bg-surface" style="color: {{ $tones['fg'] }}">
                <x-icon name="wallet" size="size-6"/>
            </span>

            <div class="min-w-0 flex-1">
                <p class="u-label">@lang('app.topup.pay_card_title')</p>
                <p class="mt-0.5 text-sm text-muted">@lang('app.topup.pay_card_hint')</p>
            </div>
        </div>
    @endif

    {{-- The manual copy-the-contract-number flow above stays for Payme/Click/
         Uzum, since only iWon is actually integrated — closed at
         config('iwon.active') the same way TopUpController itself is, so the
         button never appears offering a flow the route would 404 on.

         data-modal-open opens the quick-top-up modal below without leaving
         this page — href stays a real link to route('topup') underneath it,
         so the button still works exactly as before with JS off (modal.js
         never attaches, the click just follows the link). --}}
    @if (config('iwon.active'))
        <a href="{{ route('topup') }}" data-modal-open="topup-modal" class="u-btn-primary u-no-print mt-4 flex w-full">
            <x-icon name="wallet" size="size-5"/>@lang('app.topup.pay_card_button')
        </a>

        {{-- Same form partial the full /topup page renders — one source of
             markup, see partials/topup-form.blade.php. A validation failure
             always redirects to the real page (TopUpRequest::getRedirectUrl)
             rather than back to wherever this modal was opened from, so an
             error is never silently lost inside a page that can't show it. --}}
        <x-modal name="topup-modal" variant="topup" :title="__('app.topup.title')" :subtitle="__('app.topup.subline')">
            @include('cabinet.partials.topup-form', ['compact' => true])
        </x-modal>
    @endif
</div>
