/**
 * Click-to-open disclosures: the account switcher and the language menu.
 *
 * Markup contract:
 *   <div data-disclosure>
 *     <button data-disclosure-trigger aria-expanded="false">…</button>
 *     <div data-disclosure-panel hidden>…</div>
 *   </div>
 *
 * Only one panel is open at a time. Escape and outside-click close it, and the
 * trigger keeps aria-expanded honest for screen readers.
 */

const OPEN = 'is-open';

let openPanel = null;

function close(root) {
    if (!root) return;

    root.classList.remove(OPEN);
    root.querySelector('[data-disclosure-trigger]')?.setAttribute('aria-expanded', 'false');
    root.querySelector('[data-disclosure-panel]')?.setAttribute('hidden', '');

    if (openPanel === root) openPanel = null;
}

function open(root) {
    close(openPanel);

    root.classList.add(OPEN);
    root.querySelector('[data-disclosure-trigger]')?.setAttribute('aria-expanded', 'true');
    root.querySelector('[data-disclosure-panel]')?.removeAttribute('hidden');

    openPanel = root;
}

export default function initDisclosures() {
    document.addEventListener('click', (event) => {
        const trigger = event.target.closest('[data-disclosure-trigger]');

        if (trigger) {
            event.preventDefault();

            const root = trigger.closest('[data-disclosure]');
            root.classList.contains(OPEN) ? close(root) : open(root);

            return;
        }

        // A click anywhere outside the open panel dismisses it.
        if (openPanel && !event.target.closest('[data-disclosure]')) {
            close(openPanel);
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape' || !openPanel) return;

        const trigger = openPanel.querySelector('[data-disclosure-trigger]');
        close(openPanel);
        trigger?.focus();
    });
}
