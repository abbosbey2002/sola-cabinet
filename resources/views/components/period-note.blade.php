@props(['incomplete' => false])

{{--
    Honest reporting of what the range actually covered. The API answers a
    month at a time, so a failing month is skipped — which would otherwise
    show up as a quietly wrong total.

    Colour plus icon plus the sentence itself: never colour alone.
--}}

@if ($incomplete)
    <div class="mb-4 flex flex-col gap-2">
        <p class="flex items-start gap-3 rounded-xl px-4 py-3 text-base"
           style="background: var(--c-danger-soft); color: var(--c-danger)">
            <x-icon name="alert" class="mt-1"/>
            <span>@lang('app.dash.incomplete')</span>
        </p>
    </div>
@endif
