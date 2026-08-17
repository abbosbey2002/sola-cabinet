# APIPC — Billing API (endpointlar va responselar)

Manba: `docs/task/apipc/` (`main.php`, `functions.php`, `constants.php`, `.htaccess`).
Bu legacy PHP API — Oracle `API_PC` paketining ustidagi HTTP qobiq.

---

## 1. Umumiy qoidalar

### URL sxemasi

`.htaccess` path segmentlarini GET parametrlarga aylantiradi:

| URL | main.php parametrlari |
|---|---|
| `/{action}` | `action` |
| `/{action}/{param1}` | `action`, `param1` |
| `/{action}/{param1}/{param2}` | `action`, `param1`, `param2` |

Ya'ni `POST /abonent/info` → `main.php?action=abonent&param1=info`.
`param2` hech qayerda ishlatilmaydi (faqat log'da).

### So'rov formati

- Metod: amalda **POST**, tanasi — **JSON** (kod `php://input` ni o'qiydi, metodni tekshirmaydi).
- Barcha parametrlar JSON body ichida (query string'da emas).
- Har bir so'rovda ixtiyoriy `lang` maydoni bo'lishi mumkin: `ru` | `en` | `uz`, default `ru`. Ro'yxatdan tashqari qiymat → `ru`.

### Autentifikatsiya (2 qatlam)

**1) HTTP Basic** — `Authorization: Basic base64(username:password)`
Foydalanuvchilar `constants.php` dagi `$users` massivida (`test`, `pcuser1`, `usoft`, `ccenter`), har birida `password`, `secretKey`, `status`.

**2) Token** — `X-Access-Token: md5("{username} {secretKey} {requestJSONstring}")`
`requestJSONstring` — bu **xom body**. Agar mos kelmasa, kod ikkinchi urinishda `json_encode(json_decode(body))` natijasidan hisoblab ko'radi.

Xatolarda **HTTP 403, body bo'sh**:
- Basic emas / login yoki parol bo'sh
- Foydalanuvchi topilmadi yoki parol noto'g'ri
- `status != 1` (deaktiv foydalanuvchi)
- Token mos kelmadi

### Javob formati

**Muvaffaqiyat:** HTTP 200 + endpointga xos JSON. Ba'zi endpointlar (`verify`, `abonent/edit`, `acct/wifipassword`, `device/new`, `device/delete`, `tariff/connect`, `tariff/disconnect`, `service/connect`) **body qaytarmaydi** — faqat HTTP 200.

**Xato:** HTTP **400** + 

```json
{ "code": 114, "errMsg": "Не указан (или неправильно указан) объязательный параметр (Лицевой счёт)" }
```

`errMsg` — `constants.php` dagi `$messageBox[lang][code]`. Diqqat: `uz` va `en` matnlari hozircha rus tilining nusxasi.

### Xato kodlari (to'liq ro'yxat)

| Kod | Ma'nosi |
|---|---|
| 0 | Muvaffaqiyatli |
| 100 | Tizim xatosi |
| 101 | Ichki xato. BD 1-daraja (ulanish xatosi) |
| 102 | Ichki xato. BD 2-daraja (execute xatosi) |
| 103 | Ichki xato. BD 3-daraja (cursor execute xatosi) |
| 109 | Aniqlanmagan metod (noma'lum action/param1) |
| 110 | Abonent topilmadi |
| 111 | SMS yuborishda xato (SMS-gateway "Error") |
| 112 | SMS yuborishda xato (kutilmagan javob) |
| 113 | Telefon raqamini kiriting (`phn` bo'sh) |
| 114 | `acc_id` ko'rsatilmagan/noto'g'ri |
| 115 | oy (`pay_month` / `detail_month`) noto'g'ri |
| 116 | `email` noto'g'ri |
| 117 | `phone` noto'g'ri |
| 118 | `password` noto'g'ri |
| 119 | `tariff_id` / `permit_id` noto'g'ri |
| 120 | SMS kodi yoki parol noto'g'ri |
| 121 | Vaqtinchalik abonentga mavjud emas |
| 122 | Vaqtinchalik va bir martalik abonentga mavjud emas |
| 123 | Joriy parol noto'g'ri |
| 124 | `tariff_conndate` noto'g'ri |
| 125 | SMS uchun telefon raqami ko'rsatilmagan |
| 126 | VAQTINCHALIK abonent turi PK'ga kira olmaydi |
| 127 | MAC manzili biriktirilmagan qurilma mavjud |
| 128 | Xizmat bir martalik abonentga mavjud emas |
| 129 | Balans bu tarifga o'tishga imkon bermaydi |
| 130 | `service_id` noto'g'ri |
| 131 | `service_conndate` noto'g'ri |
| 132 | Balans bu xizmatni ulashga imkon bermaydi |

---

## 2. Endpointlar

Jami **17 ta** endpoint, 6 ta guruh.

### 2.1 `/identify` — abonentni telefon bo'yicha aniqlash + SMS

DB: `API_PC.IdentifyList`, kerak bo'lsa `API_PC.getAbonType3`

**Request**
```json
{ "phn": "998901234567", "sendsms": 1, "lang": "ru" }
```
| Maydon | Tur | Default | Izoh |
|---|---|---|---|
| `phn` | raqamli string | `0` | majburiy; raqam bo'lmasa → 0 |
| `sendsms` | int | `1` | `1` — SMS yuboriladi, aks holda yuborilmaydi |

**Response 200**
```json
{
  "accs": [
    { "abonType": 1, "abonName": "Иванов И.И.", "accId": 12345, "login": "user1" }
  ]
}
```

**Xatolar:** 113 (`phn` bo'sh), 102/103 (BD), 110 (topilmadi), 126 (abonType=0 → ruxsat yo'q), 111/112 (SMS).

SMS matni: `SOLA: Kod podtverjdeniya dlya registratsii v lichnom kabinete: {code}`, gateway: `http://172.18.0.16:1401/send`.

---

### 2.2 `/verify` — SMS kodini tekshirish

DB: `API_PC.Verify`

**Request**
```json
{ "phn": "998901234567", "smsCode": "1234" }
```

**Response 200** — **body yo'q** (muvaffaqiyat).

**Xatolar:** 113 (`phn` bo'sh), 120 (`smsCode` bo'sh yoki noto'g'ri), 102 (BD).

---

### 2.3 `/abonent/info` — abonent kartochkasi

DB: `API_PC.abonentInfo`

**Request**
```json
{ "acc_id": 12345, "lang": "ru" }
```

**Response 200**
```json
{
  "name": "Иванов Иван",
  "saldo": "15000",
  "status": "Активен",
  "curr_tariff_id": 77,
  "curr_tariff_name": "Безлимит 100",
  "contract_date": "2019-05-01",
  "email": "user@mail.uz",
  "phone": "998901234567",
  "address": "г. Ташкент, ...",
  "device_count": 3,
  "device_active_count": 2
}
```
`contract_date` formati `yyyy-mm-dd`. `status` — `OffReasonName` (o'chirilish sababi).

**Xatolar:** 114 (`acc_id <= 0`), 102, 110 (`abonType < 0` yoki `accId <= 0`), 121 (`abonType == 0`).

---

### 2.4 `/abonent/edit` — email/telefonni o'zgartirish

DB: `API_PC.abonentEdit`

**Request**
```json
{ "acc_id": 12345, "email": "new@mail.uz", "phone": "998901234567" }
```

**Response 200** — body yo'q.

**Xatolar:** 114, 116 (`email` bo'sh), 117 (`phone` bo'sh), 102, 110 (DB status=1), 122 (DB status=2).

> ⚠️ `functions.php:216 requestAbonentEdit()` da `return` yo'q → `$requestArr` `null` bo'lib qoladi va handler har doim 114 bilan tugaydi. Portlashda tuzatish kerak.

---

### 2.5 `/acct/balance` — balans

DB: `API_PC.acctBalance`

**Request** `{ "acc_id": 12345 }`

**Response 200**
```json
{ "saldo": "15000" }
```

**Xatolar:** 114, 102, 110 (status=1), 121 (status=2).

---

### 2.6 `/acct/wifipassword` — Wi-Fi parolini almashtirish

DB: `API_PC.acctWifiPassword`

**Request**
```json
{ "acc_id": 12345, "curr_password": "old", "new_password": "new" }
```

**Response 200** — body yo'q.

**Xatolar:** 114, 118 (parollardan biri bo'sh), 102, 110 (status=1), 121 (status=2), 123 (status=3 — joriy parol noto'g'ri).

---

### 2.7 `/acct/payments` — to'lovlar tarixi (oy bo'yicha)

DB: `API_PC.acctPayments`

**Request**
```json
{ "acc_id": 12345, "pay_month": "2026-08", "lang": "ru" }
```
`pay_month` formati **`YYYY-MM`** (qat'iy 7 belgi, real sana bo'lishi shart).

**Response 200**
```json
{
  "payments": [
    {
      "payment_id": 998877,
      "payment_date": "05.08.2026 14:23:11",
      "amount": 5000000,
      "payment_system": "Payme",
      "payment_status": "Проведён"
    }
  ]
}
```
- `amount` — **tiyinda** (`LOCAL_AMOUNT * 100`).
- `payment_system` — `NBANK_KASSA`; agar `BANK_KASSA == 5` bo'lsa → `NOTE` maydoni.
- Sanalar `dd.mm.yyyy hh24:mi:ss` (session NLS formati).

**Xatolar:** 114, 115 (oy noto'g'ri), 103, 110 (status=1), 121 (boshqa status).

---

### 2.8 `/traffic/detail` — trafik detalizatsiyasi

DB: `API_PC.TrafficDetail` (oy `YYMM` ko'rinishida uzatiladi)

**Request**
```json
{ "acc_id": 12345, "detail_month": "2026-08", "lang": "ru" }
```

**Response 200**
```json
{
  "detail": [
    {
      "event_time": "05.08.2026 14:23:11",
      "location_info": "AP-12",
      "traffic_input": 10485760,
      "traffic_output": 2097152,
      "pocket_info": "Безлимит 100"
    }
  ]
}
```
Trafik baytlarda (`ACCT_INPUT_OCTETS` / `ACCT_OUTPUT_OCTETS`).

**Xatolar:** 114, 115, 103. (Bu endpointda 110/121 tekshiruvi yo'q — bo'sh `detail` qaytadi.)

---

### 2.9 `/device/newoneclick` — "bir bosishda" qurilma qo'shish (2 bosqichli)

DB: `API_PC.DeviceNewOneClick`

**1-bosqich — `smsCode`siz (identifikatsiya + SMS):**
```json
{ "phn": "998901234567", "acc_id": 12345 }
```
`acc_id` ixtiyoriy (bo'sh bo'lsa DB'ga `NULL` uzatiladi).

**Response 200**
```json
{
  "smsSended": 1,
  "accs": [
    {
      "abonType": 1,
      "accId": 12345,
      "login": "user1",
      "contract": "D-000123",
      "saldo": 15000,
      "cost": 5000
    }
  ]
}
```
- `smsSended` = `1` faqat **bitta** akkaunt topilganda (SMS yuborilgan). Bir nechta akkaunt bo'lsa `0` — mijoz `accId` ni tanlab qayta so'raydi.
- Bitta akkaunt bo'lib, `saldo < cost` bo'lsa → xato **132**.
- SMS kodi PHP sessiyasida saqlanadi: `PHPSESSID = SESSID-APIPC-{phn}-{accId}`.

**2-bosqich — `smsCode` bilan:**
```json
{ "phn": "998901234567", "acc_id": 12345, "smsCode": "1234" }
```
Kod sessiyadagi bilan solishtiriladi, mos kelsa oqim `/device/new` blokiga o'tadi (`param1 = "new"`) va **body'siz 200** qaytadi.

**Xatolar:** 113, 120 (`acc_id` bo'sh yoki kod mos emas), 103, 110, 132, 111/112 + `/device/new` xatolari.

---

### 2.10 `/device/new` — qo'shimcha qurilma ulash

DB: `API_PC.DeviceNew` (ulanish sanasi = bugun; tranzaksiya commit/rollback bilan)

**Request** `{ "acc_id": 12345 }`

**Response 200** — body yo'q.

**Xatolar:** 114, 102, 110 (1), 121 (2), 127 (3 — bo'sh MAC), 128 (4 — bir martalik abonent), 132 (12/17 — balans), 100 (20 va boshqa noma'lum statuslar).

---

### 2.11 `/device/delete` — qurilmani o'chirish

DB: `API_PC.DeviceDel`

**Request**
```json
{ "acc_id": 12345, "permit_id": 555 }
```

**Response 200** — body yo'q.

**Xatolar:** 114, 119 (`permit_id <= 0`), 102, 110 (1), 122 (2), 100 (qolganlari).

---

### 2.12 `/device/list` — qurilmalar ro'yxati

DB: `API_PC.DeviceList`

**Request** `{ "acc_id": 12345 }`

**Response 200**
```json
{
  "devices": [
    {
      "permit_id": 555,
      "connect_date": "01.08.2026 00:00:00",
      "mac": "AA:BB:CC:DD:EE:FF",
      "ip": "10.1.2.3",
      "readonly": 1
    }
  ],
  "connect_cost": 5000
}
```
`connect_cost` — yangi qurilma ulash narxi (`oSrvCost`, **ko'paytirilmagan**, so'mda). `readonly` — qurilmani tahrirlash mumkinligi bayrog'i.

**Xatolar:** 114, 103, 110 (1), 121 (2).

---

### 2.13 `/tariff/available` — mavjud tariflar

DB: `API_PC.PermSRVRight`

**Request** `{ "acc_id": 12345, "lang": "ru" }`

**Response 200**
```json
{
  "tariffs": [
    {
      "tariff_id": 77,
      "tariff_name": "Безлимит 100",
      "cost": 15000000,
      "tspd": 10,
      "spdu": "Mbit",
      "tprd": 30,
      "prdu": "day",
      "vol": 0
    }
  ]
}
```
- `cost` — **tiyinda** (`COST * 100`).
- `tspd`/`spdu` — tezlik va uning birligi; `tprd`/`prdu` — davr va birligi; `vol` — trafik hajmi limiti.

**Xatolar:** 114, 103, 110 (1), 121 (2).

---

### 2.14 `/tariff/connected` — ulangan tariflar

DB: `API_PC.PermSRVLeft`

**Request** `{ "acc_id": 12345, "lang": "ru" }`

**Response 200**
```json
{
  "tariffs": [
    {
      "tariff_id": 77,
      "tariff_name": "Безлимит 100",
      "date_begin": "01.08.2026 00:00:00",
      "date_end": "31.08.2026 23:59:59",
      "tariff_isoff": 0
    }
  ]
}
```
`tariff_isoff` — tarif o'chirishga qo'yilganligi bayrog'i.

**Xatolar:** 114, 103, 110 (1), 121 (2).

---

### 2.15 `/tariff/connect` — tarifni ulash

DB: `API_PC.NewPermitSrv` (commit/rollback)

**Request**
```json
{ "acc_id": 12345, "tariff_id": 77, "tariff_conndate": "2026-09-01" }
```
`tariff_conndate` faqat ikki qiymatdan biri bo'lishi mumkin: **bugungi sana** yoki **keyingi oyning 1-sanasi** (`YYYY-MM-DD`).

**Response 200** — body yo'q.

**Xatolar:** 114, 119 (`tariff_id <= 0`), 124 (sana noto'g'ri), 102, 110 (1), 121 (2), 127 (3), 128 (4), **129** (12/17 — balans yetarli emas), 100 (qolganlari).

---

### 2.16 `/tariff/disconnect` — tarifni uzish

DB: `API_PC.DelPermitSrv`

**Request**
```json
{ "acc_id": 12345, "tariff_id": 77 }
```

**Response 200** — body yo'q.

**Xatolar:** 114, 119, 102, 110 (1), 122 (2), 100.

---

### 2.17 `/service/available` — mavjud qo'shimcha xizmatlar

DB: `API_PC.AddSRVRight`

**Request** `{ "acc_id": 12345, "lang": "ru" }`

**Response 200**
```json
{
  "services": [
    { "service_id": 91, "service_name": "Доп. устройство", "cost": 500000 }
  ]
}
```
`cost` — **tiyinda**.

**Xatolar:** 114, 103, 110 (1), 122 (2).

---

### 2.18 `/service/connected` — ulangan xizmatlar

DB: `API_PC.AddSRVLeft`

**Request** `{ "acc_id": 12345, "lang": "ru" }`

**Response 200**
```json
{
  "services": [
    {
      "permit_id": 555,
      "service_id": 91,
      "service_name": "Доп. устройство",
      "service_param": "MAC=AA:BB:...",
      "date_begin": "01.08.2026 00:00:00",
      "date_end": "31.08.2026 23:59:59"
    }
  ]
}
```

**Xatolar:** 114, 103, 110 (1), 122 (2).

---

### 2.19 `/service/connect` — xizmatni ulash

DB: `API_PC.NewAddSrv` (commit/rollback)

**Request**
```json
{ "acc_id": 12345, "service_id": 91, "service_conndate": "2026-09-01" }
```
`service_conndate` — bugun yoki keyingi oyning 1-sanasi.

**Response 200** — body yo'q.

**Xatolar:** 114, 130 (`service_id <= 0`), 131 (sana noto'g'ri), 102, 110 (1), 122 (2), 127 (3), 128 (4), **132** (12/17 — balans), 100.

> Xizmatni **uzish** uchun alohida endpoint yo'q — `/device/delete` (`API_PC.DeviceDel`) `permit_id` orqali ishlatiladi.

---

## 3. Xulosa jadval

| # | Endpoint | Asosiy request maydonlari | Success body |
|---|---|---|---|
| 1 | `/identify` | `phn`, `sendsms` | `{accs:[...]}` |
| 2 | `/verify` | `phn`, `smsCode` | — |
| 3 | `/abonent/info` | `acc_id` | abonent obyekti |
| 4 | `/abonent/edit` | `acc_id`, `email`, `phone` | — |
| 5 | `/acct/balance` | `acc_id` | `{saldo}` |
| 6 | `/acct/wifipassword` | `acc_id`, `curr_password`, `new_password` | — |
| 7 | `/acct/payments` | `acc_id`, `pay_month` | `{payments:[...]}` |
| 8 | `/traffic/detail` | `acc_id`, `detail_month` | `{detail:[...]}` |
| 9 | `/device/newoneclick` | `phn`, `acc_id`, `smsCode` | `{smsSended, accs:[...]}` yoki — |
| 10 | `/device/new` | `acc_id` | — |
| 11 | `/device/delete` | `acc_id`, `permit_id` | — |
| 12 | `/device/list` | `acc_id` | `{devices:[...], connect_cost}` |
| 13 | `/tariff/available` | `acc_id` | `{tariffs:[...]}` |
| 14 | `/tariff/connected` | `acc_id` | `{tariffs:[...]}` |
| 15 | `/tariff/connect` | `acc_id`, `tariff_id`, `tariff_conndate` | — |
| 16 | `/tariff/disconnect` | `acc_id`, `tariff_id` | — |
| 17 | `/service/available` | `acc_id` | `{services:[...]}` |
| 18 | `/service/connected` | `acc_id` | `{services:[...]}` |
| 19 | `/service/connect` | `acc_id`, `service_id`, `service_conndate` | — |

---

## 4. Integratsiya uchun muhim nuanslar

1. **Kodlash** — DB `windows-1251` da qaytaradi, PHP `iconv` bilan UTF-8 ga o'giradi. Faqat matnli maydonlar (ism, tarif nomi, manzil, status) o'giriladi.
2. **Pul birligi bir xil emas:**
   - `amount` (payments), `cost` (tariff/service available) — **tiyinda** (×100).
   - `saldo`, `connect_cost`, `newoneclick` dagi `saldo`/`cost` — **so'mda** (ko'paytirilmagan).
3. **Sana formatlari:**
   - Kirish: `YYYY-MM` (oylar), `YYYY-MM-DD` (ulanish sanasi).
   - Chiqish: `dd.mm.yyyy hh24:mi:ss` (NLS), `contract_date` esa `yyyy-mm-dd`.
4. **Token JSON string'ga bog'liq** — body'ni baytma-bayt bir xil yubormasa token buziladi. Serializatsiya (bo'shliq, kalit tartibi, unicode escaping) muhim.
5. **Sessiyaga tayanish** — `/device/newoneclick` SMS kodini PHP sessiyada saqlaydi (`SESSID-APIPC-{phn}-{accId}`), ya'ni ikki bosqich bir xil serverga tushishi kerak (yoki umumiy session store).
6. **Bo'sh body 200** — yozuv operatsiyalari hech narsa qaytarmaydi; muvaffaqiyatni faqat HTTP status bo'yicha aniqlash kerak.
7. **`lang`** — hozircha `uz`/`en` lug'atlari rus tilida; billing tomondan kelayotgan matnlar (tarif nomlari, statuslar) esa `iLang` bo'yicha DB'dan lokalizatsiya qilinadi.
