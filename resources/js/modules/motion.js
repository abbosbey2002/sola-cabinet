/**
 * Motion that only runs when something is actually happening.
 *
 * Count-up is the Home signature (balance / days left). The green progress
 * strip and button spinner exist only for in-flight navigation or POSTs —
 * a spinner on a finished server-rendered page is a bug.
 *
 * Markup:
 *   <span data-count-up="125000">125 000</span>
 *   <div data-progress hidden class="u-progress">  (once per layout)
 *   submit buttons pick up .is-loading automatically
 */

const REDUCE = '(prefers-reduced-motion: reduce)';

export function prefersReducedMotion() {
    return window.matchMedia(REDUCE).matches;
}

export function setButtonLoading(button, on) {
    if (!button) return;

    button.classList.toggle('is-loading', on);
    button.toggleAttribute('aria-busy', on);
}

function formatFigure(value) {
    const rounded = Math.round(value);
    const negative = rounded < 0;
    const grouped = String(Math.abs(rounded)).replace(/\B(?=(\d{3})+(?!\d))/g, ' ');

    return (negative ? '−' : '') + grouped;
}

function easeOutCubic(t) {
    return 1 - (1 - t) ** 3;
}

function animateCount(el) {
    const target = Number(el.dataset.countUp);
    if (!Number.isFinite(target)) return;

    if (prefersReducedMotion()) {
        el.textContent = formatFigure(target);

        return;
    }

    const duration = 560;
    const delay = Number(el.dataset.countDelay ?? 180);
    const origin = performance.now();

    el.textContent = formatFigure(0);

    const tick = (now) => {
        const elapsed = now - origin - delay;
        if (elapsed < 0) {
            requestAnimationFrame(tick);

            return;
        }

        const t = Math.min(1, elapsed / duration);
        el.textContent = formatFigure(target * easeOutCubic(t));

        if (t < 1) requestAnimationFrame(tick);
        else el.textContent = formatFigure(target);
    };

    requestAnimationFrame(tick);
}

let progressHide = 0;

function progressEl() {
    return document.querySelector('[data-progress]');
}

export function startProgress() {
    const bar = progressEl();
    if (!bar || prefersReducedMotion()) return;

    window.clearTimeout(progressHide);
    bar.classList.remove('is-done');
    bar.removeAttribute('hidden');
    // Force a starting frame so a second click retriggers the keyframes.
    bar.classList.remove('is-on');
    void bar.offsetWidth;
    bar.classList.add('is-on');
}

export function finishProgress() {
    const bar = progressEl();
    if (!bar) return;

    bar.classList.remove('is-on');
    bar.classList.add('is-done');
    progressHide = window.setTimeout(() => {
        bar.classList.remove('is-done');
        bar.setAttribute('hidden', '');
    }, 280);
}

function isModifiedClick(event) {
    return event.metaKey || event.ctrlKey || event.shiftKey || event.altKey || event.button !== 0;
}

function shouldTrackLink(anchor) {
    if (!anchor.href || anchor.hasAttribute('download') || anchor.target === '_blank') {
        return false;
    }

    const url = new URL(anchor.href, window.location.href);
    if (url.origin !== window.location.origin) return false;
    if (url.protocol === 'mailto:' || url.protocol === 'tel:') return false;
    if (url.pathname === window.location.pathname && url.search === window.location.search) {
        return false;
    }

    return true;
}

function submitButton(event, form) {
    if (event.submitter && event.submitter.type === 'submit') {
        return event.submitter;
    }

    return form.querySelector('[type="submit"]');
}

export default function initMotion() {
    document.querySelectorAll('[data-count-up]').forEach(animateCount);

    document.addEventListener('click', (event) => {
        if (event.defaultPrevented || isModifiedClick(event)) return;

        const anchor = event.target.closest('a[href]');
        if (!anchor || !shouldTrackLink(anchor)) return;

        startProgress();
    });

    document.addEventListener('submit', (event) => {
        const form = event.target.closest('form');
        if (!form) return;

        const button = submitButton(event, form);

        if (event.defaultPrevented) return;

        setButtonLoading(button, true);

        if (form.target !== '_blank') startProgress();
    });

    window.addEventListener('pageshow', (event) => {
        if (event.persisted) finishProgress();
    });
}
