# Profile page copy review

- **Date:** 2026-08-31 15:07 +05
- **Mode:** SaaS + Multi-market
- **Screens/components delivered:** Profile (`/`) copy in Uzbek, Russian, and English; shared navigation, account menu, display settings, balance states, payment card, and footer text used on the page.
- **Decisions:** Reframed the page as “My account” rather than a generic home screen; replaced vague labels such as “Login” and “Statistics” with contract, balance, tariff-charge, and usage terminology; made payment actions explicit and shortened supporting text.
- **Quality floor result:** All three locale files pass PHP syntax checks and Pint. Copy was checked for Russian/Uzbek expansion and existing responsive containers. No markup, styling, routes, or behavior changed.
- **Verification:** 183 tests passed. One pre-existing state-dependent tariff allow-list test failed because tariff `9` is already enabled in the local SQLite database; the copy changes do not touch this path.
- **Left for later:** Browser review behind an authenticated subscriber session; the running `/` route currently redirects unauthenticated requests to `/auth/login`.
