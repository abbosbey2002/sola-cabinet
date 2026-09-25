# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

@AGENTS.md

The project guide is `AGENTS.md` (imported above). It is the single source of truth, so edit it there rather than duplicating it here. `.cursor/rules/*.mdc` holds the same rules, split up by file glob (architecture, blade-frontend, payments, php-laravel, tests). If you change a rule, keep both in sync.

## Running things

PHP runs in Docker (`php` service, always `-u www-data`). Node/Vite runs on the **host**. The site is at http://localhost:8080 and needs `npm run build` (or `npm run dev`) first, or pages fail to load.

```bash
# full suite (composer script clears config cache first)
docker compose exec -u www-data php composer test

# one file / one test / one suite
docker compose exec -u www-data php php artisan test tests/Unit/AbonentProfileTest.php
docker compose exec -u www-data php php artisan test --filter=methodName   # #[Test] methods, no test_ prefix needed
docker compose exec -u www-data php php artisan test --testsuite=Unit

# style
docker compose exec -u www-data php ./vendor/bin/pint --test   # check
docker compose exec -u www-data php ./vendor/bin/pint           # fix
```

PHP 8.3+ on the host also works for tests and Pint. `phpunit.xml` pins a fake `SOLA_BASE_URL` (`http://sola.test`) and signing credentials, with `IWON_ACTIVE=false`, so tests never reach the real billing API.

First-time setup and Docker permission fixes (for root-owned `storage/` or `bootstrap/cache`) are in `readme.md` and `DOCKER.md`, both in Uzbek.




bu portalni desingni figmada qayta chizish kerak. @reference da imagelar bor bulardan extend olish
  kerak. lekin tayyor ko'chirmang. yechimdan foydalnib bu sohaga qo'llang. wf sohasiga. figmada image
  6 bor shuni bg qiling va ozroq blur berish kerak ilova avtiveda  20 yillik ui /ux ga o'xshab
  ishlang. /ui-ux-pro-max
  https://www.figma.com/design/VksyIdXwu9nooj7YnaHN28/sola-mobile?node-id=152-88&m=draw   file logolar
  bor laptop va mobile planshetga moslang. yaxshi yechim bering.


 AI ham qo'shib bering. chat support va AI boshqaruv shu yerda bo'ladi. Ai orqali boshqaradi. Aini
  ovozli variantini ham chizib bering. account va settingsda ham chizing



desingni yakunlang bazi joylari qolgan. /forge-product-owner  va /ui-ux-pro-max bo'lib ishlang.
  productni yakunlang
