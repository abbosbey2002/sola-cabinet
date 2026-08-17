@props(['icon' => 'chart', 'title', 'hint' => null])

{{--
    An empty screen is an invitation, not an error. It says what would be here
    and, where there is one, what to do about it — never "no data found".
--}}

<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center gap-3 px-6 py-12 text-center']) }}>
    <div class="grid size-14 place-items-center rounded-xl bg-surface-2 text-muted">
        <x-icon :name="$icon" size="size-7"/>
    </div>

    <p class="text-lg font-semibold text-ink">{{ $title }}</p>

    @if ($hint)
        <p class="max-w-[36ch] text-base text-muted">{{ $hint }}</p>
    @endif

    {{ $slot }}
</div>
