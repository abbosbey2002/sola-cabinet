# Sola Portal — развёртывание на сервере

> 🇺🇿 O'zbekcha versiya: [DEPLOY.uz.md](DEPLOY.uz.md)
> 🆕 **Пустой сервер, установка с нуля (пошагово, с готовыми командами):** [SERVER-SETUP.ru.md](SERVER-SETUP.ru.md)

Laravel 9 + MongoDB, без Docker. Документ описывает только то, что **специфично для этого проекта** — обычная часть работы с сервером, архивом и базой опущена.

**Передаются:** `sola-portal_v<версия>_<дата>.tar.gz` + `portal_sola_<дата>.archive` (дамп MongoDB) + этот документ.

---

## 1. Ограничения по версиям

| Компонент | Версия | Почему важно |
|---|---|---|
| PHP-FPM | **8.0.2 – 8.2** | Laravel 9 не запускается на PHP 8.3+ — не ставьте самую свежую |
| MongoDB | **7.0** | с ней проверен `jenssegers/mongodb ^3.9` |
| Redis | ≥ 7 | queue + cache + session — всё три на нём |

Расширения PHP через `pecl`: **`mongodb`** и **`redis`** (остальные стандартные: `gd curl mbstring xml bcmath intl zip`).

**Node.js и Composer не нужны** — в архиве уже лежат `vendor/` и собранные `public/css`, `public/js`.

---

## 2. Nginx

Обычный конфиг Laravel (`root` → `public/`), добавляется одна строка:

```nginx
client_max_body_size 1032M;
```

Без неё из дашборда нельзя загрузить рекламный баннер и картинки флагов (`413 Request Entity Too Large`).

---

## 3. `.env` — строки, которые меняются на сервере

Все остальные переменные остаются как в шаблоне.

| Переменная | Значение |
|---|---|
| `APP_URL` | **Реальный домен** — `https://portal.sola.uz`. `asset()` строит от него пути к CSS/JS/картинкам; при ошибке вёрстка страницы разваливается |
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |
| `API_URL` | Адрес биллингового API провайдера. **Если пусто — приложение не стартует вообще** (`RuntimeException`) |
| `DB_HOST` / `DB_PORT` | `127.0.0.1` / `27017` |
| `DB_USERNAME` / `DB_PASSWORD` | Пользователь Mongo на сервере |
| `DB_AUTHENTICATION_DATABASE` | **В какой базе** заведён пользователь. При ошибке — `Authentication failed` |
| `REDIS_HOST` / `REDIS_PORT` | `127.0.0.1` / `6379` |
| `LOG_LEVEL` | `warning` (на проде `debug` забивает диск) |

**Не пересоздавайте `APP_KEY`.** Он уже есть в `.env`. Если выполнить `php artisan key:generate` — все сессии слетят, пользователей выкинет из системы.

Три ловушки:

| Переменная | Что произойдёт |
|---|---|
| `CLICK_SERCRET_KEY` | Код читает именно это имя — **с опечаткой (`SERCRET`)**. Если «исправить» на `CLICK_SECRET_KEY`, подпись Click соберётся с пустым ключом и оплата не сработает |
| `PAYME_ACTIVE` | В шаблонах **отсутствует**. Без `PAYME_ACTIVE=true` способ оплаты Payme не появится на странице |
| `TELESCOPE_ENABLED` | Значение по умолчанию в конфиге — **`true`**. Если переменной нет в `.env`, debug-панель включится на проде и SQLite-файл будет расти бесконечно. Должно быть `false`. Даже во включённом виде `/telescope` доступен только админу, вошедшему в дашборд — остальных редиректит на страницу входа |

---

## 4. Запуск

```bash
cd /var/www/sola-portal

php artisan migrate --force
php artisan config:clear && php artisan route:clear && php artisan view:clear

sudo chown -R www-data:www-data storage bootstrap/cache public/uploads
sudo chmod -R 775 storage bootstrap/cache public/uploads
```

**Queue worker** — готовый конфиг лежит в архиве (`docs/deploy/sola-queue.conf`), команда в нём `php artisan queue:work redis --tries=3 --max-time=3600`:

```bash
sudo cp docs/deploy/sola-queue.conf /etc/supervisor/conf.d/sola-queue.conf
sudo supervisorctl reread && sudo supervisorctl update && sudo supervisorctl start sola-queue:*
```

Без него портал работает, но **статистика не собирается** — события посещений, просмотров видео и авторизаций зависают в очереди.

**Scheduler** — crontab пользователя `www-data`:

```cron
* * * * * cd /var/www/sola-portal && php artisan schedule:run >> /dev/null 2>&1
```

Каждый день в 00:05 пересчитывает статистику за вчера. Без него дневной отчёт **молча** остаётся неверным — никакой ошибки не будет.

---

## 5. При обновлении

`.env` и `public/uploads/` в архив **не входят** — переносятся из старой папки:

```bash
cp sola-portal.old/.env sola-portal/.env
cp -a sola-portal.old/public/uploads sola-portal/public/
```

Затем команды из раздела 4 и **обязательно**:

```bash
sudo supervisorctl restart sola-queue:*
```

Без перезапуска worker продолжит работать старым кодом.

Старую папку оставляйте под именем `sola-portal.old` — откат в одну команду:
`rm -rf sola-portal && mv sola-portal.old sola-portal`

---

## 6. НЕ выполняйте `php artisan config:cache`

Самый важный пункт. В отдельных местах кода `env()` вызывается напрямую:
`resources/views/site/payment/index.blade.php:24` (`PAYME_ACTIVE`) и layout дашборда (`APP_NAME`).

При закешированном конфиге `env()` возвращает **`null`** (проверено). В результате:

- **способ оплаты Payme исчезает со страницы** — `@if(env('PAYME_ACTIVE'))` никогда не истинно
- заголовок дашборда выводится пустым

Никакой ошибки в логах не будет — функциональность просто пропадёт. `route:cache` и `view:cache` при этом безопасны.
