# SOLA Billing API — amaldagi holat (reverse-documented)

Bu hujjat **haqiqiy trafik** asosida yozilgan: Laravel Telescope
(`/telescope/client-requests`) da qayd etilgan 29 ta chaqiruvning so'rov va javob
juftliklari. Rasmiy billing hujjati emas — **kuzatilgan xatti-harakat**.

**Baza:** `http://172.19.1.101:808` (`API_IP`) · **Server:** Apache 2.4.10 / PHP 5.3.29
**Qamrov:** 7 ta endpoint tekshirildi, 3 tasi (`/tariff/connect`, `/device/new`,
`/device/delete`) hali chaqirilmagan — ular kod bo'yicha yozilgan va tasdiqlanishi kerak.
Namunalardagi shaxsiy ma'lumotlar (acc_id, telefon, MAC) o'zgartirilgan.

> **To'liq ro'yxat:** [`SOLA_API_REFERENCE.md`](SOLA_API_REFERENCE.md) — gateway
> manbasidan (`docs/task/apipc/`) yozilgan **19 ta** endpoint, validatsiya
> qoidalari va xato kodlarining to'liq spravochnigi. Bu hujjat esa **kuzatilgan
> xatti-harakat** — haqiqiy qiymatlar, tiplar va g'alatiliklar uchun shu ustun.

## Umumiy qoidalar

