@props(['clamped' => false, 'incomplete' => false])

{{--
    Honest reporting of what the range actually covered. The API answers a
    month at a time, so a long range is trimmed and a failing month is skipped
    — both would otherwise show up as quietly wrong totals.

    Colour plus icon plus the sentence itself: never colour alone.
--}}

@if ($clamped || $incomplete)
    <div class="mb-4 flex flex-col gap-2">
        @if ($clamped)
            <p class="flex items-start gap-3 rounded-xl px-4 py-3 text-base"
               style="background: var(--c-warn-soft); color: var(--c-warn)">
                <x-icon name="alert" class="mt-1"/>
                <span>@lang('app.dash.clamped', ['months' => \App\Support\Period::MAX_MONTHS])</span>
            </p>
        @endif

        @if ($incomplete)
            <p class="flex items-start gap-3 rounded-xl px-4 py-3 text-base"
               style="background: var(--c-danger-soft); color: var(--c-danger)">
                <x-icon name="alert" class="mt-1"/>
                <span>@lang('app.dash.incomplete')</span>
            </p>
        @endif
    </div>
@endif
