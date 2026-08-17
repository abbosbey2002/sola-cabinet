@extends('layouts.app')
@section('title', trans('app.nav.devices').' - ')
@section('heading', trans('app.nav.devices'))

@section('content')
    @php
        // Spec §4. Only permanent subscribers hold extra device permits; for
        // everyone else this page reports what is connected and nothing more.
        // $isPermanent arrives from the view composer — the rule lives in
        // AbonentSession, because "type === 2" was wrong here: billing numbers
        // legal entities and individuals separately, and one of them was being
        // shown a "Doimiy" badge beside a page that refused them.

        // What a further permit costs, as /device/list reports it in tiyin.
        // Billing sends -1 when it has no price to give, and what that means is
        // not documented — so it is passed over rather than guessed at.
        $connect = is_numeric($connectCost ?? null) ? (float) $connectCost : null;
        $hasPrice = $connect !== null && $connect >= 0;
    @endphp

    <section class="u-card u-rise" aria-labelledby="devices-title">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 id="devices-title" class="text-xl font-bold text-ink">@lang('app.services.devices')</h2>

            @if ($isPermanent)
                {{-- POST: registering a permit is billed. As a GET link this
                     carried no CSRF token, so any page opened in the same
                     browser could have charged the subscriber for it. --}}
                <form method="post" action="{{ route('devices.add') }}" class="u-no-print">
                    @csrf
                    <button type="submit" class="u-btn-ghost u-btn-sm"
                            data-confirm="{{ __('app.header.add_device_sure') }}"
                            data-confirm-action="{{ __('app.dash.connect') }}">
                        <x-icon name="plus" size="size-4"/>@lang('app.services.add_devices')
                    </button>
                </form>
            @endif
        </div>

        @if ($devices)
            <div class="u-table-wrap u-scroll mt-4">
                {{-- No <caption>: the section heading directly above says the
                     same thing, and a screen reader would announce it twice. --}}
                <table class="u-table u-table-cards">
                    <thead>
                        <tr>
                            <th scope="col">@lang('app.header.mac')</th>
                            <th scope="col">@lang('app.header.status')</th>
                            <th scope="col">@lang('app.header.date_connect')</th>
                            <th scope="col" class="text-right">@lang('app.actions')</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($devices as $device)
                            <tr>
                                <td data-label="{{ __('app.header.mac') }}">
                                    <span class="font-semibold">{{ $device['mac'] ?: __('app.header.no_mac') }}</span>
                                </td>

                                <td data-label="{{ __('app.header.status') }}">
                                    @if ($device['ip'])
                                        <span class="u-pill-ok">
                                            <x-icon name="check" size="size-4"/>@lang('app.header.online')
                                        </span>
                                    @else
                                        <span class="u-pill-neutral">
                                            <x-icon name="minus" size="size-4"/>@lang('app.header.offline')
                                        </span>
                                    @endif
                                </td>

                                <td data-label="{{ __('app.header.date_connect') }}">
                                    {{ \Carbon\Carbon::parse($device['connect_date'])->format('d.m.Y') }}
                                </td>

                                {{-- The empty cell is written as its own branch and left
                                     genuinely empty — not an @if inside the <td>. In card
                                     mode every cell prints its header from data-label, and
                                     the ".u-table-cards td:empty { display: none }" rule
                                     that suppresses it matches only a cell with NO child
                                     nodes at all: the newlines an inline @if leaves behind
                                     are text nodes, so the rule would never fire and a
                                     phone would show an "Actions" label with nothing
                                     under it. --}}
                                @if ($isPermanent && ! $device['readonly'])
                                    <td data-label="{{ __('app.actions') }}" class="text-right">
                                        <form method="post" action="{{ route('devices.delete', $device['permit_id']) }}" class="u-no-print">
                                            @csrf
                                            <button type="submit" class="u-btn-danger u-btn-sm"
                                                    data-confirm="{{ __('app.header.are_you_cancel') }}"
                                                    data-confirm-action="{{ __('app.detele') }}"
                                                    data-confirm-tone="danger">
                                                <x-icon name="trash" size="size-4"/>@lang('app.detele')
                                            </button>
                                        </form>
                                    </td>
                                @else
                                    <td data-label="{{ __('app.actions') }}" class="text-right"></td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <x-empty icon="router"
                     :title="__('app.empty.devices')"
                     :hint="$isPermanent ? __('app.empty.devices_hint') : __('app.empty.devices_hint_guest')"/>
        @endif

        @if ($isPermanent && $hasPrice)
            <p class="mt-4 rounded-xl bg-surface-2 px-4 py-3 text-sm text-muted">
                @lang('app.dash.connect_cost'):
                <span class="font-semibold text-ink">
                    {{ $connect > 0 ? number_format($connect / 100, 0, '', ' ').' '.__('app.ye') : __('app.dash.connect_free') }}
                </span>
            </p>
        @endif
    </section>
@endsection
