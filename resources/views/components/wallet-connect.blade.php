@if (config('web3.active'))
    <button type="button"
        class="u-wallet-pill hidden min-h-[2.75rem] items-center gap-2 rounded-full px-3.5 text-xs font-semibold sm:inline-flex"
        disabled aria-disabled="true">
        <x-icon name="wallet" size="size-4"/>
        <span>@lang('app.web3.connect_wallet')</span>
    </button>
@endif
