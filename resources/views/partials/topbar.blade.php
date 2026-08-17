@php
    // One entry per section, each its own page and its own route. The desktop
    // rail and the mobile drawer both render from this list, so a section can
    // never appear in one and go missing from the other — which is exactly what
    // happens when the two lists are maintained separately.
    $items = [
        ['route' => 'cabinet', 'icon' => 'home', 'label' => __('app.nav.home')],
        ['route' => 'tariff', 'icon' => 'tag', 'label' => __('app.nav.tariff')],
        ['route' => 'devices', 'icon' => 'router', 'label' => __('app.nav.devices')],
        ['route' => 'traffic', 'icon' => 'chart', 'label' => __('app.nav.statistics')],
        ['route' => 'payment', 'icon' => 'receipt', 'label' => __('app.nav.payments')],
        ['route' => 'services', 'icon' => 'gift', 'label' => __('app.nav.services')],
        ['url' => config('sola.speedtest_url'), 'icon' => 'speed', 'label' => __('app.nav.speedtest'), 'external' => true],
    ];
@endphp

<header class="u-no-print sticky top-0 z-40 border-b-2 border-line bg-bg/90 backdrop-blur-md">
    <div class="mx-auto max-w-[1240px] px-4 sm:px-6">

        {{-- flex-wrap is the last defence on narrow screens: the right-hand
             group drops to a second line rather than producing a horizontal
             scrollbar. From lg it is nowrap, so a long name does not break the
             row and the truncate below can do its job — a wrapping flex line
             breaks first and shrinks second, which means truncate never fires
             while wrap is on.

             At 145% text the row does not fit either way; that case is handled
             by the u-id-row / u-id-facts rules at the end of app.css. --}}
        <div class="u-id-row flex min-h-[4.5rem] flex-wrap items-center gap-3 py-2 lg:flex-nowrap">
            <button type="button" data-nav-toggle aria-expanded="false" aria-controls="nav-drawer"
                    class="-ml-2 grid size-12 shrink-0 place-items-center rounded-full text-ink transition-colors hover:bg-surface-2 lg:hidden">
                <x-icon name="menu" size="size-6"/>
                <span class="sr-only">@lang('app.ui.menu')</span>
            </button>

            <a href="{{ route('cabinet') }}" class="shrink-0 no-underline">
                <x-logo/>
            </a>

            @isset($profile)
                <dl class="u-id-facts ml-5 hidden min-w-0 items-center gap-x-7 2xl:flex">
                    @foreach ([
                        ['label' => __('app.cabinet.fio'), 'value' => $profile->fullName()],
                        ['label' => __('app.dash.contract'), 'value' => $profile->contractNumber()],
                    ] as $fact)
                        @continue(blank($fact['value']))
                        <div class="flex min-w-0 items-baseline gap-2">
                            <dt class="u-label">{{ $fact['label'] }}</dt>
                            <dd class="truncate text-sm font-semibold text-ink">{{ $fact['value'] }}</dd>
                        </div>
                    @endforeach
                </dl>
            @endisset

            <div class="ml-auto flex shrink-0 items-center gap-2">
                @if (config('sola.call_center'))
                    <a href="tel:{{ config('sola.call_center') }}"
                       class="hidden min-h-[3rem] items-center gap-2 rounded-full px-4 py-2 text-sm font-semibold text-ink no-underline transition-colors hover:bg-surface-2 sm:flex">
                        <x-icon name="phone" size="size-5" style="color: var(--c-action)"/>{{ config('sola.call_center') }}
                    </a>
                @endif

                <x-view-settings/>

                {{-- Both of these are dropped from the top row on a phone and
                     offered inside the drawer instead. "Ko'rinish" keeps its
                     place: enlarging the text is needed more often than
                     switching language, and its icon is not self-explanatory. --}}
                <x-lang-switch class="hidden sm:block"/>

                @isset($accounts)
                    <x-account-menu :accounts="$accounts" class="hidden sm:block"/>
                @endisset
            </div>
        </div>

        <nav class="hidden flex-wrap items-center gap-1.5 pb-2.5 lg:flex" aria-label="{{ __('app.ui.menu') }}">
            @foreach ($items as $item)
                <a href="{{ $item['url'] ?? route($item['route']) }}"
                   @if ($item['external'] ?? false) target="_blank" rel="noopener" @endif
                   @if (isset($item['route']) && request()->routeIs($item['route'])) aria-current="page" @endif
                   class="u-nav-link">
                    <x-icon :name="$item['icon']"/>{{ $item['label'] }}
                </a>
            @endforeach
        </nav>
    </div>
