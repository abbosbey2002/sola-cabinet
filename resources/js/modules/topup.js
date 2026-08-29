/**
 * The iWon top-up form opens in a new tab (target="_blank"), so submitting
 * it leaves this tab exactly where it was — nothing tells the subscriber
 * their payment is now open elsewhere unless this fills that gap. No JS
 * means no banner, but the form's own target="_blank" still opens the new
 * tab and the subscriber can navigate to /topup/return by hand — nothing
 * here is load-bearing for the actual payment flow.
 *
 * Markup contract:
 *   <form data-topup-form target="_blank">...<button type="submit">...</form>
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
