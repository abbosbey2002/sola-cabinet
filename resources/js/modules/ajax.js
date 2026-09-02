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
 *
 * 12s timeout → in-region retry (the previous table stays). A spinner that
 * never ends on a dead metro tunnel is a bug, not a loading state.
 */

import { mountTables } from './table.js';
import { toast } from './toast.js';
import { setButtonLoading } from './motion.js';

const BUSY = 'is-busy';
const FAIL = 'u-ajax-fail';
const TIMEOUT_MS = 12_000;

function clearFail(target) {
    target.querySelector(`.${FAIL}`)?.remove();
}

function showRetry(target, form) {
    clearFail(target);

    const root = document.documentElement;
    const bar = document.createElement('div');
    bar.className = FAIL;
    bar.setAttribute('role', 'alert');

    const text = document.createElement('p');
    text.className = 'min-w-0 flex-1 text-sm font-semibold leading-snug text-ink';
    text.textContent = root.dataset.timeoutLabel ?? root.dataset.errorLabel;

    const retry = document.createElement('button');
    retry.type = 'button';
    retry.className = 'u-btn-primary u-btn-sm shrink-0';
    retry.textContent = root.dataset.retryLabel ?? 'Retry';
    retry.addEventListener('click', () => form.requestSubmit());

    bar.append(text, retry);
    target.prepend(bar);
}

async function submit(form) {
    const target = document.querySelector(form.dataset.ajaxTarget);
    if (!target) return false;

    const button = form.querySelector('[type="submit"]');
    const controller = new AbortController();
    const timer = window.setTimeout(() => controller.abort(), TIMEOUT_MS);

    clearFail(target);
    target.classList.add(BUSY);
    target.setAttribute('aria-busy', 'true');
    setButtonLoading(button, true);
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
            signal: controller.signal,
        });

        if (response.status === 422) {
            const { message } = await response.json().catch(() => ({}));
            toast(message ?? document.documentElement.dataset.errorLabel, 'error');

            return true;
        }

        if (!response.ok) throw new Error(`HTTP ${response.status}`);

        target.innerHTML = await response.text();
        mountTables(target);

        return true;
    } catch (error) {
        const timedOut = error?.name === 'AbortError';

        if (timedOut) {
            showRetry(target, form);
        } else {
            toast(document.documentElement.dataset.errorLabel, 'error');
        }

        return true;
    } finally {
        window.clearTimeout(timer);
        target.classList.remove(BUSY);
        target.removeAttribute('aria-busy');
        setButtonLoading(button, false);
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
