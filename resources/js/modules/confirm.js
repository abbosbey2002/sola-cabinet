/**
 * Destructive-action confirmation.
 *
 * Replaces the native confirm() the old templates used on "add device" and
 * "release device": window.confirm blocks the main thread, cannot be styled,
 * and on Android reads out the origin before the question.
 *
 * Markup contract — any link or button may carry:
 *   data-confirm="Question shown to the subscriber"
 *   data-confirm-action="Label for the confirming button"   (optional)
 *   data-confirm-tone="danger"                              (optional)
 */

import { openModal, closeModal } from './modal.js';

export default function initConfirm() {
    const dialog = document.querySelector('[data-modal="confirm"]');
    if (!dialog) return;

    const question = dialog.querySelector('[data-confirm-question]');
    const accept = dialog.querySelector('[data-confirm-accept]');

    let pending = null;

    document.addEventListener('click', (event) => {
        const trigger = event.target.closest('[data-confirm]');
        if (!trigger) return;

        event.preventDefault();
        pending = trigger;

        question.textContent = trigger.dataset.confirm;

        if (trigger.dataset.confirmAction) {
            accept.textContent = trigger.dataset.confirmAction;
        }

        accept.className =
            trigger.dataset.confirmTone === 'danger'
                ? 'u-btn-danger u-btn-danger-solid flex-1'
                : 'u-btn-primary flex-1';

        openModal('confirm');
    });

    accept.addEventListener('click', () => {
        const target = pending;
        // Cleared before anything else, so a second click on the accept button
        // has nothing left to act on. The confirmed actions behind this dialog
        // add a billed device permit and release one.
        pending = null;
        closeModal();

        if (!target) return;

        if (target.tagName === 'A') {
            window.location.href = target.href;
        } else if (target.form) {
            // requestSubmit, not submit: it fires the submit event, so a form
            // that later grows validation or an ajax handler is not silently
            // bypassed. Every confirmed action here is a POST carrying @csrf.
            target.form.requestSubmit();
        }

        // Locked AFTER the submit is initiated, never before: a disabled
        // control must not be able to interfere with the submission that is
        // already on its way. Belt and braces next to the null above — the
        // trigger stays visible behind the closing dialog while the browser
        // navigates, and this is the in-flight convention ajax.js follows.
        accept.disabled = true;
        if (target.tagName === 'BUTTON') target.disabled = true;
    });
}
