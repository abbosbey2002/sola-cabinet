{{--
    Theme and text size, chosen by the subscriber and remembered on the device.

    Both controls are three-state and follow the same pattern: an empty value
    removes the attribute, so "system theme" and "normal size" stay the genuine
    default rather than being imitated with an attribute of their own.

    Nothing here talks to the server. The choice is a display preference, it is
    per-device by nature, and routing it through billing would mean a round trip
    to change a font size.

    The trigger keeps its word next to the icon while the language switch does
    not: needing bigger text is more common than needing another language, and
    the two-sliders glyph means nothing on its own.
--}}

@php
    $themes = [
        ['value' => 'light', 'icon' => 'sun', 'label' => __('app.view.light')],
        ['value' => 'dark', 'icon' => 'moon', 'label' => __('app.view.dark')],
        ['value' => '', 'icon' => 'auto', 'label' => __('app.view.system')],
    ];

    // The "A" is a sample of the step; the label under it is one size in all
    // three, otherwise the longest label ("Самый крупный") wraps and makes that
    // chip taller than its neighbours.
    $sizes = [
        ['value' => '', 'sample' => '1rem', 'label' => __('app.view.normal')],
        ['value' => 'lg', 'sample' => '1.375rem', 'label' => __('app.view.large')],
        ['value' => 'xl', 'sample' => '1.75rem', 'label' => __('app.view.largest')],
    ];
@endphp

<div data-disclosure {{ $attributes->merge(['class' => 'relative']) }}>
    <button type="button" data-disclosure-trigger aria-expanded="false"
            class="flex min-h-[3rem] items-center gap-2 rounded-full border-2 border-line px-4 py-2 text-sm font-semibold text-ink transition-colors hover:bg-surface-2">
        <x-icon name="view"/>
        {{-- The word is visible from sm, and hidden again from 2xl — that is
             exactly where the identity facts join the row, and in Russian
             "Отображение" is long enough to squeeze the subscriber's own name
             down to "Alis…". sr-only rather than removed: the button keeps its
             accessible name at every width. --}}
        <span class="sr-only sm:not-sr-only 2xl:sr-only">@lang('app.view.title')</span>
    </button>

    <div data-disclosure-panel hidden
         class="u-rise absolute right-0 top-[calc(100%+.5rem)] z-50 w-[min(21rem,90vw)] rounded-card border-2 border-line-strong bg-surface p-5"
         style="box-shadow: var(--shadow-card)">

        <fieldset class="border-0 p-0">
            <legend class="u-label mb-2.5">@lang('app.view.theme')</legend>

            <div class="grid grid-cols-3 gap-2">
                @foreach ($themes as $theme)
                    <label class="u-choice flex-col !gap-1">
                        <input type="radio" name="sola-theme" value="{{ $theme['value'] }}">
                        <x-icon :name="$theme['icon']"/>
                        {{ $theme['label'] }}
                    </label>
                @endforeach
            </div>
        </fieldset>

        <fieldset class="mt-5 border-0 p-0">
            <legend class="u-label mb-2.5">@lang('app.view.text_size')</legend>

            <div class="grid grid-cols-3 gap-2">
                @foreach ($sizes as $size)
                    <label class="u-choice flex-col !gap-0.5">
                        <input type="radio" name="sola-text" value="{{ $size['value'] }}">
                        <span class="u-display leading-none" style="font-size: {{ $size['sample'] }}" aria-hidden="true">A</span>
                        <span class="text-sm">{{ $size['label'] }}</span>
                    </label>
                @endforeach
            </div>

            <p class="mt-2.5 text-xs text-muted">@lang('app.view.stored')</p>
        </fieldset>
    </div>
</div>
