@extends('layouts.guest')
@section('title', trans('app.accounts.choose').' - ')

@section('content')
    @php
        $accounts = (array) data_get($response, 'body.accs', []);

        // Initials for the avatar. mb_* throughout: these names arrive in Uzbek
        // Latin and in Cyrillic, and substr() would cut a two-byte letter in
        // half and render a replacement glyph.
        $initials = fn (?string $name): string => collect(preg_split('/\s+/u', trim((string) $name), -1, PREG_SPLIT_NO_EMPTY))
            ->take(2)
            ->map(fn (string $word): string => mb_strtoupper(mb_substr($word, 0, 1)))
            ->implode('');
    @endphp

    <div class="u-rise mb-4">
        <h1 class="u-display text-2xl text-ink">@lang('app.accounts.choose')</h1>
        <p class="mt-2.5 text-base text-muted">
            {{ trans_choice('app.accounts.attached', count($accounts), ['count' => count($accounts)]) }}
        </p>
    </div>

    <div class="space-y-3">
        @foreach ($accounts as $i => $account)
            @php $mark = $initials($account['abonName'] ?? null); @endphp

            <a href="{{ route('set.account', $account['accId']) }}"
               class="u-card u-rise group flex items-center gap-4 no-underline transition-[border-color,transform] hover:-translate-y-px hover:border-action"
               style="--i:{{ $i + 1 }}">
                @if ($mark !== '')
                    <span class="u-display grid size-12 shrink-0 place-items-center rounded-xl text-base"
                          style="background: var(--c-action-soft); color: var(--c-action)" aria-hidden="true">{{ $mark }}</span>
                @else
                    <span class="grid size-12 shrink-0 place-items-center rounded-xl bg-surface-2 text-muted">
                        <x-icon name="id" size="size-6"/>
                    </span>
                @endif

                <span class="min-w-0 flex-1">
                    {{-- An unnamed account is titled by its number rather than
                         by its type, which the pill beside it already says. --}}
                    <span class="block truncate text-lg font-semibold text-ink">
                        @if (filled($account['abonName'] ?? null))
                            {{ $account['abonName'] }}
                        @else
                            @lang('app.accounts.personal') {{ $account['accId'] }}
                        @endif
                    </span>

                    <span class="mt-1 flex flex-wrap items-center gap-2">
                        @if (filled($account['abonName'] ?? null))
                            <span class="text-sm text-muted">@lang('app.accounts.personal') {{ $account['accId'] }}</span>
                        @endif
                        <x-account-type :type="$account['abonType']"/>
                    </span>
                </span>

                <x-icon name="chevron-right" class="shrink-0 text-muted transition-transform group-hover:translate-x-0.5"/>
            </a>
        @endforeach
    </div>
@endsection
