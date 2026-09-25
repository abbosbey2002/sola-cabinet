/**
 * The browser's half of the activity journal (POST /activity).
 *
 * The server sees every page and every form it is sent, but not a table being
 * printed or searched, or a confirmation dialog closed without confirming. Those
 * four events are reported here, and nothing else.
 *
 * Markup contract:
 *   <meta name="activity-beacon" content="/activity">  (layouts/app, only while
 *     the journal is on — without it this module does nothing at all)
 *   <section data-table data-activity-table="payments|traffic"> … [data-table-search] [data-table-print]
 *   <button data-confirm … data-activity-cancel="device" [data-activity-permit="77"]>
 *   the tariff dialogs: [data-modal="tariff-timing|tariff-confirm"] with [data-tariff-option]:checked
 *
 * It also leaves the screen size, theme and text size in the `sola_scr` cookie,
 * so every request the server records carries them — not only these beacons.
 */

const SEARCH_PAUSE_MS = 800;
const COOKIE = 'sola_scr';
const TARIFF_DIALOGS = ['tariff-timing', 'tariff-confirm'];

function beaconUrl() {
    return document.querySelector('meta[name="activity-beacon"]')?.content ?? '';
}

function send(url, event, fields = {}) {
    const body = new FormData();
    body.append('_token', document.querySelector('meta[name="csrf-token"]')?.content ?? '');
    body.append('event', event);

    for (const [key, value] of Object.entries(fields)) {
        if (value !== null && value !== undefined && value !== '') body.append(key, String(value));
    }

    // sendBeacon survives the page being closed or navigated away from — a
    // print dialog or a closed modal is often the last thing on the page.
    if (navigator.sendBeacon?.(url, body)) return;

    fetch(url, { method: 'POST', body, keepalive: true, credentials: 'same-origin' }).catch(() => {});
}

function rememberScreen() {
    const root = document.documentElement;
    const theme = ['light', 'dark'].includes(root.dataset.theme) ? root.dataset.theme : 'system';
    const text = ['lg', 'xl'].includes(root.dataset.text) ? root.dataset.text : 'normal';
    const value = [screen.width, screen.height, window.innerWidth, theme, text].join(',');

    document.cookie = `${COOKIE}=${value}; path=/; max-age=31536000; SameSite=Lax`;
}

function tableName(table) {
    return table?.dataset.activityTable ?? null;
}

export default function initActivity() {
    if (document.documentElement.dataset.app === 'admin') return;

    rememberScreen();

    const url = beaconUrl();
    if (!url) return;

    // Print — the toolbar button and the browser's own Ctrl+P both end here.
    // One print of one table per beforeprint, whichever started it.
    window.addEventListener('beforeprint', () => {
        const table = document.querySelector('[data-activity-table]');
        if (!table) return;

        send(url, 'ui.print', {
            table: tableName(table),
            rows: table.querySelectorAll('tbody tr').length,
        });
    });

    // Search — one record per pause in typing, not one per keystroke.
    const timers = new WeakMap();

    document.addEventListener('input', (event) => {
        const input = event.target.closest?.('[data-table-search]');
        const table = input?.closest('[data-activity-table]');
        if (!table) return;

        clearTimeout(timers.get(input));
        timers.set(input, setTimeout(() => {
            const query = input.value.trim().slice(0, 100);
            if (!query) return;

            const needle = query.toLowerCase();
            const results = [...table.querySelectorAll('tbody tr')]
                .filter((row) => row.textContent.toLowerCase().includes(needle)).length;

            send(url, 'ui.search', { table: tableName(table), query, results });
        }, SEARCH_PAUSE_MS));
    });

    // Dialogs closed without confirming.
    let lastConfirmTrigger = null;

    document.addEventListener('click', (event) => {
        const trigger = event.target.closest('[data-confirm]');
        if (trigger) lastConfirmTrigger = trigger;
    }, true);

    document.addEventListener('sola:modal-dismiss', (event) => {
        const name = event.detail?.name;

        if (TARIFF_DIALOGS.includes(name)) {
            send(url, 'ui.tariff_dialog_cancelled', {
                tariff_id: document.querySelector('[data-tariff-option]:checked')?.value,
            });

            return;
        }

        if (name === 'confirm' && lastConfirmTrigger?.dataset.activityCancel === 'device') {
            send(url, 'ui.device_dialog_cancelled', {
                permit_id: lastConfirmTrigger.dataset.activityPermit,
            });
        }
    });
}