</header>

{{-- Mobile drawer ------------------------------------------------------- --}}
<div id="nav-drawer" data-nav-drawer hidden class="u-no-print fixed inset-0 z-[65] lg:hidden"
     role="dialog" aria-modal="true" aria-label="{{ __('app.ui.menu') }}">
    <div data-nav-scrim class="absolute inset-0 bg-black/60"></div>

    <div class="u-rise absolute inset-y-0 left-0 flex w-[min(21rem,88vw)] flex-col overflow-y-auto border-r-2 border-line bg-surface">
        <div class="flex items-center justify-between px-5 py-4">
            <x-logo/>

            <button type="button" data-nav-close
                    class="grid size-12 place-items-center rounded-full text-ink transition-colors hover:bg-surface-2">
                <x-icon name="close" size="size-6"/>
                <span class="sr-only">@lang('app.ui.close')</span>
            </button>
        </div>

        @isset($profile)
            <dl class="mx-3 mb-3 space-y-2.5 rounded-xl bg-surface-2 px-4 py-3.5">
                @foreach ([
                    ['label' => __('app.cabinet.fio'), 'value' => $profile->fullName()],
                    ['label' => __('app.dash.contract'), 'value' => $profile->contractNumber()],
                    ['label' => __('app.accounts.personal'), 'value' => request()->cookie('account')],
                ] as $fact)
                    @continue(blank($fact['value']))
                    <div>
                        <dt class="u-label">{{ $fact['label'] }}</dt>
                        <dd class="text-base font-semibold text-ink">{{ $fact['value'] }}</dd>
                    </div>
                @endforeach
            </dl>
        @endisset

        {{-- On a phone the account chip does not fit the top row, so the same
             list is rendered inline here rather than behind a dropdown inside
             an already-open drawer. --}}
        @isset($accounts)
            <div class="mb-3 px-3 sm:hidden">
                <x-account-menu :accounts="$accounts" as="list"/>
            </div>
        @endisset

        <nav class="flex-1 space-y-1.5 px-3" aria-label="{{ __('app.ui.menu') }}">
            @foreach ($items as $item)
                <a href="{{ $item['url'] ?? route($item['route']) }}"
                   @if ($item['external'] ?? false) target="_blank" rel="noopener" @endif
                   @if (isset($item['route']) && request()->routeIs($item['route'])) aria-current="page" @endif
                   class="u-nav-link w-full !rounded-xl !px-4 !py-3 !text-base">
                    <x-icon :name="$item['icon']"/>{{ $item['label'] }}
                </a>
            @endforeach
        </nav>

        <div class="border-t-2 border-line px-5 py-5 sm:hidden">
            <p class="u-label mb-2">@lang('app.view.language')</p>
            <x-lang-switch as="chips"/>
        </div>

        <div class="border-t-2 border-line px-5 py-5">
            <p class="u-label">@lang('app.menu.call')</p>
            <div class="mt-2 flex flex-col gap-1.5">
                @foreach ([config('sola.call_center'), config('sola.call_phone')] as $number)
                    @continue(blank($number))
                    <a href="tel:{{ $number }}"
                       class="flex min-h-[3rem] items-center gap-2.5 text-lg font-semibold text-ink no-underline">
                        <x-icon name="phone" style="color: var(--c-action)"/>{{ $number }}
                    </a>
                @endforeach
            </div>
        </div>
    </div>
</div>
