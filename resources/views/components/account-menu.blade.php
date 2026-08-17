@props(['accounts', 'as' => 'menu'])

@php
    $current = request()->cookie('account');
    $all = (array) data_get($accounts, 'body.accs', []);
    $others = array_values(array_filter(
        $all,
        fn ($account) => (string) ($account['accId'] ?? '') !== (string) $current
    ));
    $name = request()->cookie('full_name');

    // Initials for the avatar. mb_* throughout: the names arrive in Uzbek Latin
    // and in Cyrillic, and substr() would cut a two-byte letter in half.
    $initials = collect(preg_split('/\s+/u', trim((string) $name), -1, PREG_SPLIT_NO_EMPTY))
        ->take(2)
        ->map(fn (string $word): string => mb_strtoupper(mb_substr($word, 0, 1)))
        ->implode('');
@endphp

{{-- The list has no "current account" header of its own: in the drawer it sits
     directly under the <dl> that already states the name, the contract and the
     account number, and repeating them there was the same three facts twice on
     the smallest screen. --}}
@if ($as === 'list')
    <div {{ $attributes }}>
        @if ($others)
            <p class="u-label">@lang('app.accounts.switch')</p>

            <div class="mt-1.5 space-y-1.5">
                @foreach ($others as $account)
                    <a href="{{ route('set.account', $account['accId']) }}"
                       class="flex min-h-[3rem] items-center gap-3 rounded-xl border-2 border-line px-3 py-2.5 no-underline transition-colors hover:border-action">
                        <span class="grid size-9 shrink-0 place-items-center rounded-lg"
                              style="background: var(--c-action-soft); color: var(--c-action)">
                            <x-icon name="id" size="size-4"/>
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-base font-semibold text-ink">
                                {{ filled($account['abonName'] ?? null) ? $account['abonName'] : __('app.accounts.personal').' '.$account['accId'] }}
                            </span>
                            <span class="block truncate text-sm text-muted">{{ $account['accId'] }}</span>
                        </span>
                        <x-icon name="chevron-right" size="size-4" class="text-muted"/>
                    </a>
                @endforeach
            </div>
        @endif

        <a href="{{ route('logout') }}"
           class="mt-3 flex min-h-[3rem] items-center gap-2.5 rounded-xl px-3 text-base font-semibold no-underline"
           style="color: var(--c-danger)">
            <x-icon name="logout"/>@lang('app.menu.logout')
        </a>
    </div>
@else
    <div data-disclosure {{ $attributes->merge(['class' => 'relative']) }}>
        <button type="button" data-disclosure-trigger aria-expanded="false"
                class="flex min-h-[3rem] items-center gap-2 rounded-full py-1.5 pl-1.5 pr-3 text-left transition-colors hover:bg-surface-2">
            <span class="sr-only">@lang('app.accounts.personal') {{ $current }} — @lang('app.accounts.switch')</span>
            <span class="u-display grid size-9 place-items-center rounded-full text-xs"
                  style="background: var(--c-action-soft); color: var(--c-action)" aria-hidden="true">{{ $initials ?: '—' }}</span>
            <span class="hidden text-left lg:block">
                <span class="block text-xs text-muted">@lang('app.accounts.personal')</span>
                <span class="block text-sm font-semibold text-ink">{{ $current }}</span>
            </span>
            <x-icon name="chevron-down" size="size-4" class="text-muted"/>
        </button>

        <div data-disclosure-panel hidden
             class="u-rise absolute right-0 top-[calc(100%+.5rem)] z-50 w-[min(21rem,90vw)] overflow-hidden rounded-card border-2 border-line-strong bg-surface"
             style="box-shadow: var(--shadow-card)">

            <div class="border-b-2 border-line px-4 py-3.5">
                <p class="u-label">@lang('app.accounts.personal')</p>
                <p class="mt-1 truncate text-base font-semibold text-ink">{{ $name ?: '—' }}</p>
                <p class="mt-0.5 text-sm text-muted">{{ $current }}</p>
            </div>

            @if ($others)
                <div class="u-scroll max-h-64 overflow-y-auto p-1.5">
                    <p class="u-label px-2.5 pb-1 pt-2">@lang('app.accounts.switch')</p>

                    @foreach ($others as $account)
                        <a href="{{ route('set.account', $account['accId']) }}"
                           class="flex min-h-[3rem] items-center gap-3 rounded-xl px-2.5 py-2.5 no-underline transition-colors hover:bg-surface-2">
                            <span class="grid size-9 shrink-0 place-items-center rounded-lg"
                                  style="background: var(--c-action-soft); color: var(--c-action)">
                                <x-icon name="id" size="size-4"/>
                            </span>

                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-base font-semibold text-ink">
                                    @if (filled($account['abonName'] ?? null))
                                        {{ $account['abonName'] }}
                                    @else
                                        <x-account-type :type="$account['abonType']" as="text"/>
                                    @endif
                                </span>
                                <span class="block truncate text-sm text-muted">{{ $account['accId'] }}</span>
                            </span>

                            <x-icon name="chevron-right" size="size-4" class="text-muted"/>
                        </a>
                    @endforeach
                </div>
            @endif

            <div class="border-t-2 border-line p-1.5">
                <a href="{{ route('logout') }}"
                   class="flex min-h-[3rem] items-center gap-3 rounded-xl px-2.5 py-2.5 text-base font-semibold no-underline transition-colors hover:bg-surface-2"
                   style="color: var(--c-danger)">
                    <span class="grid size-9 shrink-0 place-items-center rounded-lg" style="background: var(--c-danger-soft)">
                        <x-icon name="logout" size="size-4"/>
                    </span>
                    @lang('app.menu.logout')
                </a>
            </div>
        </div>
    </div>
@endif
