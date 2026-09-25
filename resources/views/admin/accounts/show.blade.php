@extends('layouts.admin')
@section('title', __('admin.accounts.show_title', ['id' => $accountId]).' - ')
@section('heading', __('admin.accounts.show_title', ['id' => $accountId]))
@section('heading-icon', 'user')

@section('content')
    @php
        $when = fn (string $timestamp): string => \Carbon\CarbonImmutable::parse($timestamp, 'UTC')->format('d.m.Y H:i:s');
        $eventLabel = fn (string $event): string => __('admin.events.'.str_replace('.', '_', $event));

        // Only the fields worth a glance; the rest of an event's meta is
        // shown as-is, key: value.
        $details = function (object $event): string {
            $meta = json_decode((string) ($event->meta ?? ''), true);
            $parts = [];

            if (is_array($meta)) {
                foreach ($meta as $key => $value) {
                    if ($value === null || $value === '' || ! is_scalar($value)) {
                        continue;
                    }

                    $parts[] = $key.': '.(is_bool($value) ? ($value ? 'true' : 'false') : $value);
                }
            }

            if ($event->error_code) {
                array_unshift($parts, __('admin.tc.code', ['code' => $event->error_code]).($event->error_message ? ' — '.$event->error_message : ''));
            }

            return implode(' · ', $parts);
        };
    @endphp

    <div class="mb-5">
        <a href="{{ route('admin.accounts') }}" class="u-btn-ghost u-btn-sm">
            <x-icon name="chevron-left" size="size-4"/>@lang('admin.back')
        </a>
    </div>

    @if ($latest)
        <section class="u-card mb-5" aria-labelledby="account-who">
            <h2 id="account-who" class="sr-only">@lang('admin.cols.subscriber')</h2>
            <dl class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <dt class="u-label">@lang('admin.fields.full_name')</dt>
                    <dd class="mt-1 font-semibold text-ink">{{ $pii->name($latest->full_name) ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="u-label">@lang('admin.fields.phone')</dt>
                    <dd class="mt-1 tabular-nums text-ink">{{ $pii->phone($latest->phone) ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="u-label">@lang('admin.fields.billing_login')</dt>
                    <dd class="mt-1 text-ink">{{ $pii->login($latest->billing_login) ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="u-label">@lang('admin.fields.abon_type')</dt>
                    <dd class="mt-1 text-ink">{{ $latest->abon_type === null ? '—' : __('admin.abon_types.'.$latest->abon_type) }}</dd>
                </div>
            </dl>
            <p class="mt-4 text-sm text-muted">@lang('admin.accounts.as_of', ['when' => $when($latest->occurred_at)])</p>
        </section>
    @endif

    <section class="u-card mb-5" aria-labelledby="account-changes">
        <h2 id="account-changes" class="text-lg font-bold text-ink">@lang('admin.accounts.changes_title')</h2>

        @if ($tariffChanges->isEmpty())
            <x-empty icon="refresh" :title="__('admin.accounts.no_changes')"/>
        @else
            <div class="u-table-wrap u-scroll mt-4">
                <table class="u-table u-table-cards">
                    <thead>
                        <tr>
                            <th scope="col">@lang('admin.cols.when_utc')</th>
                            <th scope="col">@lang('admin.cols.change')</th>
                            <th scope="col">@lang('admin.cols.result')</th>
                            <th scope="col"><span class="sr-only">@lang('admin.tc.open')</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($tariffChanges as $change)
                            <tr>
                                <td data-label="{{ __('admin.cols.when_utc') }}" class="whitespace-nowrap tabular-nums">{{ $when($change->created_at) }}</td>
                                <td data-label="{{ __('admin.cols.change') }}">
                                    <span class="whitespace-nowrap text-muted">{{ $change->old_tariff_name ?? '—' }}</span>
                                    <x-icon name="chevron-right" size="size-4" class="inline text-muted"/>
                                    <span class="whitespace-nowrap font-semibold text-ink">{{ $change->new_tariff_name ?? '#'.$change->new_tariff_id }}</span>
                                </td>
                                <td data-label="{{ __('admin.cols.result') }}"><x-admin.result-pill :result="$change->result"/></td>
                                <td class="text-right">
                                    <a href="{{ route('admin.tariff-changes.show', $change->id) }}" class="u-btn-ghost u-btn-sm"
                                       aria-label="{{ __('admin.tc.open_n', ['id' => $change->id]) }}">
                                        <x-icon name="view" size="size-4"/>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    <section class="u-card" aria-labelledby="account-events">
        <h2 id="account-events" class="text-lg font-bold text-ink">@lang('admin.accounts.events_title')</h2>

        @if ($events->isEmpty())
            <x-empty icon="clock" :title="__('admin.accounts.no_events')"/>
        @else
            <div class="u-table-wrap u-scroll mt-4">
                <table class="u-table u-table-cards">
                    <thead>
                        <tr>
                            <th scope="col">@lang('admin.cols.when_utc')</th>
                            <th scope="col">@lang('admin.cols.action')</th>
                            <th scope="col">@lang('admin.cols.outcome')</th>
                            <th scope="col">@lang('admin.cols.details')</th>
                            <th scope="col">@lang('admin.cols.device')</th>
                            @if ($canSeeTechnical)
                                <th scope="col">IP</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($events as $event)
                            <tr>
                                <td data-label="{{ __('admin.cols.when_utc') }}" class="whitespace-nowrap tabular-nums">{{ $when($event->occurred_at) }}</td>
                                <td data-label="{{ __('admin.cols.action') }}" class="font-semibold text-ink">{{ $eventLabel($event->event) }}</td>
                                <td data-label="{{ __('admin.cols.outcome') }}"><x-admin.outcome-pill :outcome="$event->outcome"/></td>
                                <td data-label="{{ __('admin.cols.details') }}" class="max-w-sm">
                                    <span class="line-clamp-2 break-words text-sm text-muted" title="{{ $details($event) }}">{{ $details($event) ?: '—' }}</span>
                                </td>
                                <td data-label="{{ __('admin.cols.device') }}" class="text-sm text-muted">
                                    {{ collect([$event->browser, $event->os, $event->device_model])->filter()->implode(' · ') ?: '—' }}
                                </td>
                                @if ($canSeeTechnical)
                                    <td data-label="IP" class="whitespace-nowrap text-sm tabular-nums text-muted">{{ $event->ip ?? '—' }}</td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <x-admin.pager :paginator="$events"/>
        @endif
    </section>
@endsection
