import '../css/app.css';

import initAjaxForms from './modules/ajax.js';
import initAmountMask from './modules/amount-mask.js';
import initAmountPresets from './modules/amount-presets.js';
import initBulkSelect from './modules/bulk-select.js';
import initConfirm from './modules/confirm.js';
import initCopy from './modules/copy.js';
import initDisclosures from './modules/disclosure.js';
import initModals from './modules/modal.js';
import initNav from './modules/nav.js';
import initPhoneMask from './modules/phone-mask.js';
import initPrefs from './modules/prefs.js';
import initTables from './modules/table.js';
import initTariff from './modules/tariff.js';
import initToasts from './modules/toast.js';
import initTopUp from './modules/topup.js';
import initMotion from './modules/motion.js';
import initOffline from './modules/offline.js';
import initOtp from './modules/otp.js';

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
    initCopy();
    initNav();
    initPhoneMask();
    initAmountMask();
    initAmountPresets();
    initTables();
    initBulkSelect();
    initAjaxForms();
    initTariff();
    initToasts();
    initTopUp();
    initOtp();
    initOffline();
    // Last: sees defaultPrevented from ajax / tariff / modal so it does not
    // start a page-progress bar on a fetch that never leaves this page.
    initMotion();
}

document.readyState === 'loading' ? document.addEventListener('DOMContentLoaded', boot) : boot();
