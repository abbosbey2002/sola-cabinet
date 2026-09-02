{{--
    Modal shell — bottom sheet on phones, centered card on desktop.

    Props:
      title / subtitle — visible header copy
      label — accessible name when there is no visible title (confirm dialogs)
      header — false hides the header bar; close floats top-right (confirm)
      size — sm | md | lg
--}}
@props([
    'name',
    'title' => null,
    'subtitle' => null,
    'label' => null,
    'header' => true,
    'size' => 'md',
    'variant' => null,
])

@php
    $panelWidth = match ($size) {
        'sm' => 'max-w-sm',
        'lg' => 'max-w-lg',
        default => 'max-w-md',
    };

    $isTopup = $variant === 'topup';
    $hasHeaderBar = $header && ($title !== null || $subtitle !== null);
    $titleId = 'modal-'.$name.'-title';
    $ariaLabel = $label ?? $title;
@endphp

<div data-modal="{{ $name }}" hidden
    class="u-modal u-no-print"
    role="dialog" aria-modal="true"
    @if ($hasHeaderBar) aria-labelledby="{{ $titleId }}" @elseif ($ariaLabel) aria-label="{{ $ariaLabel }}" @endif>

    <div data-modal-overlay class="u-modal-overlay" aria-hidden="true"></div>

    <div data-modal-panel @class([
        'u-modal-panel',
        $panelWidth,
        'u-modal-panel-topup' => $isTopup,
    ])>
        @unless ($isTopup)
            <div class="u-modal-handle" aria-hidden="true"></div>
        @endunless

        @if ($hasHeaderBar)
            <header @class(['u-modal-header', 'u-modal-header-topup' => $isTopup])>
                <div class="min-w-0 flex-1 pr-2">
                    @if ($title !== null)
                        <h2 id="{{ $titleId }}" @class([
                            'u-display leading-snug text-ink',
                            'text-xl font-bold' => $isTopup,
                            'text-lg' => ! $isTopup,
                        ])>{{ $title }}</h2>
                    @elseif ($label !== null)
                        <h2 id="{{ $titleId }}" class="u-display text-lg leading-snug text-ink">{{ $label }}</h2>
                    @endif
                    @if ($subtitle !== null)
                        <p @class([
                            'leading-snug text-muted',
                            'mt-1 text-sm' => ! $isTopup,
                            'mt-0.5 text-[0.8125rem] leading-relaxed' => $isTopup,
                        ])>{{ $subtitle }}</p>
                    @endif
                </div>
                <button type="button" data-modal-close @class([
                    'u-modal-close',
                    'u-modal-close-topup' => $isTopup,
                ]) aria-label="{{ __('app.ui.close') }}">
                    <x-icon name="close" size="size-5"/>
                </button>
            </header>
        @else
            <button type="button" data-modal-close
                class="u-modal-close u-modal-close-floating"
                aria-label="{{ __('app.ui.close') }}">
                <x-icon name="close" size="size-5"/>
            </button>
        @endif

        <div @class([
            'u-modal-body',
            'u-modal-body-confirm' => ! $hasHeaderBar,
            'u-modal-body-topup' => $isTopup,
        ])>
            {{ $slot }}
        </div>
    </div>
</div>
