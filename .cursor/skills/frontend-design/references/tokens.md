# Tokens and `u-*` vocabulary

Source of truth: `resources/css/app.css` `:root` and `@layer components`. Do not introduce a second palette.

## Color

| Token | Light | Role |
|---|---|---|
| `--c-bg` | `#f1f6ed` | Page ground (logo-hue tint, not grey) |
| `--c-surface` | `#ffffff` | Cards |
| `--c-ink` | `#141d09` | Body text (AAA) |
| `--c-muted` | `#505b46` | Secondary text (AAA) |
| `--c-primary` / `--c-action` | `#4a7100` | Buttons, links, focus (5.75:1 on white) |
| `--c-signal` | `#8fd400` | Graphic only — progress bar, live pip. Never as text on light |
| `--c-warn` | `#8a5a00` | Offline, timeout, low balance |
| `--c-danger` | `#a32014` | Negative balance, destructive |

Dark theme flips these on `:root[data-theme='dark']` and `prefers-color-scheme`. New CSS must use the tokens so both themes follow.

`--c-signal` / logo `#8FD400` fails contrast on white. Fill on dark, or a 3px bar — not labels.

## Type

- Body: Inter Variable (`--font-sans`), `ss02`, `tabular-nums`, root **100%** (honor the phone's text size; `data-text` lg/xl is the reader's own control).
- Display / figures: Manrope Variable (`--font-display`) via `.u-display` / `.u-figure`.
- Verify Uzbek/Russian glyphs render in the chosen face (`oʻ`, `gʻ`, `ў`, `қ`). Safe here: Inter + Manrope, self-hosted.

## Components already in the system

Reuse before drawing a new one: `.u-card`, `.u-card-hero`, `.u-btn-primary` / `ghost` / `danger` / `outline`, `.u-field`, `.u-table` + `.u-table-cards`, `.u-pill-*`, `.u-nav-link`, `.u-modal*`, `.u-rise`, `.u-draw`, `.u-progress`, `.u-offline`, `.u-ajax-fail`, `.u-page-head*`, `x-empty`, `x-icon`, `x-arc`.

A "slightly different button" is a variant on `.u-btn-*`, not a local override.

## Payment marks

Payme, Click, Uzcard, Humo, iWon: files under `public/img/logos/`, sitting on a **white/near-white chip**. Never recolor a brand mark to match `--c-action`.
