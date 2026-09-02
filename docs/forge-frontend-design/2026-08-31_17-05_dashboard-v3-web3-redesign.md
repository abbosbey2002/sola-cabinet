# Dashboard v3 — Web3 redesign (SOLA cabinet)

- **Date:** 2026-08-31 17:05 +05
- **Mode:** SaaS + Multi-market
- **Screens/components delivered:**
  - Figma page **Dashboard — Premium Redesign**
  - `Dashboard v3 — Desktop` (1440×1260) — full spec layout
  - `Dashboard v3 — Mobile` (360×1480)
  - `Variant / Active Tariff` — hero Card B when plan is connected (arc + next billing date)
- **Decisions:**
  - **Aesthetic:** Fintech/telecom dashboard with SOLA emerald primary (`#065F46`) and Web3 accent cyan (`#06B6D4` / `#34D399`). Background `#F0F4F8`, elevated white cards, Manrope figures + Inter UI copy.
  - **Information architecture:** Split hero into two equal cards — **Balance & top-up** (single primary CTA, contract number once in body) vs **Tariff status** (empty-state hides timer/billing; active variant shows arc + date).
  - **Web3 module:** Dedicated gradient card for USDT/TON top-up chips, wallet connect, SOLA cashback + NFT pass badge — visually separated from fiat Payme/Click/Uzum flow.
  - **Secondary grid:** Four uniform cards (tariff, devices, payments, services) with explicit empty states (`To'lovlar tarixi mavjud emas`, `Tarif ulanmagan`).
  - **Header:** Logo + network badge, UZ nav labels, lang switcher, wallet pill, profile block with **ID** and **Contract** separated (contract removed from hero duplication rule).
  - **Signature element:** Cyan Web3 strip bridging telecom billing and crypto — the one memorable accent on an otherwise calm green/white dashboard.
- **Quality floor result:**
  - Token-based palette documented; primary CTA visually dominant; secondary actions outline style.
  - Uzbek copy used in Figma per brief; ru/en parity required before Blade implementation.
  - Web3 balances/wallet state are **design placeholders** — must not ship as invented billing data.
- **Left for later:**
  - Blade/CSS implementation gated by `config()` flags (wallet provider, crypto gateways) — no backend exists yet.
  - Connect Wallet + on-chain top-up flows need product/engineering spec (TON/Polygon, settlement to `/abonent/info` saldo).
  - Replace placeholder ◆ icons with inline SVG icon set matching `x-icon`.
  - Localize all new strings in `lang/{uz,ru,en}/app.php`.
  - QA: empty tariff hides cycle ring in code (`$cycle === null` already supported); verify contract appears only once on live page.

**Figma:** [Cabinet-desing → Dashboard — Premium Redesign](https://www.figma.com/design/UGKZLrFgWsGks08HgeJkNS/Cabinet-desing?node-id=224-17)
