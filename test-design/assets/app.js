/* ===========================================================================
   Sola Kabinet — umumiy xatti-harakat.
   ---------------------------------------------------------------------------
   Bu yerdagi hamma narsa Bladega ko'chadi:
     · ko'rinish paneli (mavzu + matn hajmi)  → <x-view-settings>
     · drawer                                  → resources/js/modules/nav.js
     · kun o'lchagichi                         → <x-day-meter>
   Faqat balans holatlari (data-state tugmalari) prototipga xos — ular
   ilovada real ma'lumotdan keladi.
   =========================================================================== */

const root = document.documentElement;

/* --- Ko'rinish: mavzu + matn hajmi ---------------------------------------
   Ikkalasi ham uch holatli va bir xil naqsh bo'yicha ishlaydi: qiymat bo'sh
   bo'lsa atribut olib tashlanadi — "tizim bo'yicha" va "oddiy hajm" haqiqiy
   standart holat bo'lib qoladi, atribut bilan taqlid qilinmaydi.
   Boshlang'ich qiymat har bir sahifaning <head> idagi kichik skriptda
   o'qiladi, shuning uchun sahifa hech qachon noto'g'ri mavzuda chaqnamaydi. */

const PREFS = {
    theme: { attr: 'data-theme', key: 'sola-theme', valid: ['light', 'dark'] },
    text: { attr: 'data-text', key: 'sola-text', valid: ['lg', 'xl'] },
};

function readPref(name) {
    const pref = PREFS[name];
    const value = root.getAttribute(pref.attr);

    return pref.valid.includes(value) ? value : '';
}

function applyPref(name, value) {
    const pref = PREFS[name];

    if (pref.valid.includes(value)) {
        root.setAttribute(pref.attr, value);
        localStorage.setItem(pref.key, value);
    } else {
        root.removeAttribute(pref.attr);
        localStorage.removeItem(pref.key);
    }

    syncPrefInputs();
    if (name === 'theme') syncThemeColor();
}

function syncPrefInputs() {
    for (const name of Object.keys(PREFS)) {
        const current = readPref(name);

        document.querySelectorAll(`input[name="sola-${name}"]`).forEach(input => {
            input.checked = input.value === current;
        });
    }
}

// Telefon brauzerining tepa paneli ham mavzu bilan birga o'zgarsin.
function syncThemeColor() {
    const chosen = readPref('theme');
    const dark = chosen === 'dark'
        || (!chosen && matchMedia('(prefers-color-scheme: dark)').matches);

    document.querySelector('meta[name="theme-color"]')
        ?.setAttribute('content', dark ? '#0f140e' : '#f0f3ec');
}

/* Panel har bir sahifada kerak — mehmon ekranlarida ham, chunki shriftni
   kattalashtirish ehtiyoji kirishdan oldin paydo bo'ladi. Markup shu yerda
   bir marta yozilgan; Bladeda bu <x-view-settings> komponenti bo'ladi. */
const VIEW_PANEL = `
<div class="relative">
    <button type="button" data-view-toggle aria-expanded="false"
            class="flex min-h-[3rem] items-center gap-2 rounded-full border-2 border-line px-4 py-2 text-sm font-semibold text-ink transition-colors hover:bg-surface-2">
        <svg class="size-5 shrink-0" aria-hidden="true" focusable="false"><use href="#i-view"/></svg>
        Ko'rinish
    </button>

    <div data-view-panel hidden
         class="absolute right-0 top-[calc(100%+.5rem)] z-50 w-[min(21rem,90vw)] rounded-card border-2 border-line-strong bg-surface p-5"
         style="box-shadow: var(--shadow-card)">

        <fieldset class="border-0 p-0">
            <legend class="u-label mb-2.5">Mavzu</legend>
            <div class="grid grid-cols-3 gap-2">
                <label class="u-choice flex-col !gap-1">
                    <input type="radio" name="sola-theme" value="light">
                    <svg class="size-5" aria-hidden="true" focusable="false"><use href="#i-sun"/></svg>
                    Yorug'
                </label>
                <label class="u-choice flex-col !gap-1">
                    <input type="radio" name="sola-theme" value="dark">
                    <svg class="size-5" aria-hidden="true" focusable="false"><use href="#i-moon"/></svg>
                    Qorong'i
                </label>
                <label class="u-choice flex-col !gap-1">
                    <input type="radio" name="sola-theme" value="">
                    <svg class="size-5" aria-hidden="true" focusable="false"><use href="#i-auto"/></svg>
                    Tizim
                </label>
            </div>
        </fieldset>

        <fieldset class="mt-5 border-0 p-0">
            <legend class="u-label mb-2.5">Matn hajmi</legend>

            <!-- "A" harfi namuna, yorliq esa uchalasida ham bir xil o'lchamda —
                 aks holda eng uzun yorliq ikki qatorga tushib, tugmalar bo'yi
                 har xil bo'lib qoladi. -->
            <div class="grid grid-cols-3 gap-2">
                <label class="u-choice flex-col !gap-0.5">
                    <input type="radio" name="sola-text" value="">
                    <span class="u-display leading-none" style="font-size:1rem" aria-hidden="true">A</span>
                    <span class="text-sm">Oddiy</span>
                </label>
                <label class="u-choice flex-col !gap-0.5">
                    <input type="radio" name="sola-text" value="lg">
                    <span class="u-display leading-none" style="font-size:1.375rem" aria-hidden="true">A</span>
                    <span class="text-sm">Katta</span>
                </label>
                <label class="u-choice flex-col !gap-0.5">
                    <input type="radio" name="sola-text" value="xl">
                    <span class="u-display leading-none" style="font-size:1.75rem" aria-hidden="true">A</span>
                    <span class="text-sm">Eng katta</span>
                </label>
            </div>
            <p class="mt-2.5 text-xs text-muted">Tanlov shu qurilmada saqlanadi.</p>
        </fieldset>
    </div>
</div>`;

