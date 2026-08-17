# SOLA API unreachable — cURL error 28

- **Sana:** 2026-08-07 15:39 (+05)
- **Simptom:** Loginda 503 sahifasi. Log:
  `SOLA API unreachable {"endpoint":"/identify","reason":"cURL error 28: Connection timed out after 3002 milliseconds ... http://172.19.1.101:808/identify"}`

Bitta simptom ortida **ikkita mustaqil bug** chiqdi.

---

## Bug 1 — Docker bridge subnet to'qnashuvi (asosiy sabab)

### Tasdiqlangan sabab

`docker compose` `cabinet_default` tarmog'iga **`172.19.0.0/16`** ni avtomatik
bergan. Billing API manzili `.env` da `API_IP=172.19.1.101:808` — aynan shu
diapazon **ichida**.

Natijada konteyner API manzilini o'z bridge'idagi to'g'ridan-to'g'ri qo'shni deb
hisoblab, uni eth0 ga ARP qilgan. Javob bo'lmagan, TCP handshake boshlanmagan,
`connect_timeout=3` ishlab `cURL error 28` bergan — logdagi 3002 ms aynan shu.

### Buni tasdiqlagan dalillar

Konteyner `/proc/net/route` (iproute2 yo'q, xom jadval):

```
eth0  000013AC  00000000  0001  ...  0000FFFF     # 172.19.0.0/16, gateway YO'Q (link-scope)
```

Host ham zaharlangan edi:

```
$ ip route get 172.19.1.101
172.19.1.101 dev br-ecf589ee5f61 src 172.19.0.1     # VPN/LAN emas, Docker bridge'ga
```

`docker info` da `DefaultAddressPools = null` → Docker o'rnatilgan
`172.17.0.0/16 … 172.31.0.0/16` poolidan foydalanadi, ya'ni to'qnashuv
**tasodifiy emas va takrorlanadi**: bu mashinada allaqachon 12 ta loyiha
172.17–172.29 oralig'ini band qilgan.

### Tuzatish

`docker-compose.yml` da tarmoq nomlangan va subnet qotirilgan:
`10.123.0.0/24` — `172.16.0.0/12` (Sola ichki tarmog'i), `10.8.0.0/24`
(lokal OpenVPN) va `192.168.0.0/16` (LAN) dan tashqarida. Sabab fayl ichida
izohda yozilgan, toki keyinchalik kimdir "tozalab" tashlamasin.

Eski `cabinet_default` (172.19.0.0/16) tarmog'i `docker compose down` dan keyin
ham qolib ketgan va host marshrutini buzishda davom etgan — qo'lda o'chirildi.

### Tuzatishdan keyingi dalillar

```
$ ip route get 172.19.1.101                    # host
172.19.1.101 via 192.168.0.1 dev wlo1          # endi haqiqiy default marshrut

# konteyner /proc/net/route — 172.19 marshruti umuman yo'q, faqat:
eth0  00000000  01007B0A   # default via 10.123.0.1
eth0  00007B0A  00000000   # 10.123.0.0/24 link-scope
```

Konteyner ARP keshida `172.19.1.101` uchun yozuv yo'q (faqat gateway) — ya'ni
paketlar endi bridge'da qolmay, gateway orqali chiqmoqda.

Uchdan-uchga:

```
errno=0  http=403  connect_time=0.045s  primary_ip=172.19.1.101
Server: Apache/2.4.10 (Unix) PHP/5.3.29
```

3002 ms timeout → **45 ms** javob, haqiqiy SOLA serveridan. (403 — o'sha xom
tekshiruvda imzo yuborilmagani uchun to'g'ri javob.)

Ilova qatlamida, asl yiqilgan endpoint:

```
/identify → status=400, 269 ms, code=110 "Абонент не найден"   (soxta raqam uchun to'g'ri)
```

### Regressiya qo'riqchisi

Yo'q — bu infratuzilma konfiguratsiyasi, PHP testi bilan ushlab bo'lmaydi.
O'rniga: subnet compose faylida qotirilgan + sababi izohda + `DOCKER.md` da
ogohlantirish bo'limi.

---

## Bug 2 — retry() biznes-xatolarni exception'ga aylantirardi

Bu birinchisini tuzatgandan **keyin** ochildi: API endi javob bera boshlagach,
`abonentInfo("0")` `SolaResponse` emas, `RequestException` qaytardi.

### Tasdiqlangan sabab

`Illuminate\Http\Client\PendingRequest::retry()` ning to'rtinchi parametri
`bool $throw = true`. `PendingRequest.php:1104`:

```php
if ($potentialTries > 1 && $this->retryThrow) {
    $response->throw();
}
```

`SOLA_RETRY_TIMES=2` bo'lgani uchun `tries > 1` → **har qanday** non-2xx javob
otiladi. `SolaClient::post()` esa faqat `ConnectionException` ni ushlaydi
(`SolaClient.php:193`), shuning uchun `RequestException` o'tib ketib 500 sahifa
bergan.

### Izolyatsiya

Bir xil so'rov, bir xil 4xx javob, yagona farq — `retry()`:

```
retrysiz         -> QAYTDI, status=403
retry(2) bilan   -> OTILDI RequestException
```

### Ta'sir doirasi

Faqat **idempotent** chaqiruvlar `retry()` oladi, ya'ni:
`accounts`, `abonentInfo`, `devices`, `payments`, `trafficDetail`,
`availableTariffs`.

Ular uchun mo'ljallangan yo'l — `SolaResponse::failed()` / `errorCode()` /
`ErrorMessages::for()` va `CabinetController` dagi
`abort_if($deviceList->failed(), 502)` — **o'lik kod** edi: abonent tarjima
qilingan xabar yoki 502 o'rniga 500 olardi.

Mutatsiyalar (`identify`, `verify`, `connectTariff`, `addDevice`,
`deleteDevice`) `idempotent: false` bilan ketadi, retry olmaydi — shuning uchun
login xatolari to'g'ri ishlagan va bug sezilmay qolgan.

### Tuzatish

`SolaClient::request()` da `retry(..., throw: false)`. Bir so'zlik minimal
o'zgarish: retry faqat `ConnectionException` da ishlaydi (bu ilgari ham shunday
edi), qolgan hollarda javob normal qaytadi.

Tuzatishdan keyin jonli API'ga qarshi:

```
QAYTDI  status=400  failed=true  code=114  msg=Не указан (или неправильно указан) объязательный параметр (Лицевой счёт)
```

### Regressiya qo'riqchisi

`tests/Unit/SolaClientTest.php` ga ikkita test:

- `a_business_error_on_a_retried_read_is_returned_rather_than_thrown`
- `a_business_error_on_a_read_is_not_retried`

Ikkalasi ham tuzatish olib tashlanganda **yiqiladi**
(`RequestException`, `SolaClient.php:192`) — tekshirib ko'rildi.

> **Nega mavjud test ushlamagan:** `a_business_error_is_returned_rather_than_thrown`
> `verify()` ni ishlatadi — u mutatsiya, ya'ni unga `retry()` hech qachon
> qo'llanmaydi. Test aynan buzilishi mumkin bo'lmagan yo'lni tekshirgan.
> Yangi testlar idempotent endpoint (`abonentInfo`) ishlatadi.

---

## Bog'liq xavflar

- **Boshqa loyihalar.** Bu mashinada 12 ta Docker tarmog'i 172.17–172.29 ni band
  qilgan. Sola ichki tarmog'iga murojaat qiladigan har qanday boshqa loyiha
  (`sola-cabinet_sola` → 172.27.0.0/16, `sola-premium-project-v2` → 172.22.0.0/16)
  xuddi shu tarzda yiqilishi mumkin. Mashina darajasidagi yechim `DOCKER.md` da.
- **`retry(throw:)` namunasi.** Loyihada `retry()` boshqa joyda ishlatilmaydi
  (tekshirildi), lekin kelajakda qo'shilsa xuddi shu tuzoq bor.
- **Bu passda o'zgartirilmadi:** `identify` ga `sendsms=1` bilan qilingan har bir
  urinish abonentga SMS yuboradi va u retry olmaydi — to'g'ri. Lekin login
  formasi throttle'i 5/min; SMS narxi hisobga olinsa, buni kamaytirish
  ko'rib chiqilishi mumkin. Bu bug emas, kuzatuv.

## Frontend migratsiyasi bilan bog'liqmi?

Yo'q. `SolaClient`, `.env`, tarmoq konfiguratsiyasi UI ishida umuman
tegilmagan. Log vaqti chalg'ituvchi ko'rinadi (`10:30:20` vs mahalliy 15:30) —
sabab `APP_TIMEZONE=UTC`, mashina esa `+05`. Ya'ni yozuvlar aynan yangi UI
sinab ko'rilgan paytga to'g'ri keladi, lekin sababi butunlay boshqa.

## Holat

- `php artisan test` — **31/31** o'tdi (29 + 2 yangi)
- `./vendor/bin/pint --test` — passed
- `GET /auth/login` → 200
- `/identify` → 269 ms, to'g'ri biznes-javobi
