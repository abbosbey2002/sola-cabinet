# Wait states

A wait is a designed state for **work that has not finished**. If the HTML is already on the page, show it.

## Inventory (already shipped)

| Scenario | Markup / module | Success looks like | Failure looks like |
|---|---|---|---|
| In-app navigation | `[data-progress]`, `motion.js` | Bar reaches 100% and clears on the next paint | `pageshow` persisted → `finishProgress()` |
| Money / auth POST | submit `.is-loading` | Navigate away | Button stays loading until the browser errors; do not disable before the submit event fires |
| Period filter | `[data-ajax-form]` + `[data-ajax-region]`, `ajax.js` | Replace region HTML | 12s `AbortError` → `.u-ajax-fail` with **Qayta urinish** / retry; **keep the old table** |
| Offline | `[data-offline-banner]`, `offline.js` | Banner `hidden` while `navigator.onLine` | Bottom warn strip, no spinner |
| SMS OTP | `[data-otp]`, `otp.js` | 4 digits (type or paste) → `requestSubmit()` | Wrong code: server re-renders the field; do not auto-loop |
| Billing unreachable | `resources/views/errors/503.blade.php` | GET current URL | Copy + refresh CTA, never a stack trace |

Copy lives in `lang/{uz,ru,en}/app.php` (`ui.timeout`, `ui.retry`, `ui.offline`) and `lang/{uz,ru,en}/errors.php` (`service_unavailable*`). Layouts expose `data-timeout-label` and `data-retry-label` for JS.

## When the user asks for "more animation / loading"

Pick from this list only if the wait is real. Rank:

1. **Direction after failure** (timeout retry, 503 refresh, offline honesty)
2. **Shortening a real wait** (OTP auto-submit, paste)
3. **One signature moment** on a data screen (count-up, arc draw)
4. Never: skeleton on Blade, SMS resend, looping decoration on a finished home

## Implementation traps

- Button labels are often **text nodes** next to an `<x-icon>`. `.is-loading > * { visibility: hidden }` leaves the words visible. Also set `color: transparent` on the button.
- `hidden` on the offline banner wins over `display: flex` (`display: none !important`). Toggle the `hidden` attribute; do not fight it with CSS.
- AJAX timeout must not toast-and-forget. The retry control stays on the data.
- `/identify` with SMS is **not** retried. A "resend code" button that posts login again costs the subscriber a message.
