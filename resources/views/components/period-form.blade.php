@props(['action', 'period', 'target'])

{{--
    The "Davr, dan … gacha" + "Shakllantirish" control (spec §5).

    It posts to $action and the answer replaces #$target, so the page never
    reloads. Without JavaScript the same form still submits normally and the
    server returns just that block — degraded, but not broken.

    The two date fields sit in a 2-col grid with a real gap. A plain flex row
    let native date inputs (wide Russian/Uzbek locale text) keep min-content
    width and visually butt against each other on a phone; min-w-0 lets each
    field shrink inside its cell so the gap stays visible.
--}}

<form method="post" action="{{ $action }}"
      data-ajax-form data-ajax-target="#{{ $target }}"
      class="u-no-print flex w-full flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-end">
    @csrf

    <div class="grid w-full min-w-0 grid-cols-2 gap-3 sm:w-auto sm:grid-cols-[10.5rem_10.5rem]">
        <div class="min-w-0">
            <label for="{{ $target }}-start" class="u-label mb-1.5 block">
                @lang('app.dash.period'), @lang('app.dash.from')
            </label>
            <input type="date" id="{{ $target }}-start" name="start" required
                   value="{{ $period->startInput() }}" max="{{ $period->endInput() }}"
                   class="u-field !text-sm">
        </div>

        <div class="min-w-0">
            <label for="{{ $target }}-end" class="u-label mb-1.5 block">@lang('app.dash.to')</label>
            <input type="date" id="{{ $target }}-end" name="end" required
                   value="{{ $period->endInput() }}"
                   class="u-field !text-sm">
        </div>
    </div>

    <button type="submit" class="u-btn-primary w-full shrink-0 sm:w-auto">
        <x-icon name="refresh"/>@lang('app.dash.build')
    </button>
</form>
