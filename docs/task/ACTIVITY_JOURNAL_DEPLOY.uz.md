# Harakatlar jurnali — serverga chiqarish

Kabinet (`lk.sola.uz`) uchun. Reja: `docs/plans/2026-09-23_activity-log-and-stats.md`.

Jurnal abonentning telefoni, F.I.Sh. va IP manzilini saqlaydi. **Production'da
`ACTIVITY_ENABLED=true` faqat yurist ZRU-547 bo'yicha tasdiqlagandan va maxfiylik
siyosatiga matn qo'shilgandan keyin.** Kod o'chiq holatda deploy qilinadi va hech
narsa yozmaydi.

## 1. PostgreSQL (bir marta)

Yuklama kichik (kuniga 5 000 gacha faol abonent — bitta server bemalol ko'taradi),
shuning uchun kabinet turgan serverning o'zida bitta PostgreSQL yetarli.
Ma'lumot O'zbekistondagi serverda qoladi.

```bash
sudo apt install postgresql php8.4-pgsql        # PHP versiyasi serverdagiga mos
sudo systemctl restart php8.4-fpm
sudo -u postgres createuser --pwprompt cabinet
sudo -u postgres createdb --owner=cabinet cabinet_activity
```

`php -m | grep pdo_pgsql` — chiqmasa, deploy jurnalsiz davom etadi.

## 2. `.env`

```env
ACTIVITY_ENABLED=true
ACTIVITY_DB_HOST=127.0.0.1
ACTIVITY_DB_PORT=5432
ACTIVITY_DB_DATABASE=cabinet_activity
ACTIVITY_DB_USERNAME=cabinet
ACTIVITY_DB_PASSWORD=<kuchli parol>
```

So'ng `php artisan config:clear && php artisan activity:migrate --force`.
Keyingi deploylarda `scripts/deploy.sh` buni o'zi bajaradi. Baza ishlamasa ham
deploy to'xtamaydi — log'da `WARNING: activity:migrate failed` chiqadi.

## 3. Cron (majburiy)

```cron
* * * * * cd <loyiha papkasi> && php artisan schedule:run >> /dev/null 2>&1
```

Har kuni 00:30 UTC da `activity:rollup` (kunlik va oylik yig'indi), 01:00 da
`activity:prune` (saqlash muddati: hodisalar 12 oy, tarif tarixi va admin audit
3 yil; javobsiz qolgan `pending` qatorlar `unknown` bo'ladi). Cron bo'lmasa
"Oylar bo'yicha" jadvali bo'sh qoladi va baza cheksiz o'sadi — xato chiqmaydi.

## 4. GeoIP (ixtiyoriy, oyiga bir marta)

Shahar ustuni uchun MaxMind GeoLite2-City bazasi (bepul, ro'yxatdan o'tish kerak).
IP tashqariga yuborilmaydi.

```bash
mkdir -p storage/app/geoip
# maxmind.com dan GeoLite2-City.mmdb ni yuklab, shu yerga qo'ying:
#   storage/app/geoip/GeoLite2-City.mmdb
```

Oyiga bir marta yangilang (masalan `geoipupdate` cron'i). Fayl bo'lmasa shahar
ustuni bo'sh qoladi, boshqa hech narsa buzilmaydi.

## 5. Zaxira nusxa

```cron
15 2 * * * pg_dump -U cabinet -Fc cabinet_activity > /var/backups/cabinet_activity_$(date +\%u).dump
```

Haftaning 7 kuni aylanadi. Tiklash sinab ko'rilmaguncha zaxira bor deb hisoblanmaydi:
`pg_restore -U cabinet -d cabinet_activity_test /var/backups/cabinet_activity_1.dump`.

## 6. Adminlar va rollar

```bash
php artisan admin:create ali --role=analyst     # admin | analyst | sales
php artisan admin:role ali sales
```

Mavjud adminlar migratsiyadan keyin `admin` roliga ega bo'ladi (avvalgidek hamma narsani ko'radi).

## Orqaga qaytarish

- Jurnalni o'chirish: `.env` da `ACTIVITY_ENABLED=false` + `php artisan config:clear` — bir daqiqa, kabinet ta'sirlanmaydi.
- Jadvallarni olib tashlash: `php artisan migrate:rollback --database=activity --path=database/migrations/activity --force`.
- `admins.role` ustuni: `php artisan migrate:rollback --step=1 --force`.
