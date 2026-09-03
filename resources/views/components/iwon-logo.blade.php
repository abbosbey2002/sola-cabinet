@props(['height' => 'h-8'])

{{--
    iWon lockup, one per ground — same three-state switch as <x-logo>.
    iwon-on-light has black wordmark (for a light surface); iwon-on-dark has
    white wordmark (for a dark surface). The purple mark is the same in both.
--}}

<span {{ $attributes->merge(['class' => 'u-iwon-logo']) }}>
    <img src="{{ asset('img/logos/iwon-on-light.svg') }}" alt="iWon" width="160" height="56"
         class="u-iwon-on-light {{ $height }} w-auto">
    <img src="{{ asset('img/logos/iwon-on-dark.svg') }}" alt="iWon" width="260" height="91"
         class="u-iwon-on-dark {{ $height }} w-auto">
</span>
