@props(['height' => 'h-9'])

{{--
    Two lockups, one per ground. The mark is never set on a plate of our own:
    a coloured rectangle behind a logo is a workaround for shipping the wrong
    file, and it reads as a sticker on a page that has no other plates.

    logo-dark.png sets "SOLA" and the Wi-Fi Operator line in black — it is the
    lockup drawn FOR a light ground. logo-wordmark.png sets the same word in
    white, for a dark one. CSS picks between them in .u-logo below, following
    the same three-state rule the rest of the stylesheet uses, so an explicit
    theme choice beats the system preference here exactly as it does elsewhere.

    Both carry the real alt text: only one is ever displayed, and display:none
    keeps the other out of the accessibility tree.

    Each image is sized in the markup so the row never reflows once they load.
    The two lockups have different proportions — the light one carries the
    Wi-Fi Operator line — so the row is a shade wider on a light theme. It is
    a flex row; nothing else moves.
--}}

<span {{ $attributes->merge(['class' => 'u-logo']) }}>
    <img src="/img/logo-dark.png" alt="{{ config('app.name') }}" width="341" height="91"
         class="u-logo-on-light {{ $height }} w-auto">
    <img src="/img/logo-wordmark.png" alt="{{ config('app.name') }}" width="166" height="53"
         class="u-logo-on-dark {{ $height }} w-auto">
</span>
