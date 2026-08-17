/**
 * Display preferences: colour theme and text size.
 *
 * Markup contract (see <x-view-settings>):
 *   <input type="radio" name="sola-theme" value="light|dark|">
 *   <input type="radio" name="sola-text"  value="lg|xl|">
 *
 * Both are three-state and share one rule: an empty value REMOVES the root
 * attribute rather than writing a third keyword. That keeps "follow the system"
 * and "normal size" as the real default — the CSS then falls through to
 * prefers-color-scheme and to the base root font-size on its own.
 *
 * The initial attributes are set by a tiny inline script in <head>, before the
 * stylesheet is applied, so the page never flashes the wrong theme. This module
 * only handles changes made after that.
 */

const PREFS = {
    theme: { attr: 'data-theme', key: 'sola-theme', valid: ['light', 'dark'] },
    text: { attr: 'data-text', key: 'sola-text', valid: ['lg', 'xl'] },
};

// Must match --c-bg in both themes: this is the colour the phone paints its own
// browser chrome with, and a mismatch shows as a seam above the page.
const THEME_COLOR = { light: '#f1f6ed', dark: '#0d1307' };

const root = document.documentElement;

function read(name) {
    const value = root.getAttribute(PREFS[name].attr);

    return PREFS[name].valid.includes(value) ? value : '';
}

function isDark() {
    const chosen = read('theme');

    return chosen === 'dark'
        || (chosen === '' && window.matchMedia('(prefers-color-scheme: dark)').matches);
}

function syncThemeColor() {
    document.querySelector('meta[name="theme-color"]')
        ?.setAttribute('content', isDark() ? THEME_COLOR.dark : THEME_COLOR.light);
}

function syncInputs() {
    for (const name of Object.keys(PREFS)) {
        const current = read(name);

        document.querySelectorAll(`input[name="sola-${name}"]`).forEach((input) => {
            input.checked = input.value === current;
        });
    }
}

function apply(name, value) {
    const pref = PREFS[name];

    if (pref.valid.includes(value)) {
        root.setAttribute(pref.attr, value);
        try {
            localStorage.setItem(pref.key, value);
        } catch {
            // Private mode or a full quota: the choice still applies to this
            // page, it just will not survive the next navigation.
        }
    } else {
        root.removeAttribute(pref.attr);
        try {
            localStorage.removeItem(pref.key);
        } catch { /* see above */ }
    }

    syncInputs();
    if (name === 'theme') syncThemeColor();
}

export default function initPrefs() {
    syncInputs();
    syncThemeColor();

    document.addEventListener('change', (event) => {
        const input = event.target;
        if (input.type !== 'radio') return;

        if (input.name === 'sola-theme') apply('theme', input.value);
        if (input.name === 'sola-text') apply('text', input.value);
    });

    // On "system", a change in the OS setting has to reach the browser chrome
    // too — the CSS reacts on its own, the meta tag does not.
    window.matchMedia('(prefers-color-scheme: dark)')
        .addEventListener('change', syncThemeColor);
}
