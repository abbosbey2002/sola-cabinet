@props(['as' => 'menu'])

{{--
    Text labels, never flags: a flag is a country, and there is no flag for
    "Russian as spoken in Uzbekistan". Switching keeps the subscriber on the
    page they were reading — LocaleController redirects back — rather than
    dropping them on a localized home page.

    Two shapes for two places. In the top row it is a dropdown, because the row
    is already full; inside the drawer it is a row of chips, because a dropdown
    nested in an open drawer is a second layer of hiding over the same choice.
--}}

@php
    $locales = ['uz' => "O'zbekcha", 'ru' => 'Русский', 'en' => 'English'];
    $short = ['uz' => 'UZ', 'ru' => 'RU', 'en' => 'EN'];
    $active = array_key_exists(app()->getLocale(), $locales) ? app()->getLocale() : 'uz';
@endphp

@if ($as === 'chips')
    <div {{ $attributes->merge(['class' => 'grid grid-cols-3 gap-2']) }}>
        @foreach ($locales as $code => $name)
            <a href="{{ route('change.lang', $code) }}"
               class="u-choice no-underline"
               @if ($code === $active) aria-current="true" @endif>
                {{ $short[$code] }}
                <span class="sr-only">— {{ $name }}</span>
            </a>
        @endforeach
    </div>
@else
    <div data-disclosure {{ $attributes->merge(['class' => 'relative']) }}>
        <button type="button" data-disclosure-trigger aria-expanded="false"
                class="flex min-h-[3rem] items-center gap-2 rounded-full px-4 py-2 text-sm font-semibold text-ink transition-colors hover:bg-surface-2">
            <span class="sr-only">@lang('app.view.language'): </span>{{ $short[$active] }}
            <x-icon name="chevron-down" size="size-4" class="text-muted"/>
        </button>

        <div data-disclosure-panel hidden
             class="u-rise absolute right-0 top-[calc(100%+.5rem)] z-50 w-[min(15rem,90vw)] overflow-hidden rounded-card border-2 border-line-strong bg-surface p-1.5"
             style="box-shadow: var(--shadow-card)">
            @foreach ($locales as $code => $name)
                <a href="{{ route('change.lang', $code) }}"
                   @if ($code === $active) aria-current="true" @endif
                   class="flex min-h-[3rem] items-center justify-between gap-3 rounded-xl px-4 py-2.5 text-base font-semibold no-underline transition-colors
                          {{ $code === $active ? '' : 'text-ink hover:bg-surface-2' }}"
                   @if ($code === $active) style="background: var(--c-action-soft); color: var(--c-action)" @endif>
                    {{ $name }}
                    @if ($code === $active)
                        <x-icon name="check" size="size-4"/>
                    @endif
                </a>
            @endforeach
        </div>
    </div>
@endif
