@props(['label' => null])

{{--
    Pagination only, and it belongs under the rows it pages: you reach for
    "next" after reading the ten in front of you. Search and print moved above
    the table into x-table-toolbar — see the note there.

    None of this appears in design/ — the prototype tables are seven fixed rows.
    It is kept because production pages both of these tables, and a paged table
    without a pager is a table whose second page cannot be reached.
--}}

<nav data-table-nav hidden
     class="u-no-print flex flex-wrap items-center justify-between gap-3 border-t-2 border-line px-4 py-3"
     @if ($label) aria-label="{{ $label }}" @endif>
    <button type="button" data-table-prev class="u-btn-ghost u-btn-sm disabled:pointer-events-none disabled:opacity-40">
        <x-icon name="chevron-left" size="size-4"/>@lang('app.prev')
    </button>

    <span data-table-status data-template="{{ __('app.ui.page', ['page' => '{page}', 'pages' => '{pages}']) }}"
          class="text-sm text-muted" aria-live="polite"></span>

    <button type="button" data-table-next class="u-btn-ghost u-btn-sm disabled:pointer-events-none disabled:opacity-40">
        @lang('app.next')<x-icon name="chevron-right" size="size-4"/>
    </button>
</nav>
