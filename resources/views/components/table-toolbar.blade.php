@props(['search' => false, 'print' => false])

{{--
    What you do TO a table, above the table: narrow it down, or take it away
    with you. Pagination is the other half of spec §11 and stays below in
    x-table-nav — you page through data after reading it, but you search
    before, and a search box under ten rows is a box you scroll past to reach.

    The print button opens the browser's own dialog, where "Save as PDF" is a
    destination on every desktop and both phone platforms. That is the whole
    PDF feature: the print stylesheet already lays this table out for A4, and a
    JS PDF library would add ~600KB — half of it a Cyrillic font, without which
    "Оплачено" comes out as boxes.
--}}

@if ($search || $print)
    <div class="u-no-print mb-1 flex flex-wrap items-center gap-3 border-b-2 border-line px-4 py-3">
        @if ($search)
            <label class="relative min-w-0 flex-1 sm:max-w-xs">
                <span class="sr-only">@lang('app.dash.search')</span>
                <input type="search" data-table-search placeholder="{{ __('app.dash.search') }}"
                       class="u-field py-2.5 pl-11 text-sm">
                <x-icon name="search" class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-muted"/>
            </label>
        @endif

        @if ($print)
            <button type="button" data-table-print class="u-btn-ghost u-btn-sm ml-auto">
                <x-icon name="printer" size="size-4"/>@lang('app.dash.print')
            </button>
        @endif
    </div>
@endif
