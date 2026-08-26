/**
 * Uzbek phone mask for the login field.
 *
 * Formats digits as the subscriber types into "+998 XX XXX XX XX" and keeps
 * only the 9 digits that follow the country code — no other country's
 * numbers fit through this field.
 *
 * Markup contract:
 *   <input data-phone-mask value="+998">
 */

const PREFIX = '+998';
const SUBSCRIBER_DIGITS = 9;

function format(digits) {
    let value = PREFIX;

    if (digits.length) value += ' ' + digits.slice(0, 2);
    if (digits.length > 2) value += ' ' + digits.slice(2, 5);
    if (digits.length > 5) value += ' ' + digits.slice(5, 7);
    if (digits.length > 7) value += ' ' + digits.slice(7, 9);

    return value;
}

// Pasted numbers arrive in every shape a subscriber might copy them in:
// with or without "+", with or without the "998" country code, with or
// without spaces. Stripping to bare digits and peeling off a leading "998"
// (if that's actually the country code and not just the tail end of the
// subscriber still deleting it) handles all of them the same way.
function subscriberDigitsOf(raw) {
    let digits = raw.replace(/\D/g, '');

    if (digits.startsWith('998')) {
        digits = digits.slice(3);
    } else if ('998'.startsWith(digits)) {
        // Backspacing into the "998" itself, not a different number — treat
        // it as the subscriber having typed nothing yet, not as those
        // leftover digits.
        digits = '';
    }

    return digits.slice(0, SUBSCRIBER_DIGITS);
}

export default function initPhoneMask() {
    const field = document.querySelector('[data-phone-mask]');
    if (!field) return;

    const apply = () => {
        field.value = format(subscriberDigitsOf(field.value));
        // The field is short enough that always landing the caret at the
        // end reads as normal typing, and it sidesteps fighting the browser
        // over cursor position every time a space is inserted or removed.
        field.setSelectionRange(field.value.length, field.value.length);
    };

    field.addEventListener('input', apply);
    field.addEventListener('focus', apply);

    // Normalize whatever the server rendered (default "+998", or the
    // subscriber's own input redisplayed after a validation error).
    apply();
}
