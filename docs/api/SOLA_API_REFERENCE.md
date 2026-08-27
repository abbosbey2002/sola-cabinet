# SOLA Billing API — to'liq ma'lumotnoma (manba bo'yicha)

**Manba:** `docs/task/apipc/` — gateway'ning haqiqiy kodi (`main.php`, 1653 qator,
oxirgi o'zgarish 2021-04-20). Bu hujjat **shartnomani** tavsiflaydi: barcha
endpointlar, parametrlar, validatsiya qoidalari va xato kodlari.

**Juftlik hujjati:** [`SOLA_API.md`](SOLA_API.md) — jonli trafik asosida yozilgan.
U **nima qaytgani**ni (haqiqiy qiymatlar, tiplar, g'alatiliklar) qayd etadi.

| Hujjat | Savolga javob beradi | Manba |
|---|---|---|
| `SOLA_API.md` | Amalda nima qaytdi? | Telescope + jonli zond |
| `SOLA_API_REFERENCE.md` (bu) | Nima qaytishi **mumkin**? Qaysi xato qachon? | Gateway kodi |

Ikkalasi bir-birini almashtirmaydi. Tiplar bo'yicha `SOLA_API.md` ustun (u
o'lchangan), qamrov bo'yicha bu hujjat ustun: kuzatilgani **10 ta** endpoint,
manbada esa **19 ta**.

---

## 1. Transport

| | |
|---|---|
| Baza | `http://172.19.1.101:808` (`API_IP`) |
| Server | Apache 2.4.10 / PHP 5.3.29 |
| Yo'naltirish | `.htaccess` → `main.php?action=<1>&param1=<2>&param2=<3>` |
| Tana | JSON (`php://input`) |
| Metod | **Tekshirilmaydi** — pastga qarang |
| Til | `lang`: `ru` \| `uz` \| `en`; noto'g'ri qiymat jimgina `ru` ga tushadi |
| Muvaffaqiyat | `200` + JSON (ba'zan bo'sh tana) |
| Xato | `400` + `{"code": <int>, "errMsg": "<matn>"}` |
| Avtorizatsiya xatosi | `403`, **tanasiz** |

### Ikki qatlamli avtorizatsiya

1. **HTTP Basic** — `constants.php` dagi `$users` massivi (`usoft`, `pcuser1`,
   `ccenter`, `test`). `status != 1` bo'lsa ham `403`.
2. **`X-Access-Token`** — `md5("<username> <secretKey> <tana>")`.

Token **tananing aynan o'sha baytlari** ustidan hisoblanadi. Tanani qayta
`json_encode` qilsangiz imzo buziladi. Gateway'da zaxira urinish bor
(`main.php:80` — tanani qayta encode qilib solishtiradi), lekin unga tayanmang.

Uchala tekshiruv ham `403` va bo'sh tana qaytaradi — sabab **javobdan
bilinmaydi**, faqat server logida (`writeLog`) qoladi.

### Metod tekshirilmaydi

`main.php` da `$_SERVER['REQUEST_METHOD']` umuman ishlatilmaydi. Ya'ni
`/tariff/connect` kabi **o'zgartiruvchi** endpointlar to'g'ri imzolangan `GET`
so'roviga ham javob beradi. Klient tomonidan hammasi `POST` yuboriladi va shunday
qolgani ma'qul, lekin bu server kafolati emas.

`param2` parse qilinadi, ammo hech bir endpointda ishlatilmaydi.

---

## 2. Endpointlar — umumiy jadval

`✎` — ma'lumot o'zgartiradi · `✉` — SMS yuboradi · `⇄` — Oracle tranzaksiyasi
(`commit`/`rollback`)

| # | Endpoint | So'rov | Javob | | `API_PC` protsedurasi |
|---|---|---|---|---|---|
| 1 | `/identify` | `phn`, `sendsms` | `accs[]` | ✉ | `IdentifyList`, `getAbonType3` |
| 2 | `/verify` | `phn`, `smsCode` | *bo'sh* | | `Verify` |
| 3 | `/abonent/info` | `acc_id` | obyekt | | `abonentInfo` |
| 4 | `/abonent/edit` | `acc_id`, `email`, `phone` | *bo'sh* | ✎ | `abonentEdit` |
| 5 | `/acct/balance` | `acc_id` | `{saldo}` | | `acctBalance` |
| 6 | `/acct/wifipassword` | `acc_id`, `curr_password`, `new_password` | *bo'sh* | ✎ | `acctWifiPassword` |
| 7 | `/acct/payments` | `acc_id`, `pay_begin`, `pay_end` ¹ | `payments[]` | | `acctPayments` |
| 8 | `/traffic/detail` | `acc_id`, `detail_start`, `detail_end` | `detail[]` | | `TrafficDetail` |
| 9 | `/device/list` | `acc_id` | `devices[]`, `connect_cost` | | `DeviceList` |
| 10 | `/device/new` | `acc_id` | *bo'sh* | ✎⇄ | `DeviceNew` |
| 11 | `/device/newoneclick` | `phn`, `acc_id`, `smsCode` | `accs[]`, `smsSended` | ✎✉⇄ | `DeviceNewOneClick` |
| 12 | `/device/delete` | `acc_id`, `permit_id` | *bo'sh* | ✎⇄ | `DeviceDel` |
| 13 | `/tariff/available` | `acc_id` | `tariffs[]` | | `PermSRVRight` |
| 14 | `/tariff/connected` | `acc_id` | `tariffs[]` | | `PermSRVLeft` |
| 15 | `/tariff/connect` | `acc_id`, `tariff_id`, `tariff_conndate` | *bo'sh* | ✎⇄ | `NewPermitSrv` |
| 16 | `/tariff/disconnect` | `acc_id`, `tariff_id` | *bo'sh* | ✎⇄ | `DelPermitSrv` |
| 17 | `/service/available` | `acc_id` | `services[]` | | `AddSRVRight` |
| 18 | `/service/connected` | `acc_id` | `services[]` | | `AddSRVLeft` |
| 19 | `/service/connect` | `acc_id`, `service_id`, `service_conndate` | *bo'sh* | ✎⇄ | `NewAddSrv` |

Barchasida `lang` ixtiyoriy. `acc_id` talab qiladigan endpointlarda u **butun son
> 0** bo'lishi shart (aks holda `114`).

¹ Mijoz aytdi (2026-08-19): `/acct/payments` endi bitta `pay_month` o'rniga
`pay_begin`/`pay_end` (ikkalasi ham majburiy, filtrlash oralig'i) qabul
qiladi — §7 ga qarang. Format ikki marta noto'g'ri kodga tushgan: avval
(2026-08-19) `YYYY-MM-DD`, keyin (2026-08-25) `d.m.y` (ikki xonali yil) —
ikkalasi ham live serverga qarshi to'liq tasdiqlanmagan holda kiritilgan
edi; 2026-08-27 da server aslida `d.m.Y` (to'rt xonali yil, masalan
`25.08.2026`) kutishi aniqlandi — §7 ga qarang, tuzatilgan. Bu yerdagi
`docs/task/apipc/main.php` nusxasi hali eski `pay_month`-only variantni
ko'rsatadi (server tomonidagi o'zgarish yangi apipc export bilan hali qayta
tasdiqlanmagan); xato kodlari (`115` va h.k.) shu sabab eski holicha
qoldirilgan.

**`/service/disconnect` yo'q.** Tarif uchun `disconnect` bor, xizmat uchun yo'q —
`API_PC` da `DelAddSrv` ochilmagan.

**Muvaffaqiyatda tana bo'sh** — 8 ta endpointda (`✎` belgililar + `/verify`).
Ular faqat HTTP `200` bilan tasdiqlanadi.

---

## 3. Endpointlar — tafsilot

### 1. `/identify` — abonentni topish, SMS yuborish ✉

```json
{"phn": "998901234567", "lang": "uz", "sendsms": 1}
```

`sendsms` — `1` (default) SMS yuboradi, `0` faqat ro'yxat qaytaradi.
`phn` butun son bo'lishi shart, aks holda `0` ga aylanadi → `113`.

**200:** `{"accs": [{abonType, abonName, accId, login}]}`

Ichki oqim: `IdentifyList` bo'sh qaytarsa, gateway ikkinchi marta `getAbonType3`
chaqirib **sababni** aniqlaydi — `126` (ВРЕМЕННЫЙ, PK ga ruxsat yo'q) yoki `110`.

| Xato | Sabab |
|---|---|
| `113` | `phn` bo'sh yoki raqam emas |
| `110` | Abonent topilmadi |
| `126` | `abonType = 0` — ВРЕМЕННЫЙ tipi |
| `111` / `112` | SMS gateway xatosi |

### 2. `/verify` — SMS kodini tekshirish

**200: bo'sh tana.** Token ham, sessiya ham qaytmaydi — sessiyani kabinet o'zi
yuritadi.

`113` (`phn` bo'sh) · `120` (`smsCode` bo'sh yoki noto'g'ri).

### 3. `/abonent/info` — abonent kartochkasi

**200:** `name`, `saldo`, `status`, `curr_tariff_id`, `curr_tariff_name`,
`contract_date`, `contract_id` ², `email`, `phone`, `address`, `device_count`,
`device_active_count`, `legal`, `charge_date`

| Maydon | Manba (`api_pc_aboninfo_t`) | Izoh |
|---|---|---|
| `status` | `OffReasonName` | Status emas — **o'chirilish sababi**ning nomi. Faol abonentda bo'sh |
| `saldo` | `Saldo` | **so'mda**, kasr qismi bilan (`"896451.61"`) |
| `address` | `Address` | `iLang` uzatiladi, lekin baribir rus tilida qaytadi |
| `phone` | `Phone` | Xom holda, bind 100 belgi — vergulli ro'yxat (`"712070807,,"`) |
| `email` | `Email` | bind 50 belgi |
| `contract_id` ² | — | Shartnomaning ichki billing id'si — `contract_number` ("Договор №" satri) dan boshqa maydon, biri ikkinchisiga zaxira bo'lmaydi. `AbonentProfile::contractId()` |
| `legal` | — | Mijoz tasdiqladi (2026-08-18): jismoniy shaxsda `"Физическое лицо"` yoki `0`; boshqa har qanday qiymat (masalan `"Юридическое лицо"`) — yuridik shaxs. Yuridik shaxsga tarif bo'limi (menyu, dashboard karta, `/tariffs` sahifasi) butunlay yopiladi — `AbonentProfile::isLegalEntity()`, `TariffController` |
| `charge_date` | — | Mijoz tasdiqladi (2026-08-18, o'sha kuni tuzatildi): **keyingi** to'lov sanasi (`"2026-09-17"`), oxirgisi emas. Billing individual/yuridik shaxs farqini o'zida hisoblab, tayyor sanani yuboradi — kabinet uni hech qanday arifmetikasiz o'qiydi — `AbonentProfile::nextChargeDate()` |

² Mijoz aytdi (2026-08-19): `/abonent/info` javobiga `contract_id` qo'shildi.
Manba ustuni (`api_pc_aboninfo_t` maydoni) hali aniqlanmagan — mijoz maydon
nomini bergan, lekin ichki Oracle ustuni probe qilinmagan.

`abonType` javobga **kirmaydi**, faqat ichki tekshiruvda ishlatiladi:
`< 0` → `110` · `= 0` → `121` · `> 0` → normal.

Qiymatlar (mijoz aytdi, 2026-08-13):

| Qiymat | Kim | Kabinetda |
|---|---|---|
| `< 0` | topilmadi | — |
| `0` | **vaqtinchalik** (ВРЕМЕННЫЙ) | kirish yopiq (`121` / `126`) |
| `1` | **bir martalik** (РАЗОВЫЙ) | faqat o'qish — qurilma va tarif yopiq |
| `≥ 2` | **doimiy** — *yuridik shaxs* yoki *jismoniy shaxs* | to'liq huquq |

> Doimiy abonent **bitta qiymat emas**: billing yuridik va jismoniy shaxsni
> alohida raqamlaydi. Shuning uchun tekshiruv `≥ 2`, hech qachon `== 2` emas
> (`AbonentSession::isPermanent()`). Qaysi raqam yuridik, qaysi biri jismoniy —
> hali aniqlanmagan, `/identify` javobida kuzatilishi kerak.

`114` (`acc_id`) · `110` · `121`.

### 4. `/abonent/edit` — email va telefonni yangilash ✎

`email` va `phone` — **ikkalasi ham majburiy**, bittasini yangilash uchun ham
ikkalasini yuborish kerak (`116` / `117`). Bind cheklovi: `email` 40 belgi,
`phone` **11 belgi** — ya'ni bu yerda bitta raqam kutiladi, `/abonent/info`
qaytaradigan vergulli ro'yxat emas.

**200: bo'sh tana.** `114` · `116` · `117` · `110` · `122`.

### 5. `/acct/balance` — faqat balans

**200:** `{"saldo": "89645161"}` — **tiyinda**. `/abonent/info` dagi `saldo` esa
so'mda. Bir xil nomdagi maydon, ikki xil birlik.

`114` · `110` · `121`.

### 6. `/acct/wifipassword` — Wi-Fi parolini almashtirish ✎

`curr_password` + `new_password`, ikkalasi ham bo'sh bo'lmasligi kerak (`118`).
Parol murakkabligi **tekshirilmaydi** — bu Oracle tomonida.

**200: bo'sh tana.** `114` · `118` · `110` · `121` · `123` (joriy parol noto'g'ri).

### 7. `/acct/payments` — to'lovlar

`pay_begin`, `pay_end` — **`d.m.Y`** (masalan `25.08.2026`), ikkalasi ham
**majburiy**, inklyuziv oraliq. Oraliq borligi mijoz tomonidan 2026-08-19 da
aytilgan, lekin o'shanda format `YYYY-MM-DD` deb yozilgan va live serverga
qarshi tasdiqlanmagan edi; 2026-08-25 da live tekshiruv (keyinchalik noto'g'ri
chiqqan) kun-oy-yil tartibida **ikki** xonali yil deb ko'rsatgan edi —
2026-08-27 da bu ham noto'g'ri ekani aniqlandi: server to'liq, **to'rt**
xonali yilni kutadi, sana kun-oy-yil tartibida, nuqta bilan ajratilgan holda.
`Period::paymentsStart()`/`paymentsEnd()` shunga mos tuzatildi. Eski
`pay_month` (`YYYY-MM`, `checkRequestMonth()` bilan tekshirilardi) endi
yuborilmaydi — `SolaClient::payments()`, `BillingHistory::payments()`.
Validatsiya xato kodlari (`115` qatorining yangi shakli, min/max oraliq
bormi) hali live probe qilinmagan — quyidagi `114`/`110`/`121` eski
kontraktdan qolgan, ehtiyot bilan o'qing.

**200:** `payments[{payment_id, payment_date, amount, payment_system, payment_status}]`

- `amount` = `LOCAL_AMOUNT * 100` → **tiyin** (ko'paytirish PHP'da)
- `payment_system` = `NBANK_KASSA`, lekin `BANK_KASSA == 5` bo'lsa `NOTE`
- `payment_status` — bazadan erkin matn, `lang` bo'yicha tarjima qilinadi

Sahifalash yo'q. Sana oralig'i endi bor (`pay_begin`/`pay_end`) — 12 oylik
`Period::MAX_MONTHS` cheklovi 2026-08-25 da olib tashlangan (mijoz so'rovi
bilan), serverning o'z maksimal oralig'i hali tasdiqlanmagan.

`114` · `110` · `121` (`115` eski `pay_month` uchun edi — yangi kontraktda hali tasdiqlanmagan).

### 8. `/traffic/detail` — trafik detalizatsiyasi

`detail_start`, `detail_end` — **`d.m.Y`** (masalan `25.08.2026`), inklyuziv
oraliq — `pay_begin`/`pay_end` (§7) bilan bir xil shakl. Eski `detail_month`
(`YYYY-MM`, ichkarida `YYMM`ga aylantirilardi — `getMonthYYMM`) endi
yuborilmaydi — `SolaClient::trafficDetail()`, `BillingHistory::traffic()`.
Bu format billing/SOLA tomoni bilan mijoz orqali tasdiqlangan (2026-08-27),
lekin `pay_begin`/`pay_end` kabi live serverga qarshi mustaqil o'lchanmagan —
haqiqiy format va xato kodlari (quyidagi `114`/`115`/`110`/`121` eski
`detail_month` kontraktidan qolgan) birinchi real chaqiruvda tekshirilsin.

**200:** `detail[{event_time, location_info, traffic_input, traffic_output, pocket_info}]`

`traffic_input`/`traffic_output` — **bayt**. `location_info` = `CALLING_STATION_ID`.
`pocket_info` = `TARIFF_NAME`.

`114` · `115` · `110` · `121`.

### 9. `/device/list` — qurilmalar

**200:** `{"devices": [{permit_id, connect_date, mac, ip, readonly}], "connect_cost": …}`

`connect_cost` massivdan **tashqarida** — `DeviceList` ning `oSrvCost` chiqish
parametri, xom holda uzatiladi (`*100` yo'q). `-1` ma'nosi kodda izohlanmagan.

`114` · `110` · `121`.

### 10. `/device/new` — qurilma qo'shish ✎⇄

Faqat `acc_id`. Ulanish sanasi **serverda** `date("Y-m-d")` bilan qo'yiladi —
klient uni tanlay olmaydi. `iSrvId = NULL` (xizmatni Oracle o'zi tanlaydi).

`executeDefault()` (OCI_DEFAULT) ishlatiladi, ya'ni avtokommit yo'q: xatoda
`rollback()`, muvaffaqiyatda `commit()`.

**200: bo'sh tana.**

| DB `oStatus` | Chiqish kodi |
|---|---|
| `1` | `110` topilmadi |
| `2` | `121` ВРЕМЕННЫЙ ga mumkin emas |
| `3` | `127` bog'lanmagan MAC bor |
| `4` | `128` РАЗОВЫЙ ga mumkin emas |
| `12`, `17` | `132` balans yetmaydi |
| `20` | `100` tarif rejasida xizmat yo'q |
| boshqa | `100` |

### 11. `/device/newoneclick` — SMS bilan qurilma qo'shish ✎✉⇄

**Yagona holatli (stateful) endpoint.** Ikki qadamda ishlaydi:

**1-qadam** (`smsCode` yuborilmaydi) — abonentlarni topadi, bitta bo'lsa SMS
yuboradi va kodni PHP sessiyasiga yozadi:

```php
$_COOKIE['PHPSESSID'] = "SESSID-APIPC-{$phone}-{$accId}";
```

Sessiya ID **determinlashgan** — telefon + `acc_id` dan yasaladi, cookie'ga
tayanmaydi. Shu sababli klient hech narsa saqlamaydi.

**200:** `{"smsSended": 0|1, "accs": [{abonType, accId, login, contract, saldo, cost}]}`

> Diqqat: bu yerdagi `accs[]` `/identify` dagidan **boshqacha** — qo'shimcha
> `contract` (`CONTRACT_NUMBER`), `saldo` (`ACC_BALANCE`) va `cost` (`SRV_COST`)
> bor. Ya'ni **`contract_number` allaqachon `API_PC` da mavjud** — uni
> `/abonent/info` ga qo'shish yangi ma'lumot manbasi talab qilmaydi.
> (`docs/task/BILLING_API_TALABLARI.md`, P1)

Bir nechta hisob topilsa SMS yuborilmaydi (`smsSended: 0`) — klient hisobni
tanlab, 2-qadamni `acc_id` bilan chaqiradi.

**2-qadam** (`smsCode` yuboriladi) — kodni sessiyadan solishtiradi va
muvaffaqiyatda **`/device/new` blokiga o'tadi** (`$param1 = "new"`), ya'ni javob
va xato kodlari 10-banddagi bilan bir xil.

`113` · `120` · `132` (1-qadamda balans `cost` dan kam) · `111`/`112`.

### 12. `/device/delete` — qurilmani o'chirish ✎⇄

`permit_id` — `/device/list` dan olinadi.

> **Qopqon:** noto'g'ri `permit_id` uchun kod **`119`** qaytadi, uning matni esa
> *"…объязательный параметр (tariff_id)"*. Kod `119` ni `tariff_id` bilan
> baham ko'radi — `errMsg` bu yerda chalg'itadi.

`114` · `119` · `110` · `122` · `100`.

### 13–14. `/tariff/available` va `/tariff/connected`

Ikkalasi ham faqat `acc_id` oladi, lekin **turli maydon to'plami** qaytaradi.

**`/tariff/available`** (`PermSRVRight`) → `{"tariffs": [...]}`

| Maydon | Izoh |
|---|---|
| `tariff_id` | `SRV_ID` |
| `tariff_name` | Oxirida ortiqcha probel uchraydi — `trim` kerak |
| `cost` | `COST * 100` → **tiyin** |
| `tspd` / `spdu` | Tezlik va birligi (`"500"` / `"Mbps"`) |
| `tprd` / `prdu` | Muddat va birligi (`"5"` / `"HOUR"`) |
| `vol` | `TVOL` — trafik limiti, `0` = cheksiz |

**`/tariff/connected`** (`PermSRVLeft`) → `{"tariffs": [...]}`

Jonli javob (2026-08-13, hisob 1336708, 201 ms):

```json
{"tariffs": [{
  "tariff_id":    "1197",
  "tariff_name":  "Smart 300 - 355 000 сум",
  "date_begin":   "2026-08-10 16:34:27",
  "date_end":     null,
  "tariff_isoff": "0"
}]}
```

| Maydon | Izoh |
|---|---|
| `tariff_id` | `/abonent/info` dagi `curr_tariff_id` bilan **mos keladi** — tasdiqlangan |
| `date_begin` | `BDATE` — tarifning boshlanish payti. **`Y-m-d H:i:s`**, sana emas |
| `date_end` | `EDATE` — faol tarifda `null` |
| `tariff_isoff` | `ISOFF` — `"0"` = faol |

**Narx yo'q.** Va faqat **faol** qator qaytadi — tarix emas.

> Bu endpoint **`current_tariff_start_date`** ni beradi: `tariff_id ==
> curr_tariff_id` bo'lgan qatorning `date_begin` i. Kabinet uni hozir
> chaqirmaydi. Batafsil:
> [`BILLING_API_TALABLARI.md`](../task/BILLING_API_TALABLARI.md) §"Yopildi".

Ikkalasi: `114` · `110` · `121`.

### 15. `/tariff/connect` — tarifni ulash ✎⇄

```json
{"acc_id": 1234567, "tariff_id": 839, "tariff_conndate": "2026-08-13"}
```

**`tariff_conndate` qat'iy cheklangan.** Faqat ikki qiymat qabul qilinadi:

- **bugun** — `date("Y-m-d")`
- **keyingi oyning 1-kuni** — joriy oyning oxirgi kuni + 1 kun

Boshqa har qanday sana → `124`. Ya'ni "kelasi hafta" yoki "15-sanadan" degan
tanlov API darajasida mumkin emas.

`114` · `119` (`tariff_id`) · `124` · `110` · `121` · `129` (balans yetmaydi) · `100`.

### 16. `/tariff/disconnect` — tarifni uzish ✎⇄

`acc_id` + `tariff_id`. Sana yo'q — `DelPermitSrv` ga `NULL` uzatiladi.

`114` · `119` · `110` · `100`.

### 17–18. `/service/available` va `/service/connected`

**`/service/available`** (`AddSRVRight`) → `{"services": [{service_id, service_name, cost}]}`
`cost` = `COST * 100` → tiyin.

**`/service/connected`** (`AddSRVLeft`) → `{"services": [...]}`
`permit_id` (`SRV_PERMIT_ID`), `service_id`, `service_name`, `service_param`
(`PARAMS`), `date_begin`, `date_end`.

Ikkalasi: `114` · `110` · `122`.

Ikkalasi ham jonli tekshirildi (2026-08-13) — `200`, lekin sinov hisobida
qo'shimcha xizmat yo'q, shuning uchun ikkalasi ham `{"services": []}` qaytardi.
Ya'ni **endpointlar mavjud**, maydon nomlari esa hali namunada ko'rilmagan.

### 19. `/service/connect` — xizmatni ulash ✎⇄

`service_conndate` uchun **`/tariff/connect` bilan bir xil qoida** (bugun yoki
keyingi oyning 1-kuni), lekin xato kodi boshqa: `131`.

`114` · `130` (`service_id`) · `131` · `110` · `122` · `127` · `128` · `132` · `100`.

Uzish endpointi yo'q — ulangan xizmatni API orqali o'chirib bo'lmaydi.

---

## 4. Xato kodlari — to'liq spravochnik

Manba: `constants.php:56–118`. Uchala til uchun matnlar **bir xil** — hammasi
ruscha, `uz` va `en` bloklari nusxa. Ya'ni `lang` `errMsg` ga ta'sir qilmaydi.

| Kod | Matn (`ru`) | Qachon |
|---|---|---|
| `0` | Успешно | — |
| `100` | Системная ошибка | Oracle noma'lum status qaytardi |
| `101` | Внутренняя ошибка. БД уровень 1 | Bazaga ulanib bo'lmadi |
| `102` | …уровень 2 | Protsedura bajarilmadi |
| `103` | …уровень 3 | Kursorli protsedura bajarilmadi |
| `109` | Неопределенный метод | **Endpoint mavjud emas** — mavjudlikni tekshirish uchun ishonchli belgi |
| `110` | Абонент не найден | |
| `111` / `112` | Ошибка при отправке СМС | SMS gateway |
| `113` | Введите номер телефона | `phn` bo'sh |
| `114` | …(Лицевой счёт) | `acc_id` yo'q yoki ≤ 0 |
| `115` | …(месяц) | `detail_month`. Eski `pay_month` uchun ham shu edi — `/acct/payments` endi `pay_begin`/`pay_end` ishlatadi, yangi xato kodi tasdiqlanmagan (§7) |
| `116` | …(email) | `/abonent/edit` |
| `117` | …(phone) | `/abonent/edit` |
| `118` | …(password) | `/acct/wifipassword` |
| `119` | …(tariff_id) | `tariff_id` **va** `/device/delete` dagi `permit_id` |
| `120` | Не верный код СМС или пароль | |
| `121` | Временным не доступно | `abonType = 0` |
| `122` | Временным и разовым не доступно | |
| `123` | Не верно указан текущий пароль | |
| `124` | …(tariff_conndate) | Sana bugun/1-kun emas |
| `125` | Не указан номер телефона для SMS | **Hech qachon qaytmaydi** — kodda ishlatilmagan |
| `126` | ВРЕМЕННЫЙ тип не имеет доступа к ПК | Faqat `/identify` |
| `127` | Имеется устройство с не привязанным MAC | |
| `128` | Услуга не доступна для разового абонента | |
| `129` | Баланс не позволяет изменить тариф | `/tariff/connect` |
| `130` | …(service_id) | |
| `131` | …(service_conndate) | |
| `132` | Баланс не позволяет подключить услугу | |

Bir xil vaziyat endpointga qarab turli kod beradi: `abonType = 0` →
`/identify` da `126`, qolgan hamma joyda `121` yoki `122`. Klient kodni
endpoint kontekstida talqin qilishi kerak.

---

## 5. Xulq-atvor: bilib qo'yish kerak

**Tiplar.** Javobdagi deyarli barcha raqamlar **string** (`"saldo": "19000"`,
`"device_count": "1"`). Istisno — `cost` va `amount`: ular PHP'da `* 100`
qilingani uchun **int**. Batafsil: `SOLA_API.md` §"Muhim: tiplar".

**Pul birliklari izchil emas.** `* 100` faqat uch joyda qo'llanadi:

| Maydon | Birlik | Sabab |
|---|---|---|
| `cost` (tarif, xizmat) | tiyin | PHP'da `COST * 100` |
| `amount` (to'lovlar) | tiyin | PHP'da `LOCAL_AMOUNT * 100` |
| `saldo` (`/acct/balance`) | tiyin | Oracle shunday qaytaradi |
| `saldo` (`/abonent/info`) | **so'm**, kasr bilan | Xom holda uzatiladi |
| `connect_cost` (`/device/list`) | **noma'lum** | Xom holda uzatiladi |
| `saldo`, `cost` (`newoneclick`) | **noma'lum**, o'zaro solishtiriladi | Xom holda |

**Kodlash.** Baza `windows-1251` da, gateway har bir matn maydonini alohida
`iconv` qiladi. `iconv` qo'llanmagan maydonlar (`phone`, `login`, `mac`) xom
holda o'tadi — ularda kirill bo'lsa buziladi.

**Sahifalash yo'q.** Hech bir ro'yxat endpointida `limit`/`offset` yo'q.
`/acct/payments` `pay_begin`/`pay_end` bilan haqiqiy sana oralig'ini qabul
qiladi (mijoz, 2026-08-19) — §7. `/traffic/detail` ham endi xuddi shunday
`detail_start`/`detail_end` bilan oraliq qabul qiladi (mijoz, 2026-08-27) —
§8; eski o'lchov (`date_from`/`date_to` e'tiborsiz qoldirilishi,
`SOLA_API.md` §10) `detail_month` kontraktiga tegishli edi va yangi
`detail_start`/`detail_end` maydonlari bilan qayta tekshirilmagan.

**Tranzaksiya.** O'zgartiruvchi 6 ta endpoint `executeDefault()` + aniq
`commit`/`rollback` ishlatadi. Idempotentlik yo'q: takroriy `/tariff/connect`
ikkinchi marta ham bajariladi. Klient tomonda qayta yuborishdan himoya kerak
(`SolaClient` da `idempotent: false` shuning uchun bor).

**Log.** Har bir xato SysV message-queue orqali demonga yoziladi. Navbat
to'lganda (`msg_qnum >= 100`) logging **jimgina o'chadi** — xatolar yo'qoladi.

---

## 6. Ma'lum bo'shliqlar

Kabinet talab qiladigan, ammo API da **yo'q** narsalar —
[`docs/task/BILLING_API_TALABLARI.md`](../task/BILLING_API_TALABLARI.md):

- `/abonent/info`: `current_tariff_start_date`, `curr_tariff_cost`,
  `contract_number`, `next_tariff_name`, `next_tariff_cost` — `next_charge_date`
  itself closed on 2026-08-18: billing sends `charge_date` (the NEXT payment
  date already, read straight through) instead, ko'ring 3-bo'lim va
  `AbonentProfile::nextChargeDate()`
- `/tariff/history`, `/loyalty/info` — endpointlar umuman yo'q (`109` bilan
  tasdiqlangan)
- `/acct/payments`: `payment_status_code`
- `date_from` / `date_to` parametrlari
- `/service/disconnect`

Ulardan **`contract_number`** eng arzoni: `CONTRACT_NUMBER` ustuni
`DeviceNewOneClick` kursorida allaqachon qaytariladi (11-bandga qarang).

---

## 7. Kengaytirish

Gateway'da biznes-logika yo'q — u validatsiya qiladi, `API_PC` protsedurasini
chaqiradi, natijani JSON'ga o'giradi. Shuning uchun **yangi maydon yoki endpoint
avval Oracle tomonida** paydo bo'lishi kerak.

PHP tomoni shablon:

```php
if ($action == "acct") {
    if ($param1 == "invoice") {
        $requestArr = requestAcctInvoice($requestArr);   // functions.php
        $accId = $requestArr["acc_id"];
        if ($accId <= 0) { echo responseOnError(114); exit(0); }

        $query = "begin :result := API_PC.acctInvoice(:iAccId, :iLang, :oCursor); end;";
        $sql->cursor();
        $sql->parse($query, false);
        $sql->bindByName(":iAccId", $accId, 10);
        $sql->bindByName(":result", &$status, 2);
        $sql->bindByNameCur("oCursor");
        $sql->execute();
        // getError() → 103 · status → 110/121 · executeCurs() → fetchAllCur() → iconv → json
        exit(0);
    }
}
```

`.htaccess` o'zgartirilmaydi — yo'naltirish avtomatik. Yangi kodlar
`constants.php` dagi **uchala** til blokiga qo'shiladi.

> **Ogohlantirish.** Bu kod faqat **PHP 5.x** da ishlaydi:
> `bindByName(":o", &$var)` — call-time pass-by-reference (PHP 8 da fatal) ·
> `$HTTP_RAW_POST_DATA` (PHP 7 da olib tashlangan) · `oracle9.php` dagi `<?`
> qisqa teg (PHP 8 da fatal). Yangi kod ham shu uslubda yozilishi kerak;
> modernizatsiya alohida loyiha.

---

## 8. Tekshirish holati

| Manba | Qamrov |
|---|---|
| Jonli trafik (Telescope, 29 chaqiruv) | 7 endpoint |
| Jonli zond (2026-08-10) | `/acct/balance` + 30 ta mavjud emaslik tasdig'i |
| Jonli zond (2026-08-13) | `/tariff/connected`, `/service/available`, `/service/connected` |
| Gateway kodi (bu hujjat) | **19 endpoint, hammasi** |

Quyidagi **8 tasi jonli tekshirilmagan** — shakl kod bo'yicha, javob namunasi yo'q:
`/abonent/edit` · `/acct/wifipassword` · `/device/new` · `/device/newoneclick` ·
`/device/delete` · `/tariff/connect` · `/tariff/disconnect` · `/service/connect`

Hammasi **o'zgartiradi yoki SMS yuboradi**, shuning uchun jonli hisobda sinab
bo'lmaydi. Barcha faqat-o'qish endpointlari endi tasdiqlangan.

### Zondlash uchun eslatma

API `172.19.1.101` da yotadi va **VPN talab qilmaydi** — lokal tarmoqdan
ochiq (`192.168.0.1` orqali, ~70–220 ms).

Yagona to'siq — Docker: `172.16.0.0/12` diapazonidagi subnet olgan har qanday
docker tarmog'i API manzilini o'g'irlaydi va so'rov `Connection timed out` bilan
o'ladi. Tekshirish:

```
ip route get 172.19.1.101      # br-* chiqsa — docker to'sib turibdi
```

Cabinet o'zining `docker-compose.yml` da subnet'ni `10.123.0.0/24` ga
qadab qo'ygan, ammo mashinadagi **boshqa** loyihalar ham konflikt yaratishi mumkin.
