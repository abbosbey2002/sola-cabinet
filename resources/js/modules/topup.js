/**
 * The iWon top-up form opens in a new tab (target="_blank"). This tab swaps
 * the form for a short note so the subscriber knows where the payment opened.
 *
 * Markup contract:
 *   <form data-topup-form target="_blank">...</form>
 *   <div data-topup-opened hidden>...</div>
 */
export default function initTopUp() {
    const form = document.querySelector('[data-topup-form]');
    const banner = document.querySelector('[data-topup-opened]');
    if (!form || !banner) return;

    form.addEventListener('submit', () => {
        form.hidden = true;
        banner.hidden = false;
    });
}
