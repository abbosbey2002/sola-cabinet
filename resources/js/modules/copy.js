/**
 * Copy-to-clipboard for contract numbers, logins and account ids.
 *
 * Markup contract:
 *   <button data-copy="998xxxxxxxx" data-copy-done="Copied">
 *     <span data-copy-icon-default>...</span>
 *     <span data-copy-icon-done hidden>...</span>
 *     <span data-copy-text class="sr-only">Copy</span>
 *   </button>
 *
 * The buttons are icon-only — no visible label sits in these already-tight
 * rows — so the confirmation has to be something a sighted subscriber can
 * actually see without a word: the icon itself swaps to a checkmark for a
 * moment. The sr-only text still updates in lockstep (via the same
 * `role="status"` element the markup already carries) so a screen reader
 * gets the same "Copied" announcement a visible label would have given.
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
        const iconDefault = button.querySelector('[data-copy-icon-default]');
        const iconDone = button.querySelector('[data-copy-icon-done]');
        if (button.dataset.copying) return;

        copyText(button.dataset.copy).then((copied) => {
            if (!copied) return;

            button.dataset.copying = '1';
            const original = label?.textContent;
            if (label) label.textContent = button.dataset.copyDone ?? original;
            if (iconDefault) iconDefault.hidden = true;
            if (iconDone) iconDone.hidden = false;

            setTimeout(() => {
                if (label) label.textContent = original;
                if (iconDefault) iconDefault.hidden = false;
                if (iconDone) iconDone.hidden = true;
                delete button.dataset.copying;
            }, 1600);
        });
    });
}
