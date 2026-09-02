<section {{ $attributes->merge(['class' => 'u-card u-card-web3 u-rise p-6 sm:p-8']) }}
    aria-labelledby="web3-panel-title">
    <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
        <div class="min-w-0 flex-1">
            <h2 id="web3-panel-title" class="text-xl font-semibold text-ink">@lang('app.web3.panel_title')</h2>
            <p class="mt-2 max-w-2xl text-sm text-muted">@lang('app.web3.panel_intro')</p>

            <div class="mt-4 flex flex-wrap gap-2" role="list">
                @foreach (['usdt_trc', 'usdt_bep', 'ton'] as $chip)
                    <span class="inline-flex min-h-[2.25rem] items-center rounded-full border px-3.5 text-xs font-semibold"
                        style="border-color: var(--c-web3-line); background: var(--c-surface); color: var(--c-web3)"
                        role="listitem">@lang('app.web3.'.$chip)</span>
                @endforeach
            </div>
        </div>

        <button type="button" class="u-btn-web3 shrink-0" disabled aria-disabled="true">
            <x-icon name="wallet" size="size-5"/>@lang('app.web3.connect_wallet')
        </button>
    </div>
</section>
