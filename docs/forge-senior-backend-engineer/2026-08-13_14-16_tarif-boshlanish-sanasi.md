# Tarif boshlanish sanasi — `/tariff/connected` topildi va ulandi

- **Sana:** 2026-08-13 14:16
- **SCOPE:** backend · **RISK:** false — faqat o'qish, pul harakati yo'q, yangi
  maydon ko'rsatiladi xolos
- **Natija:** `php artisan test` → **84 passed (373 assertions)** · Pint → passed

## 1. Kelib chiqishi

`docs/task/apipc/` — billing gateway'ning haqiqiy manbasi. Uni o'qib chiqib
ma'lum bo'ldiki, kabinet **10 ta** endpointni biladi, manbada esa **19 ta**.
To'liq ma'lumotnoma yozildi: `docs/api/SOLA_API_REFERENCE.md`.

Ishlatilmayotganlar orasida `/tariff/connected` (`API_PC.PermSRVLeft`) bor edi,
va u `date_begin` qaytarardi — ya'ni SOLA'dan so'ralayotgan
`current_tariff_start_date` ning o'zi.

## 2. Zondlash to'sig'i — docker, VPN emas

API `172.19.1.101` da. Boshida VPN yo'qligi sabab deb o'ylandi (`openvpn3
sessions-list` bo'sh, `tun0` — bu mashinaning **o'z serveri**). Aslida sabab
boshqa: **`ollama_default` docker tarmog'i `172.19.0.0/16` ni egallagan**.

```
ip route get 172.19.1.101  →  dev br-7e93529b97be   (docker bridge)
```

Bu `docker-compose.yml` dagi izohda tasvirlangan muammoning aynan o'zi, faqat
boshqa loyihaning tarmog'i sabab. VPN **umuman kerak emas** — API lokal
tarmoqdan ochiq, 70–220 ms.

Ollama vaqtincha `down` qilinib, zond o'tkazilib, darhol `up -d` bilan tiklandi.

## 3. Zond natijasi (hisob 1336708)

```json
{"tariffs":[{"tariff_id":"1197","tariff_name":"Smart 300 - 355 000 сум",
             "date_begin":"2026-08-10 16:34:27","date_end":null,"tariff_isoff":"0"}]}
```

`tariff_id` = `/abonent/info` dagi `curr_tariff_id` — **mos keldi**.

