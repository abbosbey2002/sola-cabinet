# Finance page has no top-up card
- **Date:** 2026-09-02_14-36
- **Coverage summary:** Feature: `/finance` does not render the iWon pay-card button or title when iWon is active. Home pay-card assertions unchanged.
- **Failure scenarios included:** none beyond the page contract (auth already covered by existing finance tests).
- **Deliberately NOT tested & why:** legal-entity `/finance` — they already cannot top up; the card is gone for everyone.
- **Assumptions / risks:** `pay_card_button` must not appear elsewhere on finance (nav, table). It currently does not.
- **How to run:** `docker compose exec -u www-data php php artisan test --filter=the_finance_page_does_not_offer_a_top_up_card`