document.querySelectorAll('[data-view-mount]').forEach(mount => {
    mount.innerHTML = VIEW_PANEL;
});

function closeViewPanels(except) {
    document.querySelectorAll('[data-view-panel]').forEach(panel => {
        if (panel === except || panel.hidden) return;

        const toggle = panel.previousElementSibling;

        // Fokus panel ichida qolgan bo'lsa u <body> ga tushib ketadi va
        // klaviatura foydalanuvchisi o'z o'rnini yo'qotadi.
        const restore = panel.contains(document.activeElement);

        panel.hidden = true;
        toggle.setAttribute('aria-expanded', 'false');

        if (restore) toggle.focus();
    });
}

document.addEventListener('click', e => {
    const toggle = e.target.closest('[data-view-toggle]');

    if (toggle) {
        const panel = toggle.nextElementSibling;
        const open = panel.hidden;

        closeViewPanels(panel);
        panel.hidden = !open;
        toggle.setAttribute('aria-expanded', String(open));

        return;
    }

    if (!e.target.closest('[data-view-panel]')) closeViewPanels();
});

document.addEventListener('change', e => {
    if (e.target.name === 'sola-theme') applyPref('theme', e.target.value);
    if (e.target.name === 'sola-text') applyPref('text', e.target.value);
});

matchMedia('(prefers-color-scheme: dark)').addEventListener('change', syncThemeColor);

syncPrefInputs();
syncThemeColor();

/* --- Drawer ---------------------------------------------------------------
   Hook nomlari prod dagi resources/js/modules/nav.js bilan bir xil, shuning
   uchun Bladega o'tkazganda bu blok o'rniga o'sha modul ishlaydi. */

const drawer = document.querySelector('[data-nav-drawer]');
const navToggle = document.querySelector('[data-nav-toggle]');
let restoreFocusTo = null;

function openDrawer() {
    restoreFocusTo = document.activeElement;
    drawer.hidden = false;
    navToggle.setAttribute('aria-expanded', 'true');
    document.body.style.overflow = 'hidden';
    drawer.querySelector('[data-nav-close]').focus();
}

function closeDrawer() {
    if (!drawer || drawer.hidden) return;

    drawer.hidden = true;
    navToggle.setAttribute('aria-expanded', 'false');
    document.body.style.overflow = '';

    if (restoreFocusTo && document.contains(restoreFocusTo)) restoreFocusTo.focus();
    restoreFocusTo = null;
}

navToggle?.addEventListener('click', openDrawer);
drawer?.querySelector('[data-nav-close]')?.addEventListener('click', closeDrawer);
drawer?.querySelector('[data-nav-scrim]')?.addEventListener('click', closeDrawer);

document.addEventListener('keydown', e => {
    if (e.key !== 'Escape') return;

    closeDrawer();
    closeViewPanels();
});

// Desktopga kengaytirilganda ochiq qolgan drawer osilib qolmasin.
matchMedia('(min-width: 64rem)').addEventListener('change', e => { if (e.matches) closeDrawer(); });

/* --- Kun o'lchagichi ------------------------------------------------------
   Bladeda bu @for sikli (yoki <x-day-meter>) bo'ladi. Bezak: aria-hidden,
   ma'nosi yonidagi matnda so'z bilan takrorlangan. */

function buildMeter(el, { total, used, charge }) {
    const ticks = Array.from({ length: total }, (_, i) => {
        const tick = document.createElement('span');
        const day = i + 1;

        tick.className = 'u-tick';

        if (day === charge) tick.dataset.t = 'charge';
        else if (day === used) tick.dataset.t = 'today';
        else if (day < used) tick.dataset.t = 'past';

        return tick;
    });

    el.replaceChildren(...ticks);
}

/* --- Balans holatlari — FAQAT PROTOTIP ------------------------------------
   Uchala holat ham ko'rilishi kerak: faqat "hammasi joyida" da ishlaydigan
   dizayn — dizayn emas. Har uchalasida rang, ikonka va so'z birga o'zgaradi,
   ya'ni rangni ajrata olmaydigan foydalanuvchi ham farqni ko'radi. */

