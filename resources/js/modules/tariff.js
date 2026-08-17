/**
 * Tariff switching (spec §3.2, §13).
 *
 * The subscriber picks a tariff from the radio list, presses "Tarifni
 * o'zgartirish" and confirms. Permanent subscribers additionally choose when it
 * starts; everyone else can only start it now, so they go straight to the
 * confirm.
 *
 * Markup contract:
 *   <form data-tariff-flow data-choose-timing="1" method="post" action="…">
 *     @csrf
 *     <input type="hidden" name="timing" data-tariff-timing-field value="month">
 *     <input type="radio" name="tariff" value="7" data-tariff-option data-tariff-name="Smart 50">
 *     <button type="submit" data-tariff-connect disabled>…</button>
 *   </form>
 *   plus [data-modal="tariff-timing"] with [data-tariff-timing="now|month"].
 *
 * The action is a POST form, not a link: connecting a tariff spends the
 * subscriber's money, so it must carry a CSRF token. Without JavaScript the
 * form still submits with the hidden field's server-rendered default — no
 * dialog, but no dead button either.
 */

import { openModal, closeModal } from './modal.js';

export default function initTariff() {
    const flow = document.querySelector('[data-tariff-flow]');
    if (!flow) return;

    const chooseTiming = flow.dataset.chooseTiming === '1';
    const button = flow.querySelector('[data-tariff-connect]');
    const timingField = flow.querySelector('[data-tariff-timing-field]');
    // Both dialogs carry a name slot; only one is ever shown, so fill both.
    const nameSlots = document.querySelectorAll('[data-tariff-name-slot]');

    let tariffName = '';

    const chosen = () => flow.querySelector('[data-tariff-option]:checked');

    // The button stays disabled until something is actually selected — a
    // confirmation dialog for "no tariff" would be a dead end.
    flow.addEventListener('change', (event) => {
        const option = event.target.closest('[data-tariff-option]');
        if (!option) return;

        tariffName = option.dataset.tariffName ?? '';

        if (button) button.disabled = false;
    });

    // One-shot: set immediately before the confirmed submit and cleared as the
    // submit passes through. Left set, it would be a permanent bypass of the
    // dialog rather than a token — and it would survive a bfcache restore
    // together with the still-checked radio, so the next press of the button
    // would post a tariff change with no confirmation at all.
    let confirmed = false;
    let submitting = false;

    const go = (timing) => {
        if (submitting || !chosen()) return;

        if (timingField) timingField.value = timing;

        confirmed = true;
        flow.requestSubmit();
    };

    // The submit button opens the dialog instead of posting; the dialog's own
    // choice is what finally submits the form.
    flow.addEventListener('submit', (event) => {
        if (! confirmed) {
            event.preventDefault();

            if (!chosen()) return;

            nameSlots.forEach((slot) => {
                slot.textContent = tariffName;
            });

            openModal(chooseTiming ? 'tariff-timing' : 'tariff-confirm');

            return;
        }

        // Let this one through, then close the door behind it. The button is
        // disabled for the whole in-flight window — this POST spends money and
        // SolaClient neither retries it nor sends an idempotency key, so a
        // second one is a second charge.
        confirmed = false;
        submitting = true;
        if (button) button.disabled = true;
    });

    document.addEventListener('click', (event) => {
        const timing = event.target.closest('[data-tariff-timing]');

        if (timing) {
            event.preventDefault();
            closeModal();
            go(timing.dataset.tariffTiming);

            return;
        }

        if (event.target.closest('[data-tariff-accept]')) {
            event.preventDefault();
            closeModal();
            go('now');
        }
    });
}
