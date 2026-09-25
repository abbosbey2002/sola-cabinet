{{-- The four views of the same journal. The date range travels with the tab. --}}
@php
    $query = ['from' => $period->fromDate(), 'to' => $period->toDate()];
    $tabs = [
        'admin.stats' => __('admin.stats.tab_overview'),
        'admin.stats.funnel' => __('admin.stats.tab_funnel'),
        'admin.stats.segments' => __('admin.stats.tab_segments'),
        'admin.stats.errors' => __('admin.stats.tab_errors'),
    ];
@endphp

<nav class="u-no-print u-scroll mb-5 flex gap-2 overflow-x-auto pb-1" aria-label="@lang('admin.stats.tabs_label')">
    @foreach ($tabs as $route => $label)
        <a href="{{ route($route, $query) }}"
           class="u-choice shrink-0 whitespace-nowrap aria-[current=page]:border-action aria-[current=page]:bg-action-soft aria-[current=page]:text-action"
           @if (request()->routeIs($route)) aria-current="page" @endif>{{ $label }}</a>
    @endforeach
</nav>
