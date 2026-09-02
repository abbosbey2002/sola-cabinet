/**
 * Offline strip — the metro-tunnel case. The page is already in memory;
 * a spinner here would lie. Show a status, hide it when the radio comes back.
 *
 * Markup: <div data-offline-banner hidden>
 */

export default function initOffline() {
    const banner = document.querySelector('[data-offline-banner]');
    if (!banner) return;

    const sync = () => {
        banner.toggleAttribute('hidden', navigator.onLine);
    };

    window.addEventListener('online', sync);
    window.addEventListener('offline', sync);
    sync();
}
