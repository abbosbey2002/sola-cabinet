# Sola Cabinet — lokal Docker muhiti

`cabinet.sola.uz` serveridan (`/var/www/html`) ko'chirilgan ilova.
Laravel **13**, PHP **8.4**.

## Ishga tushirish

```bash
docker compose up -d --build
docker compose exec php composer install
docker compose exec php php artisan key:generate   # faqat .env da APP_KEY bo'lmasa
```

Sayt: http://localhost:8080

## Frontend (Vite + Tailwind)

Interfeys Tailwind CSS 4 va Vite bilan yig'iladi. Node **20+** kerak.
Docker konteynerida Node yo'q — build **host mashinada** bajariladi
(`public/` katalogi konteynerga bind-mount qilingan).

```bash
npm ci
npm run build      # public/build/ ni yaratadi
```

Dizayn ustida ishlaganda hot-reload:

```bash
npm run dev        # ochiq qolsin, http://localhost:8080 avtomatik yangilanadi
```

> **Diqqat:** `public/build/` `.gitignore` da. `npm run build` bajarilmasa
> ilova `Vite manifest not found` xatosi bilan ishga tushmaydi. Deploy
> ketma-ketligiga qo'shishni unutmang (pastga qarang).

## Servislar

| Servis | Image | Port |
|---|---|---|
| nginx | nginx:1.27-alpine | 8080 → 80 |
| php   | php:8.4-fpm (build) | 9000 (ichki) |

Baza servisi yo'q — ilova hech qanday ma'lumotlar bazasidan foydalanmaydi
(pastga qarang).

## Telescope (faqat local)

Debugging uchun Laravel Telescope o'rnatilgan: http://localhost:8080/telescope

Eng foydali paneli — **HTTP Client**: ilovaning butun mazmuni billing API'ga
qilingan chaqiruvlar, shuning uchun har bir so'rov, javob, status va davomiylik
shu yerda ko'rinadi.

### Ma'lumot qayerda saqlanadi

Ilovaning o'zi bazadan foydalanmaydi, shuning uchun Telescope uchun alohida
**SQLite fayl** ishlatiladi: `database/database.sqlite` (git'ga tushmaydi,
`database/.gitignore` da). Server ham, konteyner ham kerak emas — `pdo_sqlite`
php:8.4-fpm image'ida allaqachon bor.

Birinchi marta o'rnatishda:

```bash
touch database/database.sqlite
docker compose exec php php artisan migrate
```

### Nega production'ga chiqmaydi

Uch qatlamli himoya:

1. `composer.json` da `require-dev` — `composer install --no-dev` bilan umuman
   o'rnatilmaydi.
2. `extra.laravel.dont-discover` da — avtomatik ro'yxatdan o'tmaydi.
3. `AppServiceProvider::registerTelescope()` uni faqat `APP_ENV=local` da
   ro'yxatdan o'tkazadi. Boshqa muhitda `/telescope` marshrutlari umuman
   yaratilmaydi (tekshirilgan).

### Maxfiy ma'lumotlar

`app/Providers/TelescopeServiceProvider.php` **har qanday muhitda** quyidagilarni
yashiradi (Laravel'ning standart stub'i local'da yashirmaydi — bu loyiha uchun
xato bo'lardi):

- `authorization` header — SOLA servis akkaunti paroli (base64, shifr emas);
- `x-access-token` — so'rov imzosi (uni qayta yuborish chaqiruvni takrorlaydi);
- `cookie` / `set-cookie`, CSRF tokenlar;
- `code`, `smsCode` — bir martalik SMS kodi;
- `password`, `curr_password`, `new_password`, `_token`.

Hisob raqam va telefon **ataylab ochiq qoldirilgan** — ular kredensial emas,
abonentni izlashda kerak bo'ladi. Fayl local'da yotadi va git'ga tushmaydi.

### Tozalash

Bitta sahifa yuklanishi ~30 ta yozuv beradi. Kunlik `telescope:prune` jadvalga
qo'shilgan, lekin Docker'da cron yo'q — shuning uchun fayl kattalashsa qo'lda:

```bash
docker compose exec php php artisan telescope:prune --hours=48
docker compose exec php php artisan telescope:clear   # hammasini o'chirish
```

## Artisan / composer / testlar

```bash
docker compose exec php php artisan <buyruq>
docker compose exec php php artisan test
docker compose exec php ./vendor/bin/pint        # kod stili
```

## Muhim: API bog'liqligi

Ilova bazadan foydalanmaydi — barcha ma'lumot (abonent, tarif, to'lov, trafik)
`.env` dagi `API_IP=172.19.1.101:808` ichki billing API'sidan olinadi.
Bu manzil Sola ichki tarmog'ida — unga yo'l bo'lgan tarmoqdan turib ishlaydi,
aks holda so'rovlar `cURL error 28` bilan uziladi.

### ⚠️ Docker subnet to'qnashuvi

