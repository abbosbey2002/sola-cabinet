@props([
    'icon',
    'label',
    'value' => null,
    'hint' => null,
    'href' => null,
    'clickable' => true,
])

@php
    $tag = ($clickable && filled($href)) ? 'a' : 'div';
@endphp

<{{ $tag }} @if ($clickable && filled($href)) href="{{ $href }}" @endif
    {{ $attributes->merge(['class' => 'u-card u-rise group flex items-start gap-4 no-underline'.(($clickable && filled($href)) ? ' transition-[border-color,transform,box-shadow] hover:-translate-y-0.5 hover:border-action' : ' cursor-default')]) }}>
    <span class="grid size-11 shrink-0 place-items-center rounded-xl"
        style="background: var(--c-action-soft); color: var(--c-action)">
        <x-icon :name="$icon" size="size-5"/>
    </span>
    <span class="min-w-0 flex-1">
        <span class="u-label block">{{ $label }}</span>
        @if (filled($value))
            <span class="mt-1.5 block text-lg font-semibold text-ink">{{ $value }}</span>
        @endif
        @if (filled($hint))
            <span class="mt-1.5 block text-sm {{ filled($value) ? 'text-muted' : 'font-semibold text-ink' }}">{{ $hint }}</span>
        @endif
    </span>
    @if ($clickable && filled($href))
        <x-icon name="chevron-right"
            class="mt-1 shrink-0 text-muted transition-transform group-hover:translate-x-0.5"/>
    @endif
</{{ $tag }}>
