/**
 * Toast notifications — the replacement for toastr.
 *
 * Server-rendered messages arrive as <template data-toast data-tone="error">,
 * so nothing is injected as raw HTML from a Blade string: the text is read out
 * of the template's textContent and written back with textContent.
 */

// bg-surface, not bg-white: a toast on the dark theme has to be a raised card,
// and a hard white one would be the brightest thing on a near-black page.
const TONES = {
    error: 'border-danger/40 bg-surface text-ink',
    info: 'border-action/40 bg-surface text-ink',
};

const DOTS = {
    error: 'bg-danger',
    info: 'bg-action',
};

let region = null;

function ensureRegion() {
    if (region) return region;

    region = document.createElement('div');
    region.className = 'u-no-print pointer-events-none fixed inset-x-3 top-3 z-[70] flex flex-col items-end gap-2 sm:inset-x-auto sm:right-5 sm:top-5';
    // Assertive: these are the result of the action the subscriber just took.
    region.setAttribute('role', 'alert');
    region.setAttribute('aria-live', 'assertive');
    document.body.append(region);

    return region;
}

export function toast(message, tone = 'info', timeout = 6000) {
    if (!message) return;

    const node = document.createElement('div');
    node.className = `u-rise pointer-events-auto flex w-full max-w-sm items-start gap-3 rounded-card border-2 ${
        TONES[tone] ?? TONES.info
    } px-4 py-3 text-sm shadow-[var(--shadow-card)]`;

    const dot = document.createElement('span');
    dot.className = `mt-1.5 size-2 shrink-0 rounded-full ${DOTS[tone] ?? DOTS.info}`;

    const text = document.createElement('p');
    text.className = 'flex-1 leading-snug';
    text.textContent = message;

    const dismiss = document.createElement('button');
    dismiss.type = 'button';
    dismiss.className = 'shrink-0 rounded-full p-1 text-muted transition-colors hover:text-ink';
    dismiss.innerHTML =
        '<svg viewBox="0 0 20 20" class="size-4" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M5 5l10 10M15 5L5 15"/></svg>';

    const remove = () => node.remove();
    dismiss.addEventListener('click', remove);
    dismiss.setAttribute('aria-label', document.documentElement.dataset.closeLabel ?? 'Close');

    node.append(dot, text, dismiss);
    ensureRegion().append(node);

    if (timeout) setTimeout(remove, timeout);
}

export default function initToasts() {
    document.querySelectorAll('template[data-toast]').forEach((template) => {
        toast(template.content.textContent.trim(), template.dataset.tone);
    });
}
