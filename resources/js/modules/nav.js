/**
 * The mobile navigation drawer.
 *
 * On >=lg the primary nav is a pill rail in the top bar and this does nothing;
 * below that the same list slides in from the left.
 */

const OPEN = 'is-open';

export default function initNav() {
    const drawer = document.querySelector('[data-nav-drawer]');
    const toggle = document.querySelector('[data-nav-toggle]');

    if (!drawer || !toggle) return;

    const scrim = drawer.querySelector('[data-nav-scrim]');

    const setOpen = (open) => {
        drawer.classList.toggle(OPEN, open);
        drawer.toggleAttribute('hidden', !open);
        toggle.setAttribute('aria-expanded', String(open));
        document.body.style.overflow = open ? 'hidden' : '';

        if (open) {
            drawer.querySelector('a, button')?.focus();
        } else {
            toggle.focus();
        }
    };

    toggle.addEventListener('click', () => setOpen(!drawer.classList.contains(OPEN)));
    scrim?.addEventListener('click', () => setOpen(false));
    drawer.querySelector('[data-nav-close]')?.addEventListener('click', () => setOpen(false));

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && drawer.classList.contains(OPEN)) setOpen(false);
    });

    // Coming back from a resize into desktop layout must not leave the body locked.
    window.matchMedia('(min-width: 1024px)').addEventListener('change', (event) => {
        if (event.matches && drawer.classList.contains(OPEN)) setOpen(false);
    });
}
