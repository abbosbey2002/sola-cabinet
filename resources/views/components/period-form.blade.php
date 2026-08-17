@props(['action', 'period', 'target'])

{{--
    The "Davr, dan … gacha" + "Shakllantirish" control (spec §5).

    It posts to $action and the answer replaces #$target, so the page never
    reloads. Without JavaScript the same form still submits normally and the
    server returns just that block — degraded, but not broken.

    The date inputs are given an explicit width rather than left to grow: the
    native picker sizes itself to the locale's format and the two fields ended
    up visibly different widths side by side.
--}}

<form method="post" action="{{ $action }}"
      data-ajax-form data-ajax-target="#{{ $target }}"
      class="u-no-print flex flex-wrap items-end gap-3">
    @csrf

    <div>
        <label for="{{ $target }}-start" class="u-label mb-1.5 block">
            @lang('app.dash.period'), @lang('app.dash.from')
        </label>
        <input type="date" id="{{ $target }}-start" name="start" required
               value="{{ $period->startInput() }}" max="{{ $period->endInput() }}"
               class="u-field !text-sm sm:w-[10.5rem]">
    </div>

    <div>
        <label for="{{ $target }}-end" class="u-label mb-1.5 block">@lang('app.dash.to')</label>
        <input type="date" id="{{ $target }}-end" name="end" required
               value="{{ $period->endInput() }}"
               class="u-field !text-sm sm:w-[10.5rem]">
    </div>

    <button type="submit" class="u-btn-primary">
        <x-icon name="refresh"/>@lang('app.dash.build')
    </button>
</form>
