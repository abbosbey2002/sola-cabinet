@extends('layouts.admin')
@section('title', __('admin.tc.show_title', ['id' => $change->id]).' - ')
@section('heading', __('admin.tc.show_title', ['id' => $change->id]))
@section('heading-icon', 'refresh')

@section('content')
    @php
        $som = function (mixed $value): ?string {
            if ($value === null || $value === '') {
                return null;
            }

            $amount = (float) $value;

            return number_format($amount, fmod($amount, 1.0) === 0.0 ? 0 : 2, ',', ' ').' '.__('admin.som');
        };
        $date = fn (?string $value): ?string => $value ? \Carbon\CarbonImmutable::parse($value, 'UTC')->format('d.m.Y') : null;
        $when = fn (?string $value): ?string => $value ? \Carbon\CarbonImmutable::parse($value, 'UTC')->format('d.m.Y H:i:s').' UTC' : null;
        $yesNo = fn (mixed $value): ?string => $value === null ? null : ((bool) $value ? __('admin.yes') : __('admin.no'));

        $sections = [
            'account' => [
                'account_id' => $change->account_id,
                'billing_login' => $pii->login($change->billing_login),
                'full_name' => $pii->name($change->full_name),
                'phone' => $pii->phone($change->phone),
                'abon_type' => $change->abon_type === null ? null : __('admin.abon_types.'.$change->abon_type),
                'is_legal_entity' => $yesNo($change->is_legal_entity),
                'account_status' => $change->account_status,
                'contract_date' => $date($change->contract_date),
            ] + ($pii->isUnmasked() ? ['address' => $change->address, 'email' => $change->email] : []),
            'money' => [
                'balance_before' => $som($change->balance_before),
                'balance_after' => $som($change->balance_after),
                'next_charge_date' => $date($change->next_charge_date),
                'pending_tariff_after' => $change->pending_tariff_after,
            ],
            'old_tariff' => [
                'tariff_id' => $change->old_tariff_id,
                'tariff_name' => $change->old_tariff_name,
                'tariff_price' => $som($change->old_tariff_price),
                'connected_at' => $date($change->old_tariff_connected_at),
            ],
            'new_tariff' => [
                'tariff_id' => $change->new_tariff_id,
                'tariff_name' => $change->new_tariff_name,
                'tariff_price' => $som($change->new_tariff_price),
                'speed' => $change->new_tariff_speed,
                'period' => $change->new_tariff_period,
            ],
            'request' => [
                'created_at' => $when($change->created_at),
                'timing' => $change->timing ? __('admin.timing.'.$change->timing) : null,
                'effective_date' => $date($change->effective_date),
                'billing_duration_ms' => $change->billing_duration_ms === null ? null : $change->billing_duration_ms.' ms',
                'locale' => $change->locale ? strtoupper($change->locale) : null,
                'device_type' => $change->device_type ? __('admin.segments.values.'.$change->device_type) : null,
            ] + ($canSeeTechnical ? [
                'ip' => $change->ip,
                'user_agent' => $change->user_agent,
                'session_id' => $change->session_id,
                'request_id' => $change->request_id,
            ] : []),
        ];
    @endphp

    <div class="mb-5 flex flex-wrap items-center gap-3">
        <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('admin.tariff-changes') }}" class="u-btn-ghost u-btn-sm">
            <x-icon name="chevron-left" size="size-4"/>@lang('admin.back')
        </a>
        @if ($canOpenAccount)
            <a href="{{ route('admin.accounts.show', $change->account_id) }}" class="u-btn-outline u-btn-sm">
                <x-icon name="user" size="size-4"/>@lang('admin.tc.open_account')
            </a>
        @endif
    </div>

    {{-- The verdict first: what happened, and billing's own words for it. --}}
    <section class="u-card mb-5" aria-labelledby="tc-result-title">
        <h2 id="tc-result-title" class="sr-only">@lang('admin.cols.result')</h2>
        <div class="flex flex-wrap items-center gap-3">
            <x-admin.result-pill :result="$change->result" class="!text-sm"/>
            <span class="text-base font-semibold text-ink">
                {{ $change->old_tariff_name ?? '—' }}
                <x-icon name="chevron-right" size="size-4" class="inline text-muted"/>
                {{ $change->new_tariff_name ?? '#'.$change->new_tariff_id }}
            </span>
        </div>

        @if ($change->denied_reason || $change->error_code || $change->error_message)
            <dl class="mt-4 grid gap-3 sm:grid-cols-3">
                @if ($change->denied_reason)
                    <div>
                        <dt class="u-label">@lang('admin.fields.denied_reason')</dt>
                        <dd class="mt-1 text-ink">{{ __('admin.denied.'.$change->denied_reason) }}</dd>
                    </div>
                @endif
                @if ($change->error_code)
                    <div>
                        <dt class="u-label">@lang('admin.fields.error_code')</dt>
                        <dd class="mt-1 tabular-nums text-ink">{{ $change->error_code }}</dd>
                    </div>
                @endif
                @if ($change->error_message)
                    <div class="sm:col-span-2">
                        <dt class="u-label">@lang('admin.fields.error_message')</dt>
                        <dd class="mt-1 text-ink">{{ $change->error_message }}</dd>
                    </div>
                @endif
            </dl>
        @endif
    </section>

    <div class="grid gap-5 lg:grid-cols-2">
        @foreach ($sections as $section => $fields)
            <section class="u-card" aria-labelledby="tc-{{ $section }}">
                <h2 id="tc-{{ $section }}" class="text-lg font-bold text-ink">{{ __('admin.tc.sections.'.$section) }}</h2>
                <dl class="mt-3 divide-y divide-line">
                    @foreach ($fields as $field => $value)
                        <div class="grid grid-cols-[minmax(0,2fr)_minmax(0,3fr)] gap-3 py-2.5">
                            <dt class="text-sm text-muted">{{ __('admin.fields.'.$field) }}</dt>
                            <dd class="break-words text-sm text-ink">{{ $value ?? '—' }}</dd>
                        </div>
                    @endforeach
                </dl>
            </section>
        @endforeach
    </div>

    @unless ($pii->isUnmasked())
        <p class="mt-5 flex items-center gap-1.5 text-sm text-muted">
            <x-icon name="shield" size="size-4"/>@lang('admin.masked_note')
        </p>
    @endunless

    @if ($snapshot)
        <details class="u-card mt-5">
            <summary class="cursor-pointer text-lg font-bold text-ink">@lang('admin.tc.snapshot')</summary>
            <p class="mt-2 text-sm text-muted">@lang('admin.tc.snapshot_hint')</p>
            <pre class="u-scroll mt-3 max-h-[28rem] overflow-auto rounded-xl bg-surface-2 p-4 text-xs leading-relaxed text-ink">{{ $snapshot }}</pre>
        </details>
    @endif
@endsection
