{{-- Server-side pages for a LengthAwarePaginator; the query string rides along. --}}
@props(['paginator'])

@if ($paginator->hasPages())
    <nav class="u-no-print mt-4 flex flex-wrap items-center justify-between gap-3" aria-label="@lang('admin.pager.label')">
        <p class="text-sm text-muted tabular-nums">
            @lang('admin.pager.status', ['from' => $paginator->firstItem(), 'to' => $paginator->lastItem(), 'total' => $paginator->total()])
        </p>
        <div class="flex gap-2">
            @if ($paginator->onFirstPage())
                <span class="u-btn-ghost u-btn-sm opacity-50" aria-disabled="true">
                    <x-icon name="chevron-left" size="size-4"/>@lang('admin.pager.prev')
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" class="u-btn-ghost u-btn-sm" rel="prev">
                    <x-icon name="chevron-left" size="size-4"/>@lang('admin.pager.prev')
                </a>
            @endif

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" class="u-btn-ghost u-btn-sm" rel="next">
                    @lang('admin.pager.next')<x-icon name="chevron-right" size="size-4"/>
                </a>
            @else
                <span class="u-btn-ghost u-btn-sm opacity-50" aria-disabled="true">
                    @lang('admin.pager.next')<x-icon name="chevron-right" size="size-4"/>
                </span>
            @endif
        </div>
    </nav>
@endif
