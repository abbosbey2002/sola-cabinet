/**
 * Copy-to-clipboard for the contract number on x-pay-card.
 *
 * Markup contract:
 *   <button data-copy="998xxxxxxxx" data-copy-label="Copy" data-copy-done="Copied">
 *     <span data-copy-text>Copy</span>
 *   </button>
 *
 * The button's own label swaps to the "done" word for a moment instead of
 * raising a toast — the confirmation sits right where the subscriber is
 * already looking, on the control they just pressed.
 */

// navigator.clipboard needs a secure context; local dev over plain http and
// older in-app browsers don't have it, and a subscriber retyping a contract
// number by hand is exactly the failure this feature exists to prevent.
async function copyText(text) {
    if (navigator.clipboard) {
        try {
            await navigator.clipboard.writeText(text);
            return true;
        } catch {
            // Falls through to the textarea fallback below.
        }
    }

    const area = document.createElement('textarea');
    area.value = text;
    area.style.position = 'fixed';
    area.style.opacity = '0';
    document.body.append(area);
    area.select();

    let copied = false;
    try {
        copied = document.execCommand('copy');
    } catch {
        copied = false;
    }
    area.remove();

    return copied;
}

export default function initCopy() {
    document.addEventListener('click', (event) => {
        const button = event.target.closest('[data-copy]');
        if (!button) return;

        const label = button.querySelector('[data-copy-text]');
        if (button.dataset.copying) return;

        copyText(button.dataset.copy).then((copied) => {
            if (!copied || !label) return;

            const original = label.textContent;
            button.dataset.copying = '1';
            label.textContent = button.dataset.copyDone ?? original;

            setTimeout(() => {
                label.textContent = original;
                delete button.dataset.copying;
            }, 1600);
        });
    });
}
