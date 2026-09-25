{{-- One headline number. A number, not a chart — it answers "how many". --}}
@props(['label', 'value', 'hint' => null, 'tone' => null])

<div class="u-card !p-4 sm:!p-5">
    <p class="u-label">{{ $label }}</p>
    <p @class([
        'u-figure mt-1.5 text-3xl',
        'text-ink' => $tone === null,
        'text-warn' => $tone === 'warn',
        'text-action' => $tone === 'ok',
    ])>{{ $value }}</p>
    @if ($hint)
        <p class="mt-1 text-sm text-muted">{{ $hint }}</p>
    @endif
</div>
