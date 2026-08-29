/**
 * Space-separated thousands as the subscriber types an amount — "10 000"
 * reads as a sum at a glance the way a bare "10000" never does. The field
 * keeps its name and submits the spaced string as-is; TopUpRequest strips
 * the spaces server-side before validating, so the form works the same
 * with JS on or off.
 *
 * Markup contract:
 *   <input type="text" inputmode="numeric" data-amount-mask>
 */

function format(digits) {
    return digits.replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
}

export default function initAmountMask() {
    const field = document.querySelector('[data-amount-mask]');
    if (!field) return;

    const apply = () => {
        field.value = format(field.value.replace(/\D/g, ''));
        // Same tradeoff as the phone mask: always landing the caret at the
        // end sidesteps fighting the browser over cursor position every
        // time a space is inserted or removed mid-string.
        field.setSelectionRange(field.value.length, field.value.length);
    };

    field.addEventListener('input', apply);

    // Only reformat what the server redisplayed if it was actually a
    // number (or blank). A genuinely invalid value old('amount') redisplays
    // after a "must be numeric" error — e.g. "abc" — must stay exactly as
    // the subscriber typed it: stripping every non-digit from it here would
    // silently collapse it to an empty box, leaving them staring at the
    // error message with no way to tell what they need to fix. Editing it
    // at all hands control straight back to the input listener above.
    if (/^[\d\s]*$/.test(field.value)) {
        apply();
    }
}
