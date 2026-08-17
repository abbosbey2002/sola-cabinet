{{-- $title renders a visible heading; $label names the dialog for assistive
     tech without one, for dialogs whose content is its own headline. --}}
@props(['name', 'title' => null, 'label' => null, 'size' => 'max-w-md'])

<div data-modal="{{ $name }}" hidden
     class="u-no-print fixed inset-0 z-[60] flex items-end justify-center p-0 sm:items-center sm:p-4"
     role="dialog" aria-modal="true"
     aria-label="{{ $label ?? $title }}">

    <div data-modal-overlay class="absolute inset-0 bg-black/60"></div>

    <div class="u-rise relative max-h-dvh w-full {{ $size }} overflow-y-auto rounded-t-card border-2 border-line-strong bg-surface p-5 sm:rounded-card sm:p-6"
         style="box-shadow: var(--shadow-card)">
        {{-- Thumb grip: on a phone this sheet rises from the bottom edge. --}}
        <div class="mx-auto mb-4 h-1.5 w-12 rounded-full bg-line sm:hidden"></div>

        <div class="mb-5 flex items-start justify-between gap-4">
            @if ($title)
                <h2 class="u-display text-xl text-ink">{{ $title }}</h2>
            @else
                <span></span>
            @endif

            <button type="button" data-modal-close
                    class="-mr-2 -mt-2 grid size-12 shrink-0 place-items-center rounded-full text-muted transition-colors hover:bg-surface-2 hover:text-ink"
                    aria-label="{{ __('app.ui.close') }}">
                <x-icon name="close" size="size-6"/>
            </button>
        </div>

        {{ $slot }}
    </div>
</div>
