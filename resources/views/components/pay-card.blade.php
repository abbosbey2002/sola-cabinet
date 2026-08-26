@props(['contract', 'tone' => 'action'])

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

<div {{ $attributes->merge(['class' => 'u-card u-rise flex flex-wrap items-center gap-4']) }}
     style="background: {{ $tones['bg'] }}">
    <span class="grid size-12 shrink-0 place-items-center rounded-xl bg-surface" style="color: {{ $tones['fg'] }}">
        <x-icon name="wallet" size="size-6"/>
    </span>

    <div class="min-w-0 flex-1">
        <p class="u-label">@lang('app.dash.contract')</p>
        <p class="u-figure mt-0.5 text-2xl text-ink">{{ $contract }}</p>
        <p class="mt-1 text-sm text-muted">@lang('app.dash.pay_hint')</p>
    </div>

    <button type="button" data-copy="{{ $contract }}" data-copy-done="@lang('app.ui.copied')"
            class="u-btn-ghost u-btn-sm u-no-print shrink-0">
        <x-icon name="copy" size="size-4"/><span data-copy-text role="status">@lang('app.ui.copy')</span>
    </button>
</div>
