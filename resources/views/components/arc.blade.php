@props([
    // [['fraction' => 0.0..1.0, 'color' => '<css colour>'], …] drawn in order,
    // each starting where the previous one ended.
    'segments' => [],
])

{{--
    A 240° gauge built from concentric arcs — the same shape language as the )))
    in the Sola logo. It carries the traffic split: the one place in the cabinet
    where a number really is a share of something.

    Geometry: pathLength="360" normalises the circle so one unit is one degree,
    which lets a segment be positioned by rotation and sized by dash length with
    no trigonometry in the template.

    Colours are passed as var(--c-*), never var(--color-*): @theme inline means
    Tailwind never emits the --color-* variables at all — the utilities resolve
    to var(--c-*) directly — so a --color-* reference inside style/stroke/fill
    resolves to nothing and the arc renders invisible.

    The caption sits in an absolutely positioned block at the arc's centre
    rather than being pulled up with a negative margin: a negative margin is
    tuned to one font size, and this interface lets the subscriber change it.
--}}

@php
    $sweep = 240;

    $offset = 0.0;
    $arcs = [];

    foreach ($segments as $i => $segment) {
        $length = max(0.0, min(1.0, (float) ($segment['fraction'] ?? 0))) * $sweep;

        $arcs[] = [
            'rotate' => 150 + $offset,
            'length' => $length,
            'color' => $segment['color'] ?? 'var(--c-action)',
            'delay' => 0.15 + $i * 0.15,
        ];

        $offset += $length;
    }
@endphp

<div {{ $attributes->merge(['class' => 'relative']) }}>
    <svg viewBox="0 0 200 150" class="w-full" aria-hidden="true" focusable="false">
        {{-- The faint dotted ring: the innermost of the logo's arcs. --}}
        <circle cx="100" cy="100" r="58" fill="none"
                stroke="var(--c-line-strong)" stroke-width="1.5"
                stroke-dasharray="0.8 5.5" stroke-linecap="round"/>

        {{-- Track --}}
        <circle cx="100" cy="100" r="78" fill="none" pathLength="360"
                stroke="var(--c-surface-2)" stroke-width="18" stroke-linecap="round"
                stroke-dasharray="{{ $sweep }} 360"
                transform="rotate(150 100 100)"/>

        @foreach ($arcs as $arc)
            @continue($arc['length'] <= 0)
            <circle cx="100" cy="100" r="78" fill="none" pathLength="360"
                    class="u-draw"
                    stroke="{{ $arc['color'] }}" stroke-width="18" stroke-linecap="round"
                    transform="rotate({{ round($arc['rotate'], 2) }} 100 100)"
                    style="--arc-len: {{ round($arc['length'], 2) }}; animation-delay: {{ $arc['delay'] }}s"/>
        @endforeach
    </svg>

    {{-- 100/150 — where the arc's centre falls inside the viewBox. --}}
    <div class="absolute inset-x-0 top-[66.7%] -translate-y-1/2 text-center">
        {{ $slot }}
    </div>
</div>
