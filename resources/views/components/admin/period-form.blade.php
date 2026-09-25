{{--
    The date range every statistics screen is read over. A GET form, so the
    range lives in the URL: a link to "last week's funnel" can be sent to a
    colleague and opens on the same numbers.

    $period  App\Support\Activity\ReportPeriod
    $action  where the form submits
    $slot    extra fields that belong to the same query (e.g. the segment)
--}}
@props(['period', 'action'])

<form method="get" action="{{ $action }}" class="u-no-print flex flex-wrap items-end gap-3">
    <div>
        <label for="period-from" class="u-label mb-2 block">@lang('admin.period.from')</label>
        <input type="date" id="period-from" name="from" value="{{ $period->fromDate() }}"
               max="{{ now('UTC')->format('Y-m-d') }}" class="u-field w-44 py-2.5 text-sm">
    </div>
    <div>
        <label for="period-to" class="u-label mb-2 block">@lang('admin.period.to')</label>
        <input type="date" id="period-to" name="to" value="{{ $period->toDate() }}"
               max="{{ now('UTC')->format('Y-m-d') }}" class="u-field w-44 py-2.5 text-sm">
    </div>

    {{ $slot }}

    <button type="submit" class="u-btn-primary u-btn-sm">@lang('app.admin.apply')</button>
</form>
