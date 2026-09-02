/**
 * OTP field on the SMS verify screen.
 *
 * Markup: <input data-otp maxlength="4">
 *
 * Pasted codes arrive with spaces or "Kod: 1234". Four digits is a complete
 * code, so the form submits itself — the subscriber is staring at this
 * field waiting for the SMS, and an extra tap is just delay.
 */

function digits(raw) {
    return String(raw).replace(/\D/g, '').slice(0, 4);
}

export default function initOtp() {
    const field = document.querySelector('[data-otp]');
    if (!field) return;

    const form = field.closest('form');
    let submitting = false;

    const fill = (raw) => {
        const value = digits(raw);
        if (field.value !== value) field.value = value;

        if (value.length === 4 && form && !submitting) {
            submitting = true;
            form.requestSubmit();
        }
    };

    field.addEventListener('input', () => fill(field.value));

    field.addEventListener('paste', (event) => {
        event.preventDefault();
        fill(event.clipboardData?.getData('text') ?? '');
    });
}