`docker-compose.yml` dagi tarmoq subneti **ataylab `10.123.0.0/24` ga qotirilgan**.
Docker'ning standart pooli `172.17.0.0/16 … 172.31.0.0/16` bo'lib, billing API
manzili (`172.19.1.101`) aynan shu diapazon ichida. Docker loyihaga
`172.19.0.0/16` ni bergan paytda konteyner API manzilini **o'z bridge'idagi
qo'shni** deb hisoblaydi, ARP javobsiz qoladi va har bir so'rov 3 soniyadan
keyin `cURL error 28: Connection timed out` bilan o'ladi.

`networks:` blokini olib tashlamang va subnetni `172.16.0.0/12` ichiga
ko'chirmang. Batafsil: `docs/forge-debugger/`.

Agar shunga o'xshash to'qnashuv boshqa loyihalarda ham chiqsa, mashina
darajasida `/etc/docker/daemon.json` ga qo'shish mumkin:

```json
{ "default-address-pools": [{ "base": "10.200.0.0/12", "size": 24 }] }
```

Sessiya ham, kesh ham fayl drayverida (`storage/framework`), shuning uchun
MySQL/Redis kerak emas. Laravel'ning standart skeletidan qolgan `users` /
`password_resets` migratsiyalari va `User` modeli o'chirildi — ular hech qachon
ishlatilmagan.

API'siz ham to'liq tekshirish mumkin — testlar soxta (fake) API bilan ishlaydi:

```bash
php artisan test
```

### VPN'siz ishlash — `SOLA_FAKE`

Billing API Sola ichki tarmog'ida bo'lgani uchun VPN'dan tashqarida har bir
chaqiruv `cURL error 28` bilan uziladi, ilovada esa lokal baza yo'q — natijada
**har bir sahifa 503** qaytaradi (`bootstrap/app.php`). Interfeys ustida
ishlash uchun `.env` da:

```env
SOLA_FAKE=true
```

`php artisan config:clear` — va API `App\Services\Sola\FakeSolaServer` tomonidan
xotirada javob beradi.

Bu **SolaClient'ning ikkinchi nusxasi emas**: soxta server HTTP klient
darajasiga ulanadi, ya'ni Basic auth, `X-Access-Token` imzosi, retry qoidalari
va `ConnectionException` ishlovi — hammasi production'dagidek ishlaydi, faqat
tarmoq o'rniga xotira javob beradi. Production yo'lida hech narsa o'zgarmaydi.

Nima ishlaydi:

| | |
|---|---|
| Telefon | istalgan raqam qabul qilinadi |
| `998900000000` | "Абонент не найден" (kod 110) — xato ekrani uchun |
| SMS kod | istalgan 4 raqam; `0000` → "Не верный код" (kod 120) |
| Akkauntlar | 1001 (doimiy, tip 2) va 1002 (bir martalik, tip 1) — tanlash ekrani ham ko'rinadi |
| Qurilmalar | qo'shish/o'chirish saqlanadi; `77` — shartnoma liniyasi, o'chirilmaydi; limit 5 ta |
| Tarif | almashtirish "keyingi tarif" sifatida yoziladi va dashboard'da ko'rinadi |
| Trafik / to'lovlar | so'ralgan oy uchun generatsiya qilinadi, kelajak sanalarsiz |

Raqamlar `rand()` emas, `crc32` orqali hosil qilinadi — sahifani yangilaganda
o'zgarmaydi, skrinshotlar barqaror. Boshlang'ich holatga qaytarish:

```bash
docker compose exec php php artisan cache:clear
```

> Faqat `APP_ENV=local` da ishlaydi. Deploy qilingan hostda `.env` ga
> `SOLA_FAKE=true` yozilib qolsa ham soxta ma'lumot ko'rsatilmaydi
> (`AppServiceProvider::installFakeBilling()`).

## Fayllar

- `docker-compose.yml`
- `docker/php/Dockerfile` — php:8.4-fpm + gd, zip, exif, bcmath, opcache
- `docker/nginx/default.conf` — serverdagi konfig asosida
- `.env` — lokal uchun moslangan (APP_DEBUG=true, port 8080)
- `.env.example` — namuna
- `.env.server-original` — serverdagi asl `.env` nusxasi

## Serverga chiqarishdan oldin

Laravel 11+ da ba'zi `.env` kalitlari nomi o'zgargan. Serverdagi `.env` ga:

```diff
-CACHE_DRIVER=file
+CACHE_STORE=file
+APP_LOCALE=ru
+APP_FALLBACK_LOCALE=ru
```

`CACHE_STORE` qo'yilmasa Laravel 13 keshni `database` drayverida qidiradi va
xato beradi — bu deploy'ni buzadigan yagona `.env` o'zgarishi.

`APP_ENV=prodaction` — serverdagi `.env` da imlo xatosi (`production` bo'lishi
kerak). Kod hozircha bunga bog'liq emas, lekin tuzatilgani ma'qul.

`DB_*`, `REDIS_*`, `MAIL_*`, `PUSHER_*`, `AWS_*` kalitlari endi ishlatilmaydi.

Deploy ketma-ketligi:

```bash
composer install --no-dev --optimize-autoloader
npm ci && npm run build     # public/build/ — busiz sahifalar ochilmaydi
php artisan optimize        # config + route + view cache
```

Agar serverda Node bo'lmasa, `public/build/` ni lokalda yig'ib, o'sha katalogni
serverga nusxalash kifoya — boshqa hech narsa kerak emas.
