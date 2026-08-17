/**
 * Form submissions that replace one block instead of the whole page (spec §1.1).
 *
 * Markup contract:
 *   <form data-ajax-form data-ajax-target="#traffic-result" method="post" …>
 *   <div id="traffic-result" data-ajax-region>…</div>
 *
 * The server answers with the block's markup, not JSON, so the rendering stays
 * in Blade and there is exactly one template per block. If the request fails
 * the form is left to submit normally on the next attempt — a broken fetch
 * must not strand the subscriber on a stale block.
 */

import { mountTables } from './table.js';
import { toast } from './toast.js';

const BUSY = 'is-busy';

async function submit(form) {
    const target = document.querySelector(form.dataset.ajaxTarget);
    if (!target) return false;

    const button = form.querySelector('[type="submit"]');

    target.classList.add(BUSY);
    target.setAttribute('aria-busy', 'true');
    if (button) button.disabled = true;

    try {
        const response = await fetch(form.action, {
            method: form.method || 'post',
            body: new FormData(form),
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
            },
            credentials: 'same-origin',
        });

        if (response.status === 422) {
            const { message } = await response.json().catch(() => ({}));
            toast(message ?? document.documentElement.dataset.errorLabel, 'error');

            return true;
        }

        if (!response.ok) throw new Error(`HTTP ${response.status}`);

        target.innerHTML = await response.text();
        // The replaced markup brings its own tables with it.
        mountTables(target);

        return true;
    } catch {
        toast(document.documentElement.dataset.errorLabel, 'error');

        return true;
    } finally {
        target.classList.remove(BUSY);
        target.removeAttribute('aria-busy');
        if (button) button.disabled = false;
    }
}

export default function initAjaxForms() {
    document.addEventListener('submit', (event) => {
        const form = event.target.closest('[data-ajax-form]');
        if (!form) return;

        event.preventDefault();
        submit(form);
    });
}
