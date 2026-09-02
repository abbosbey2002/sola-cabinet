# Sola cabinet

Laravel 13 abonent kabineti (`lk.sola.uz`). Ma’lumot billing API dan keladi; lokal SQLite faqat admin / tarif ruxsatnomasi / Telescope uchun.

Batafsil: [DOCKER.md](DOCKER.md).

## Talab

- Docker + Compose
- Node 20+ (hostda — konteynerda Node yo‘q)
- PHP 8.3+ faqat test/Pint hostda yurmoqchi bo‘lsangiz

## Ishga tushirish

```bash
cp .env.example .env
docker compose up -d --build
docker compose exec -u www-data php composer install
docker compose exec -u www-data php php artisan key:generate
touch database/database.sqlite
docker compose exec -u www-data php php artisan migrate
npm ci && npm run build
```

Sayt: http://localhost:8080

`docker compose exec` **har doim** `-u www-data` bilan. Rootda qoldirilgan `storage/` / `bootstrap/cache` keyin PHP-FPM ga yozilmaydi.

CSS/JS `public/build/` da (gitda yo‘q). Build qilinmasa sahifa ochilmaydi. Dizayn ustida:

```bash
npm run dev
```

## Billing API

VPN / ichki tarmoq bo‘lmasa har sahifa 503. Lokal ishlash:

```env
SOLA_FAKE=true
```

so‘ng `php artisan config:clear`. Faqat `APP_ENV=local`.

SMS ni o‘tkazib, qolganini jonli API ga: `SOLA_FAKE_LOGIN=true` (VPN kerak).

## Test

```bash
php artisan test
./vendor/bin/pint --test
```