Uchta cheklov aniqlandi: `date_begin` — **vaqt bilan** (sana emas); `date_end`
faol tarifda `null` (ya'ni `next_charge_date` chiqmaydi); faqat **bitta** qator
(ya'ni **tarix emas**, `/tariff/history` baribir kerak).

Yo'l-yo'lakay: `/service/available` va `/service/connected` ham ishlaydi (200),
sinov hisobida xizmat yo'qligi uchun bo'sh massiv qaytardi.

## 4. O'zgarishlar

| Fayl | Nima |
|---|---|
| `app/Services/Sola/SolaClient.php` | `connectedTariffs()` |
| `app/Support/ConnectedTariff.php` | **yangi** — javobni `curr_tariff_id` bo'yicha juftlaydi, `startedAt()` |
| `app/Http/Controllers/TariffController.php` | uchinchi chaqiruv, `startedAt` view'ga |
| `resources/views/cabinet/tariff.blade.php` | joriy tarif kartochkasida sana |
| `lang/{uz,ru,en}/app.php` | `tariff.started_at` |
| `app/Services/Sola/FakeSolaServer.php` | `/tariff/connected` (offlayn ish uchun) |
| `app/Support/AbonentProfile.php` | eskirgan izoh tuzatildi |
| `tests/Feature/CabinetTest.php` | 2 ta test + `fakeSola()` ga default |

Juftlash **`tariff_id` bo'yicha**, nom bo'yicha emas — billing ba'zi nomlarni
ortiqcha probel bilan yuboradi (bu qoida kod bazasida allaqachon bor edi).

Mos qator topilmasa — sana **ko'rsatilmaydi**. Birinchi qatorni olish varianti
ataylab rad etildi: abonent ko'rgan sana bo'yicha to'lov rejalashtiradi.

`AbonentProfile::nextChargeDate()` da fallback **hali ham yo'q**: sana endi bor,
lekin undan yechish kunini chiqaradigan **qoida** noma'lum. Qoida so'ralgan.

## 4a. Yechish sanasi — mijoz qoidani aytdi

Sessiya oxirida mijoz qoidani berdi: **davr tarif boshlangan kunida tugaydi,
keyingi oyda.** Ya'ni SOLA'dan `next_charge_date` so'rash ham kerak emas.

`ConnectedTariff::nextChargeDate()` qo'shildi, `CabinetController` unga fallback
qiladi (billing o'z sanasini yuborsa — o'shanisi ustun). Shu bilan bosh
sahifadagi kun o'lchagichi **ishga tushdi** — ilgari u hech qachon chizilmasdi.

Qarorlar:

- Birinchi yechish — boshlanishdan **bir oy keyin**, ya'ni bugun ulangan tarif
  bugun yechilmaydi
- Anker kuni **oldinga yurgiziladi** — 2019-yildan beri turgan tarif uchun ham
  shu oyning sanasi chiqadi
- **31-kun** qisqa oyda 28/29/30-ga bosiladi (`addMonthNoOverflow`) —
  `ChargeCycle::endingAt()` allaqachon shu talqinda ishlagan, ikki uchi mos
  bo'lishi uchun shunday qilindi. Bu **taxmin**, xatda tasdiqlash so'ralgan

Yo'l-yo'lakay **haqiqiy bug topildi:** `CarbonImmutable::parse("0000-00-00")`
istisno tashlamaydi — u `-0001-11-30` qaytaradi. `AbonentProfile::date()` dagi
`try/catch` buni hech qachon ushlamagan (izohda esa ushlaydi deb yozilgan edi),
ya'ni shartnoma sanasi o'rniga ekranga `30.11.-001` chiqishi mumkin edi. Yangi
kodda esa bu `nextChargeDate()` ni ikki ming yil oldinga yurishga majburlardi.
Ikkala joyga ham yil bo'yicha chegara qo'yildi.

Jonli tekshiruv (hisob 1336708, tarifi o'sha kuni almashtirilgan):

```
tarif      : Strong 100 - 175 000 сум (id 1218)
boshlandi  : 13.08.2026
keyingi    : 13.09.2026        ← bugun emas, to'g'ri
davr       : 13.08.2026 -> 13.09.2026 · 31 kun
```

Testlar: `ConnectedTariffTest` (9 ta, chegaraviy holatlar) +
`CabinetTest::the_meter_is_drawn_from_the_day_the_tariff_started`.
`no_charge_date_means_no_meter_rather_than_an_invented_one` **saqlandi** — u
`contract_date` dan hisoblashga qaytishni to'sib turadi.

## 5. Hujjatlar

- `docs/api/SOLA_API_REFERENCE.md` — **yangi**, 19 endpoint, xato kodlari
  spravochnigi, zondlash eslatmasi
- `docs/api/SOLA_API.md` — o'zaro havola
- `docs/task/BILLING_API_TALABLARI.md` — "Yopildi" bo'limi; talab ro'yxatidan
  `current_tariff_start_date` olindi; 8 ta savoldan 4 tasi koddan yopildi
- `docs/task/BILLING_API_ZAPROS.ru.md` — **yangi**, SOLA'ga yuboriladigan xat

## 6. Qoldi

- `/tariff/connected` xulqini SOLA tasdiqlashi (tarixmi yoki faqat faolmi)
- **31-kun** qisqa oyda qanday ishlashi — yagona taxmin qolgan joy
- `/tariff/history`, `curr_tariff_cost` — baribir kerak
- `payment_status`, `readonly`, `connect_cost = -1` — billing javobi kerak
- O'zgartiruvchi 8 ta endpoint hech qachon jonli sinalmagan (sinash mumkin emas)