const STATES = {
    ok: {
        balance: '125 000',
        days: 'Yechishgacha 24 kun qoldi',
        meter: { total: 31, used: 7, charge: 31 },
        note: 'Balans yetarli — 01.09 dagi 25 000 so\'m yechim qoplanadi.',
        icon: '#i-check', bg: 'var(--c-action-soft)', fg: 'var(--c-action)',
    },
    low: {
        balance: '18 400',
        days: 'Yechishgacha 6 kun qoldi',
        meter: { total: 31, used: 25, charge: 31 },
        note: '01.09 dagi yechim uchun 6 600 so\'m yetishmaydi. Payme, Click yoki Uzum ilovasida D-100145 shartnoma raqamini kiritib to\'lang.',
        icon: '#i-alert', bg: 'var(--c-warn-soft)', fg: 'var(--c-warn)',
    },
    neg: {
        balance: '−18 500',
        days: 'Yechish muddati o\'tdi',
        meter: { total: 31, used: 31, charge: 31 },
        note: 'Hisobingiz manfiy. Internet uzilib qolmasligi uchun 43 500 so\'m to\'lang — Payme, Click yoki Uzum ilovasida D-100145 shartnoma raqamini kiriting.',
        icon: '#i-alert', bg: 'var(--c-danger-soft)', fg: 'var(--c-danger)',
    },
};

const hero = document.querySelector('[data-hero]');

function setState(name) {
    const state = STATES[name];

    if (!hero || !state) return;

    // Matn textContent orqali qo'yiladi, innerHTML orqali emas: bu markup
    // Bladega ko'chganda shu joyga abonentning real ismi va summasi tushadi.
    hero.querySelector('[data-hero-balance]').textContent = state.balance;
    hero.querySelector('[data-hero-days]').textContent = state.days;

    buildMeter(hero.querySelector('[data-meter]'), state.meter);

    const note = hero.querySelector('[data-hero-note]');

    note.style.background = state.bg;
    note.replaceChildren(icon(state.icon, 'mt-1 size-5 shrink-0', state.fg), text(state.note));

    document.querySelectorAll('[data-state]').forEach(el => {
        el.setAttribute('aria-pressed', String(el.dataset.state === name));
    });
}

function icon(href, className, color) {
    const NS = 'http://www.w3.org/2000/svg';
    const svg = document.createElementNS(NS, 'svg');
    const use = document.createElementNS(NS, 'use');

    svg.setAttribute('class', className);
    svg.setAttribute('style', `color: ${color}`);
    svg.setAttribute('aria-hidden', 'true');
    svg.setAttribute('focusable', 'false');
    use.setAttribute('href', href);
    svg.append(use);

    return svg;
}

function text(value) {
    const span = document.createElement('span');

    span.textContent = value;

    return span;
}

// Inline on* atributlari ataylab ishlatilmagan: Bladega ko'chganda ularning
// ichiga {{ $abonent->name }} kabi qiymat tushadi va {{ }} JS satr kontekstida
// himoya qilmaydi — ' belgisi &#039; ga aylanib, HTML parseri uni JS parseriga
// yetkazguncha yana ' ga qaytaradi. Qiymatlar data-* orqali uzatilsin.
document.addEventListener('click', e => {
    const button = e.target.closest('[data-state]');

    if (!button) return;

    setState(button.dataset.state);
});

if (hero) setState('ok');

/* --- Kirish kodi katakchalari ---------------------------------------------
   Ma'no bitta <input> da: u shaffof va katakchalar ustida turadi, shuning
   uchun SMS avtoto'ldirish ham, klaviatura ham, ekran o'quvchi ham odatdagidek
   ishlaydi. Bu yerdagi kod faqat ko'rinishni inputga ergashtiradi. */
(() => {
    const input = document.querySelector('[data-code-input]');
    if (!input) return;

    const slots = [...document.querySelectorAll('[data-code-slot]')];

    function paint() {
        // Faqat raqam: qo'lda yozilgan harf katakchada ko'rinmasligi kerak.
        const digits = input.value.replace(/\D/g, '').slice(0, slots.length);

        if (digits !== input.value) input.value = digits;

        slots.forEach((slot, i) => {
            slot.textContent = digits[i] ?? '';
            slot.dataset.filled = String(i < digits.length);
            // Navbatdagi katakcha — kursor qayerdaligini ko'rsatadi.
            slot.dataset.next = String(i === digits.length && document.activeElement === input);
            slot.removeAttribute('data-state');
        });
    }

    input.addEventListener('input', paint);
    input.addEventListener('focus', paint);
    input.addEventListener('blur', paint);

    // Prototip: to'liq kod "0000" bo'lsa xato holatini ko'rsatamiz, shunda
    // xato ko'rinishi ham maketda tekshiriladi.
    input.form?.addEventListener('submit', (event) => {
        if (input.value !== '0000') return;

        event.preventDefault();
        slots.forEach(slot => { slot.dataset.state = 'error'; });
        document.querySelector('[data-code-hint]').textContent = 'Kod noto’g’ri. Qaytadan urinib ko’ring.';
    });

    paint();
})();
