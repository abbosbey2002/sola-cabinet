/**
 * Quick-amount chips on the top-up form — a tap instead of typing a round
 * figure. Sets the same field amount-mask.js owns and dispatches its own
 * `input` event so that module's digit-grouping runs exactly as it would
 * for a typed value; nothing here talks to the server, TopUpRequest
 * validates whatever ends up in the field the same way either way.
 *
 * Markup contract:
 *   <input data-amount-mask>
 *   <button type="button" data-amount-preset="10000" aria-pressed="false">…</button>
 */
export default function initAmountPresets() {
    const field = document.querySelector('[data-amount-mask]');
    const chips = document.querySelectorAll('[data-amount-preset]');
    if (!field || chips.length === 0) return;

    const syncPressed = () => {
        const current = field.value.replace(/\D/g, '');
        chips.forEach((chip) => {
            chip.setAttribute('aria-pressed', String(chip.dataset.amountPreset === current));
        });
    };

    chips.forEach((chip) => {
        chip.addEventListener('click', () => {
            field.value = chip.dataset.amountPreset;
            field.dispatchEvent(new Event('input', { bubbles: true }));
            field.focus();
        });
    });

    // Typing a preset's exact figure by hand should highlight it too, and
    // typing over a selected preset should drop the highlight.
    field.addEventListener('input', syncPressed);
    syncPressed();
}
