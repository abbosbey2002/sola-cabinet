@extends('layouts.app')
@section('title', trans('app.nav.services').' - ')
@section('heading', trans('app.nav.services'))

@section('content')
    @php
        // Spec §8, §9, §10. These cards carry no billing data — they are entry
        // points — so each appears only once its destination is configured
        // (config/sola.php). An unconfigured card would be a dead link, and a
        // dead link is worse than an absent one.
        $cards = array_values(array_filter([
            config('sola.promo_url') ? [
                'icon' => 'tag', 'url' => config('sola.promo_url'), 'external' => true,
                'title' => __('app.promo.title'), 'text' => __('app.promo.text'), 'go' => __('app.promo.go'),
            ] : null,
            config('sola.loyalty_url') ? [
                'icon' => 'gift', 'url' => config('sola.loyalty_url'), 'external' => true,
                'title' => __('app.loyalty.title'), 'text' => __('app.loyalty.text'), 'go' => __('app.loyalty.go'),
            ] : null,
            config('sola.chat.url') ? [
                'icon' => 'chat', 'url' => config('sola.chat.url'), 'external' => true,
                'title' => __('app.chat.title'), 'text' => __('app.chat.text'), 'go' => __('app.chat.go'),
            ] : null,
            config('sola.manager_url') ? [
                'icon' => 'user', 'url' => config('sola.manager_url'), 'external' => true,
                'title' => __('app.manager.title'), 'text' => __('app.manager.text'), 'go' => __('app.manager.go'),
            ] : null,
            config('sola.speedtest_url') ? [
                'icon' => 'speed', 'url' => config('sola.speedtest_url'), 'external' => true,
                'title' => __('app.nav.speedtest'), 'text' => __('app.speedtest.text'), 'go' => __('app.speedtest.go'),
            ] : null,
        ]));
    @endphp

    <p class="u-rise mb-5 max-w-[52ch] text-base text-muted">@lang('app.services.intro')</p>

    @if ($cards)
        <div class="grid gap-4 sm:grid-cols-2">
            @foreach ($cards as $i => $card)
                <a href="{{ $card['url'] }}" target="_blank" rel="noopener"
                   class="u-card u-rise group flex min-w-0 items-start gap-4 no-underline transition-[border-color,transform] hover:-translate-y-px hover:border-action"
                   style="--i:{{ $i + 1 }}">
                    <span class="grid size-12 shrink-0 place-items-center rounded-xl"
                          style="background: var(--c-action-soft); color: var(--c-action)">
                        <x-icon :name="$card['icon']" size="size-6"/>
                    </span>

                    <span class="min-w-0">
                        <span class="block text-lg font-semibold text-ink">{{ $card['title'] }}</span>
                        <span class="mt-1 block text-sm text-muted">{{ $card['text'] }}</span>
                        <span class="mt-2.5 inline-flex items-center gap-1.5 text-sm font-semibold" style="color: var(--c-action)">
                            {{ $card['go'] }}<x-icon name="chevron-right" size="size-4"/>
                        </span>
                    </span>
                </a>
            @endforeach
        </div>
    @endif

    {{-- If nothing above is configured and there's no call center either, this
         page — reached specifically by someone looking for help — must not
         render as a dead end. --}}
    @if (! $cards && ! config('sola.call_center'))
        <x-empty icon="chat" :title="__('app.empty.services')" :hint="__('app.empty.services_hint')"/>
    @endif

    @if (config('sola.call_center'))
        <section class="u-card u-rise mt-4" style="--i:5" aria-labelledby="call-title">
            <h2 id="call-title" class="text-xl font-bold text-ink">@lang('app.services.call_title')</h2>
            <p class="mt-2 text-base text-muted">@lang('app.services.call_hours')</p>

            {{-- The short call-centre number is the primary action; the city
                 line is the same action by another route, so it is a ghost.
                 Two filled buttons side by side would be two things asking to
                 be pressed first. --}}
            <div class="mt-4 flex flex-wrap gap-3">
                @foreach (array_values(array_filter([config('sola.call_center'), config('sola.call_phone')])) as $i => $number)
                    <a href="tel:{{ $number }}" class="{{ $i === 0 ? 'u-btn-primary' : 'u-btn-ghost' }} w-full sm:w-auto">
                        <x-icon name="phone"/>{{ __('app.services.call_action', ['number' => $number]) }}
                    </a>
                @endforeach
            </div>
        </section>
    @endif
@endsection
