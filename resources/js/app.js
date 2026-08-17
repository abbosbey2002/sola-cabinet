import '../css/app.css';

import initAjaxForms from './modules/ajax.js';
import initConfirm from './modules/confirm.js';
import initDisclosures from './modules/disclosure.js';
import initModals from './modules/modal.js';
import initNav from './modules/nav.js';
import initPrefs from './modules/prefs.js';
import initTables from './modules/table.js';
import initTariff from './modules/tariff.js';
import initToasts from './modules/toast.js';

/**
 * The cabinet is server-rendered; this is the whole client runtime. Every
 * module is delegation-based or a no-op when its markup is absent, so pages
 * only pay for what they actually show.
 */
function boot() {
    initPrefs();
    initDisclosures();
    initModals();
    initConfirm();
    initNav();
    initTables();
    initAjaxForms();
    initTariff();
    initToasts();
}

document.readyState === 'loading' ? document.addEventListener('DOMContentLoaded', boot) : boot();
