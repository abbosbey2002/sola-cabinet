# Hero card: balance/next-charge/login as boxed rows

- **Date:** 2026-08-28 15:54
- **Mode:** SaaS (kabinet dashboard) — elevate within the existing token system, not a new one.
- **Screens/components delivered:**
  - `resources/views/cabinet/index.blade.php` — the Home page hero/balance card

## Decisions

Source of truth was a Figma reference the user built from this same card (a
"Balance Row" / "Login Row" component pair) — three visually identical
panels: label left, value right, on a recessed background, replacing the
previous layout (a bare flex row for balance+next-charge, then a
border-top-divided login block).

Composed entirely from tokens already in `app.css` — no new hex values, no
new fonts (the Figma reference used a mono face for the login value; skipped,
since `font-variant-numeric: tabular-nums` on `body` already gives aligned
digits, and the project explicitly self-hosts only Inter/Manrope):

- Row background: `var(--c-bg)` — the page's own background reused as a
  "recessed panel" fill inside the white `u-card`. No new token needed:
  `--c-bg` is already the proven contrast pair for `--c-ink`/`--c-muted`
  everywhere else on the page, and in dark mode it is *darker* than
  `--c-surface`, so the inset reads correctly in both themes without a
  conditional.
- `rounded-xl` (12px) and `p-5` (20px) — both exact matches to the Figma
  node's corner radius and padding, already existing Tailwind scale steps.
- Row label: reused `u-label` as-is (14px/semibold/muted — already the
  exact style the reference uses).
- Balance figure stepped down from `text-4xl` to `text-3xl` (still `u-figure`
  ExtraBold) since it's no longer the lone hero element but one of three
  equally-shaped rows — hierarchy now lives in size only, matching the
  Figma reference's 32px-vs-16px relationship.
- Next-charge and login values stepped down proportionally (`text-xl`→
  `text-base`, `text-lg`→`text-base`) to match.
- The three rows wrap in one `space-y-4` container; the status note strip
  below changed `mt-5`→`mt-4` to sit on the same 16px rhythm (already the
  gap used between the info-tile cards further down this same page).
- Negative-balance danger coloring, the nullable `$cycle` row, and the
  blank-`$billingLogin` row all kept their exact same conditionals — pure
  visual restructuring, no logic touched.

No new component class added (`.u-row` or similar): this shape repeats 3×
within one card, not across the 5+ templates `app.css`'s own stated
threshold requires before a pattern earns a shared class. If it recurs
elsewhere later, extract then.

## Quality floor result

- Verified WCAG-safe: every color is a token already vetted in `app.css`.
- Verified in a real Chrome browser (`npm run build` + a temporary static
  HTML harness under `public/`, deleted after use — not part of the repo):
  three balance states (ok / negative / no-login) × light/dark theme × a
  340px mobile width. Confirmed: rows render correctly in both themes, the
  danger color reads clearly, the next-charge row wraps its value onto its
  own line at 340px without clipping or overlap (balance/login rows stay
  single-line at that width), no new console errors.
- `prefers-reduced-motion`: unaffected — no new animation.
- Responsive: reused the existing `flex-wrap`/`gap-y-1` pattern so a narrow
  viewport wraps value under label per-row instead of overflowing.
- No new brand logos, no new fonts, no CDN dependency.
- `php artisan test --filter=Cabinet` — 56 passed, including the
  login-row-present and login-row-absent tests that exercise this exact
  markup.

## Pipeline results

- `forge-code-reviewer`: not run this pass — RISK=false, single-file
  Blade/CSS-class restructuring with no logic change, verified directly via
  browser + full existing test suite; skipping matches the routing rule
  against calling the reviewer for changes already covered by a green
  passing suite and direct visual verification on a pure-display diff this
  small. Available on request.
- `forge-security-auditor`: not run — RISK=false, pure display/markup.

## Left for later

Mobile (`Dashboard — Mobile`) and the alternate `dashboard-premium-redesign`
frame in Figma were not touched — user asked for the code change scoped to
this one card; if the same boxed-row treatment should propagate to those
Figma frames or to other pages, that's a separate follow-up.

## User must do

Nothing — no migration, no env var, no config change. The `npm run build`
used for the visual check was a local, already-cleaned-up verification step;
Vite compiles this the same way in the normal build pipeline.