| | |
|---|---|
| Metod | Barcha endpointlar uchun `POST` |
| Content-Type | `application/json` |
| Autentifikatsiya | HTTP Basic (`Authorization`) |
| Imzo | `X-Access-Token: md5("<username> <secret_key> <request_body>")` — **so'rov tanasining aynan o'sha baytlari** ustidan |
| Til | Har bir so'rovda `lang`: `ru` \| `uz` \| `en` |
| Muvaffaqiyat | HTTP `200` + JSON |
| Xato | HTTP `400` + `{"code": <int>, "errMsg": "<matn>"}` |
| Javob vaqti | 79–408 ms (o'lchangan): `/abonent/info` ≈130 ms, `/acct/payments` ≈360 ms |

**Muhim: tiplar.** Javobda deyarli barcha raqamlar **string** sifatida keladi
(`"saldo": "19000"`, `"device_count": "1"`, `"traffic_input": "2359086"`).
Yagona istisno — `cost` va `amount`: ular **int**. Klient bu ikkalasiga ham
tayyor bo'lishi kerak.

**Pul birliklari izchil emas:**

| Maydon | Birlik | Misol |
|---|---|---|
| `saldo` (`/abonent/info`) | **so'm** | `"19000"` → 19 000 so'm |
| `cost` (`/tariff/available`) | **tiyin** | `200000` → 2 000 so'm |
| `amount` (`/acct/payments`) | **tiyin** | `2500000` → 25 000 so'm |

`saldo` birligi billing hujjatida tasdiqlanmagan — eski va yangi kabinet uni
so'm sifatida ko'rsatadi (`AbonentProfile::balance()`, 100 ga bo'linmaydi).

---

## 1. `/identify` — abonentni topish va SMS yuborish

**So'rov:** `phn` (string, `998XXXXXXXXX`), `lang`, `sendsms` (`1` — SMS yuborish, `0` — faqat ro'yxat)

```json
{"phn": "998901234567", "lang": "uz", "sendsms": 1}
```

**Javob 200:** `accs[]`

| Maydon | Tur | Misol | Izoh |
|---|---|---|---|
| `accId` | string | `"1234567"` | Keyingi barcha so'rovlarda `acc_id` |
| `login` | string | `"998901234567"` | Odatda telefon raqami |
| `abonType` | string | `"1"` | Qiymatlar ro'yxati noma'lum |
| `abonName` | string | `""` | Kuzatilgan holatda bo'sh |

**Javob 400:** `{"code": 110, "errMsg": "Абонент не найден"}` — bu so'rov `lang=ru`
bilan yuborilgan, shuning uchun `errMsg` boshqa tillarda tarjima qilinadimi — noma'lum.

## 2. `/verify` — SMS kodini tekshirish

**So'rov:** `phn`, `smsCode` (string), `lang`

**Javob 200:** **tana bo'sh** (`Empty Response`). Muvaffaqiyat faqat HTTP status
bilan aniqlanadi — javobda token ham, sessiya ham yo'q.

## 3. `/abonent/info` — abonent kartochkasi

**So'rov:** `acc_id`, `lang`

```json
{
  "name": null,
  "email": null,
  "phone": null,
  "address": "Ташкент Мирабадский р-н",
  "status": "Активен",
  "saldo": "19000",
  "contract_date": "2019-01-11",
  "curr_tariff_id": "4",
  "curr_tariff_name": "Paket 2 soat",
  "device_count": "1",
  "device_active_count": "0"
}
```

| Maydon | Tur | Izoh |
|---|---|---|
| `name`, `email`, `phone` | string \| **null** | Test hisobida uchalasi ham `null` keldi — klient buni hisobga olishi shart |
| `address` | string | `lang=uz` bo'lsa ham rus tilida qaytdi |
| `status` | string | Erkin matn (`"Активен"`), kod emas |
| `saldo` | string | So'mda (yuqoriga qarang) |
| `contract_date` | `Y-m-d` | Shartnoma **sanasi**; raqami yo'q |
| `curr_tariff_id` | string | Joriy tarifni ro'yxatda topish uchun ishlatiladi (nom ishonchsiz — pastga qarang) |
| `device_count` / `device_active_count` | string | Butun son matn sifatida |

**Yo'q maydonlar** (TZ talab qiladi): `contract_number`, `curr_tariff_cost`,
`next_tariff_name`, `next_tariff_cost`, `next_charge_date` —
`docs/task/BILLING_API_TALABLARI.md` ga qarang.

## 4. `/device/list` — qurilmalar

**So'rov:** `acc_id`, `lang` · **Javob:** `devices[]` + `connect_cost`

| Maydon | Tur | Misol | Izoh |
|---|---|---|---|
| `permit_id` | string | `"324104"` | O'chirishda ishlatiladi |
| `mac` | string | `"A1B2C3D4E5F6"` | Ajratgichsiz |
| `ip` | string \| null | `null` | Offlayn qurilmada `null` |
| `connect_date` | `Y-m-d` | `"2026-08-05"` | |
| `readonly` | string | `"1"` | `"1"` — o'chirib bo'lmaydi |
| `connect_cost` | string | `"-1"` | Massivdan tashqarida, tiyinda deb qabul qilingan. `-1` ma'nosi tasdiqlanmagan — kabinet bunday holatda narxni umuman ko'rsatmaydi |

## 5. `/tariff/available` — mavjud tariflar

**So'rov:** `acc_id`, `lang` · **Javob:** `tariffs[]`

| Maydon | Tur | Misol | Izoh |
|---|---|---|---|
| `tariff_id` | string | `"839"` | `/tariff/connect` ga **int** sifatida yuboriladi |
| `tariff_name` | string | `"Paket 5 soat "` | Oxirida ortiqcha probel uchraydi — trim kerak |
| `cost` | **int** | `300000` | Tiyinda |
| `tspd` / `spdu` | string | `"500"` / `"Mbps"` | Tezlik va uning birligi |
| `tprd` / `prdu` | string | `"5"` / `"HOUR"` | Amal qilish muddati va birligi (`HOUR`, `DAY`) |
| `vol` | string | `"0"` | Trafik hajmi limiti; `0` — cheksiz |

Ro'yxatda **joriy tarif ham bor** (`tariff_id=4` = `curr_tariff_id`), lekin bunga
kafolat yo'q — shuning uchun joriy tarif narxini shu ro'yxatdan olish ishonchsiz.
Moslashtirish **faqat `tariff_id` bo'yicha** qilinadi: `tariff_name` da ortiqcha
probel uchragani uchun nom bo'yicha solishtirish joriy tarifni yo'qotadi.

`tprd`/`prdu`/`vol` tarif kartochkasida ham, tanlash ro'yxatida ham ko'rsatiladi.

## 6. `/traffic/detail` — trafik detalizatsiyasi

**So'rov:** `acc_id`, `detail_month` (`Y-m`), `lang` · **Javob:** `detail[]`

| Maydon | Tur | Misol | Izoh |
|---|---|---|---|
| `event_time` | `Y-m-d H:i:s` | `"2026-08-05 20:13:39"` | Sessiya vaqti |
| `traffic_input` | string | `"2359086"` | **Bayt** |
| `traffic_output` | string | `"218702401"` | **Bayt** |
| `pocket_info` | string | `"Tezlik 15MBit/s"` | Bir javobda ham o'zbekcha, ham ruscha (`"Скорость 50+ Mb/c"`) qatorlar aralash keldi |
| `location_info` | string \| null | `null` | Har doim `null` |

Faqat **bitta oy** so'raladi — sana oralig'i parametri yo'q. Javob sahifalanmaydi.

## 7. `/acct/payments` — to'lovlar

**So'rov:** `acc_id`, `pay_month` (`Y-m`), `lang` · **Javob:** `payments[]`
(to'lov bo'lmasa — bo'sh massiv)

| Maydon | Tur | Misol | Izoh |
|---|---|---|---|
| `payment_id` | string | `"1062960"` | To'lovlar jadvalida va CSV eksportida ko'rsatiladi |
| `payment_date` | `Y-m-d H:i:s` | `"2026-07-31 16:58:09"` | |
| `amount` | **int** | `2500000` | Tiyinda |
| `payment_system` | string | `"PayNet"` | |
| `payment_status` | string | `"to'langan"` | **`lang` bo'yicha tarjima qilinadi** — mashina kodi emas |

`payment_status` lokalizatsiya qilingani muhim: uni matn bo'yicha tanib olish
tilga bog'liq bo'lib qoladi. Shuning uchun `payment_status_code` so'ralmoqda.

---

## 8. Tekshirilmagan endpointlar

Quyidagilar Telescope'da uchramadi (mutatsion chaqiruvlar). Shakl kod bo'yicha
(`app/Services/Sola/SolaClient.php`), javoblari **tasdiqlanmagan**:

| Endpoint | So'rov | Eslatma |
|---|---|---|
| `/tariff/connect` | `acc_id`, `tariff_id` (int), `tariff_conndate` (`Y-m-d`) | `lang` **yuborilmaydi** — imzo tana baytlari ustidan hisoblangani uchun shakl o'zgarmasligi kerak |
| `/device/new` | `acc_id`, `lang` | |
| `/device/delete` | `acc_id`, `permit_id`, `lang` | |

## 9. Aniqlanishi kerak bo'lgan savollar

1. ~~`saldo` birligi~~ — **javob topildi**, 10-bo'limga qarang.
2. `status` maydonining mumkin bo'lgan qiymatlari ro'yxati
3. `payment_status` qiymatlari + mashina kodi (`payment_status_code`)
4. `connect_cost = "-1"` nimani anglatadi
5. `abonType` va `readonly` qiymatlari ro'yxati
6. Xato kodlari to'liq spravochnigi (`110`, `114`, `109`, `115` kuzatildi — 10-bo'lim)
7. `address` nega `lang=uz` da ham rus tilida qaytadi; `errMsg` tarjima qilinadimi
   (barcha kuzatilgan xatolar `lang=ru` bilan olingan)

---

## 10. Zondlash natijalari (2026-08-10, jonli hisob 1336708)

Yetishmayotgan endpointlar haqiqatan yo'qmi yoki shunchaki hujjatlanmaganmi —
jonli API ga so'rov yuborib tekshirildi. **Faqat o'qish** so'rovlari yuborildi;
`/wifi/password` va `/abonent/edit` kabi yozadigan nomlar atayin sinalmadi.

### Nazorat: mavjud emaslik qanday ko'rinadi

```
/zzz/nonexistent   →  400  {"code":109,"errMsg":"Неопределенный метод"}
```

Ya'ni **`code 109` — endpoint mavjud emasligining isboti**. Mavjud endpoint
noto'g'ri parametr bilan boshqacha javob beradi:

```
/abonent/info (acc_id siz)  →  400  {"code":114, "…объязательный параметр (Лицевой счёт)"}
/acct/payments (oysiz)      →  400  {"code":115, "…объязательный параметр (месяц)"}
```

### Yangi topilgan endpoint

| Endpoint | So'rov | Javob |
|---|---|---|
| `POST /acct/balance` | `acc_id`, `lang` | `{"saldo":"89645161"}` |

Hujjatda ham, eski kodda ham yo'q edi. **Muhim tomoni:** u `saldo` ni
**tiyinda** qaytaradi, `/abonent/info` esa **so'mda, kopeykasi bilan**:

```
/abonent/info  → "saldo": "896451.61"     (so'm)
/acct/balance  → "saldo": "89645161"      (tiyin)   ← 896451.61 × 100
```

Shu bilan 9-bo'limdagi 1-savol yopildi: `/abonent/info` dagi `saldo` **so'mda**,
va u kasr qismga ega. Kabinet uni butun songa yaxlitlab ko'rsatadi.

`/acct/balance` faqat balansni oladi — kelajakda balansni sahifani
yangilamasdan (AJAX) yangilash kerak bo'lsa, `/abonent/info` ning to'liq
kartochkasidan arzonroq chaqiruv.

### Mavjud emasligi TASDIQLANGAN endpointlar

Hammasi `109` qaytardi, ya'ni nazorat bilan bir xil:

`/tariff/history` · `/loyalty/info` · `/bonus/info` · `/abonent/discounts` ·
`/discount/list` · `/acct/info` · `/abonent/contract` · `/acct/charges` ·
`/acct/writeoff` · `/acct/history` · `/acct/period` · `/abonent/balance` ·
`/abonent/status` · `/abonent/tariff` · `/tariff/current` · `/tariff/info` ·
`/tariff/list` · `/device/info` · `/acct/list` · `/acct/detail` ·
`/acct/tariff` · `/acct/services` · `/acct/status` · `/acct/summary` ·
`/acct/sessions` · `/acct/traffic` · `/abonent/list` · `/abonent/devices` ·
`/abonent/payments` · `/device/permits`

Ya'ni `MISSING_APIS.md` dagi bo'shliqlar **taxmin emas, o'lchangan fakt**:
sodiqlik dasturi, tarif tarixi va chegirmalar API da umuman yo'q.

### Sana oralig'i parametrlari — qabul qilinmaydi

```
/acct/payments  {date_from, date_to}                →  115 "не указан … (месяц)"
/acct/payments  {pay_month, date_from, date_to}     →  200, lekin faqat pay_month ishlaydi
/traffic/detail {date_from, date_to}                →  115 "не указан … (месяц)"
```

Oy majburiy, oraliq esa **jimgina e'tiborsiz qoldiriladi**. Ya'ni
`Period::MAX_MONTHS = 12` cheklovi va har oy uchun alohida so'rov yuborish
yechimi kuchida qoladi.

### Yo'l-yo'lakay: `phone` maydoni toza emas

```
"phone": "712070807,,"
```

Bir nechta raqam vergul bilan ajratiladi va oxirida bo'sh elementlar qoladi.
Agar bu maydon qachondir ekranga chiqarilsa, avval ajratib tozalash kerak.
