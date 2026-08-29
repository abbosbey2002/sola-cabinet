# Top-up page: premium redesign with quick-amount chips

- **Date:** 2026-08-30 02:15
- **Mode:** SaaS (kabinet dashboard) — same token system as the Home hero redesign earlier this session.
- **Screens/components delivered:**
  - `resources/views/cabinet/topup.blade.php` — the balance top-up form
  - `resources/views/cabinet/topup-return.blade.php` — the post-payment status page (light consistency pass)
  - `resources/js/modules/amount-presets.js` — new module, quick-amount chip behaviour
  - `resources/js/app.js` — wiring for the new module
  - `resources/css/app.css` — `.u-card-hero`'s comment generalized (no behaviour change) now that it's used on more than the Home page
  - `lang/{en,ru,uz}/app.php` — new `app.topup.amount_presets` key

## Brief

Follow-up to the earlier Home-page hero redesign this session: user asked to also bring the top-up (balance refill) page in line.

## Decisions

1. **`.u-card-hero` reused, not duplicated.** It was documented as "the Home hero card only" after the earlier pass; since the top-up form is the same shape of problem (the one card a single-purpose page is built around), the class was reused as-is and its comment reworded to say so explicitly — a page with three equal cards still names none of them hero, per the same comment.
2. **Quick-amount chips** (10 000 / 20 000 / 50 000 / 100 000 so'm) — the single most standard, highest-value fintech top-up pattern from this session's earlier research, and the most concrete way to act on it. Built entirely client-side: `resources/js/modules/amount-presets.js` sets the existing masked `<input>`'s value and dispatches the same `input` event `amount-mask.js` already listens for, so the two modules share one formatting code path instead of two. Chips are `.u-choice` (already an established toggle-button pattern in this codebase, e.g. `admin/tariffs.blade.php`), plain hardcoded literals in the Blade template — `TopUpRequest`'s server-side validation (min 1 000 / max 50 000 000, numeric) is completely unmodified and authoritative regardless of how the field got its value.
3. **Icon-tile intro header**, matching the vocabulary from the Home-page pass (icon tile on `--c-action-soft`/`--c-action`), and a bigger `text-2xl` amount figure — money the subscriber is about to enter is worth the same typographic weight as money they're looking at.
4. **`topup-return.blade.php`** got the same `.u-card-hero` treatment for visual continuity across the whole top-up flow (form → status), otherwise untouched — its icon-circle + centered-text states (checking/success/timeout) were already clean.

## Quality floor result

- Verified live in Chrome: chip click fills and formats the field correctly, marks itself `aria-pressed="true"`, deselects on typing something else; light and dark theme both checked. Did not actually submit the form (avoids opening a real external redirect to iWon mid-session).
- `php artisan test --filter=TopUp`: 18/18 passed, before and after. Full suite: 178 passed, the one pre-existing unrelated failure (admin tariff whitelist) still present, unchanged from earlier this session.
- `npx vite build`: clean, no warnings.
- `prefers-reduced-motion`: unaffected, no new animation.
- No new brand logos, no new fonts, no CDN dependency, no new colours (reuses existing tokens throughout).

## Pipeline results

- RISK=true (top-up is part of the money flow, even though no server logic was touched) → ran `forge-code-reviewer` and `forge-security-auditor` in parallel.
- `forge-code-reviewer`: **APPROVE**, zero blockers, zero warnings. Two nitpicks, both explicitly called non-blocking: (a) a comment-worthiness note on the value-then-dispatch pattern in `amount-presets.js`, already covered by the module's own header comment; (b) the chip group's `aria-label` duplicated the visible field label — fixed anyway (cheap): added `app.topup.amount_presets` ("Tezkor summalar" / "Быстрые суммы" / "Quick amounts") in all three locales.
- `forge-security-auditor`: **SHIP**. Confirmed: preset chips are `type="button"` (can't submit the form), the new JS module never calls `fetch`/`XMLHttpRequest`/sends data anywhere, presets are hardcoded PHP literals not user input, and the form's `action`/`method`/`target="_blank"`/`rel="noopener"`/`@csrf` are all untouched.

## Follow-up: real iWon logo

User pointed at the existing `public/img/iwon-logo.svg` asset (already in the repo, unused) after this pass landed. Swapped the generic wallet-icon header tile for the actual iWon wordmark, per this design system's own brand-assets rule (plain-text/generic-icon payment-brand labelling "reads as a phishing page and kills client trust"). The SVG is a black-only wordmark with no light-on-dark variant, so it sits on a fixed `bg-white` chip rather than the theme's `--c-surface` — same reasoning `.u-logo` applies to the SOLA mark for the same problem, just a literal white instead of a second asset since iWon only gave us the one. Verified in both light and dark theme (white chip reads clearly against the dark hero gradient too). Purely additive/presentational (one `<img>` tag, existing static asset, no new JS or data flow) — not re-run through the full review pipeline per the "don't call the reviewer twice for a trivial follow-up" rule; `php artisan test --filter=TopUp` still 18/18.

## Follow-up: desktop scroll + mobile check

User reported the top-up card needed scrolling even on desktop, and asked for a mobile pass. Confirmed with real measurements (`window.innerHeight` vs `document.documentElement.scrollHeight` in the live browser) rather than guessing:

- Before: 1077px page vs 905px viewport — 116px of forced scroll for what is a short, single-purpose form. Root cause: this session's own additions (icon header row, quick-amount chip row, bumped `text-2xl` input, `p-6 sm:p-8` override on top of `.u-card`'s own default padding) stacked height nobody asked the page to carry.
- Fixes, all on `topup.blade.php` (and the padding override on `topup-return.blade.php`): dropped the `p-6 sm:p-8` override back to `.u-card`'s own default; merged the amount label and the "minimum 1000 сум" hint onto one row instead of two; dropped the input back to `text-xl`; tightened `mt-6`→`mt-4`/`space-y-5`→`space-y-3` throughout; dropped the submit button's `text-lg` override.
- After: 57px overflow, down from 116px — what's left is only the footer's small last line (shared page chrome present on every route in this app, not something this page's content controls). Home page, for comparison, runs 701px of overflow at the same viewport — this is now much closer to the app's other short-form pages than to a data-heavy dashboard.
- Mobile: `resize_window` still doesn't affect the real viewport in this sandbox (confirmed again via `window.innerWidth` staying unchanged), so verified with a genuine-viewport workaround instead — an in-page `<iframe>` sized to 390×844 pointing at the live route, which gets its own real CSS viewport for media queries (unlike zooming/scaling a screenshot). This caught one real bug: the icon-chip + intro-text header used `items-center`, which looked fine at desktop but visually floated the logo mid-paragraph once the text wrapped to four lines at 390px — fixed to `items-start` with a small `pt-1` nudge on the text for optical alignment. Preset chips wrap to a 2×2 grid at this width (expected, not a defect), the submit button's Russian label wraps to two lines (pre-existing copy, not something this pass should be rewriting, still fully legible and the button keeps its full touch-target height). Content is taller than one mobile screen (1229px at 390×844) — normal and expected for a page with the app's header/nav/footer chrome on a phone, not itself a defect; nothing overlaps or clips.
- A washed-out preset-chip-text appearance seen in one light-theme mobile screenshot turned out to be a screenshot-timing artifact (mid-flight on the page's `.3s` colour transition) — `getComputedStyle` on the actual element confirmed `color: rgb(20, 29, 9)` (full `--c-ink`, opacity 1) the whole time; not a real contrast bug.
- This was a presentational-only follow-up (spacing/class changes plus one alignment fix) with no new risk surface, so it was not re-run through the full review pipeline — `php artisan test --filter=TopUp` still 18/18, `npx vite build` clean.

## Left for later

- Nothing outstanding from either review.

## User must do

- Nothing required.
