/**
 * Modal dialogs — the month picker and the tariff confirmation.
 *
 * Markup contract:
 *   <button data-modal-open="month">…</button>
 *   <div data-modal="month" hidden role="dialog" aria-modal="true">
 *     <div data-modal-overlay></div>
 *     <div data-modal-panel>… <button data-modal-close>…</button></div>
 *   </div>
 *
 * Focus moves into the dialog on open, is trapped inside it, and returns to the
 * element that opened it on close.
 */

const FOCUSABLE =
    'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])';

let active = null;
let lastFocused = null;

function focusables(dialog) {
    return [...dialog.querySelectorAll(FOCUSABLE)].filter((el) => el.offsetParent !== null);
}

export function openModal(name) {
    const dialog = document.querySelector(`[data-modal="${name}"]`);
    if (!dialog) return;

    lastFocused = document.activeElement;
    active = dialog;

    dialog.removeAttribute('hidden');
    // The scrollbar disappearing under a fixed overlay shifts the page; pin it.
    document.body.style.overflow = 'hidden';

    // Prefer the first real control over the close button, so the month picker
    // lands on the <select> rather than on "×".
    const targets = focusables(dialog);
    (targets.find((el) => !el.hasAttribute('data-modal-close')) ?? targets[0])?.focus();
}

export function closeModal() {
    if (!active) return;

    active.setAttribute('hidden', '');
    active = null;
    document.body.style.overflow = '';

    lastFocused?.focus();
    lastFocused = null;
}

export default function initModals() {
    document.addEventListener('click', (event) => {
        const opener = event.target.closest('[data-modal-open]');

        if (opener) {
            event.preventDefault();
            openModal(opener.dataset.modalOpen);

            return;
        }

        if (event.target.closest('[data-modal-close]') || event.target.matches('[data-modal-overlay]')) {
            event.preventDefault();
            closeModal();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (!active) return;

        if (event.key === 'Escape') {
            closeModal();

            return;
        }

        if (event.key !== 'Tab') return;

        const targets = focusables(active);
        if (targets.length === 0) return;

        const first = targets[0];
        const last = targets[targets.length - 1];

        if (event.shiftKey && document.activeElement === first) {
            event.preventDefault();
            last.focus();
        } else if (!event.shiftKey && document.activeElement === last) {
            event.preventDefault();
            first.focus();
        }
    });
}
