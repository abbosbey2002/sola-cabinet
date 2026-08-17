# SOLA Billing API — yetishmayotgan maydon va endpointlar

> **Yuboriladigan xat:** [`BILLING_API_ZAPROS.ru.md`](BILLING_API_ZAPROS.ru.md) —
> shu hujjat asosida ruscha tayyorlangan versiya. Bu yerda ichki hisob-kitob,
> u yerda hamkasblarga ketadigan matn.

`http://172.19.1.101:808` · `POST` + JSON · Basic + `X-Access-Token` · so'rovda
`acc_id` + `lang` · sana `Y-m-d` · pul **tiyinda** (int)

## Kerak

| P     | Endpoint                            | Qo'shilsin                                | Tur                                                                                                     |
| ----- | ----------------------------------- | ----------------------------------------- | ------------------------------------------------------------------------------------------------------- |
| **0** | `/tariff/history`                   | **yangi** — `history[]`                   | `change_date`, `old_tariff_name`, `old_tariff_cost`, `new_tariff_name`, `new_tariff_cost`, `changed_by` |
| **0** | `/abonent/info`                     | `curr_tariff_cost`                        | int, tiyin                                                                                              |
| 1     | `/abonent/info`                     | `contract_number`                         | string                                                                                                  |
| 1     | `/abonent/info`                     | `next_tariff_name`, `next_tariff_cost`    | string \| null · int \| null                                                                            |
| 1     | `/acct/payments`                    | `payment_status_code` ²                   | `paid` \| `pending` \| `failed` \| `cancelled`                                                          |
| 2     | `/traffic/detail`, `/acct/payments` | `date_from`, `date_to` **parametrlari** ³ | `Y-m-d`, ixtiyoriy, inklyuziv                                                                           |
| 2     | `/loyalty/info`                     | **yangi** ⁴                               | `bonus_balance` int · `tier` string \| null · `privileges[]{name, description}`                         |

² Hozir `payment_status` erkin matn va `lang` bo'yicha tarjima qilinadi
(`"to'langan"` / `"оплачено"`), ya'ni rang matnni taniganiga bog'liq. Muqobil:
qiymatlarning to'liq ro'yxati.
³ Hozir faqat bitta oy so'raladi: 12 oylik hisobot = 12 ta HTTP so'rov.
⁴ Sodiqlik dasturi alohida servisda bo'lsa — endpoint emas, **URL** yetadi.

## Yopildi — `current_tariff_start_date` so'ralmaydi

**Jonli tekshiruv, 2026-08-13, hisob 1336708.** `/tariff/connected` (`API_PC.PermSRVLeft`)
ulangan tarifni **boshlanish sanasi bilan** qaytarar ekan:

```json
{"tariffs":[{"tariff_id":"1197","tariff_name":"Smart 300 - 355 000 сум",
             "date_begin":"2026-08-10 16:34:27","date_end":null,"tariff_isoff":"0"}]}
```

`tariff_id` = `/abonent/info` dagi `curr_tariff_id` — **mos keldi**. Ya'ni joriy
tarif boshlanish sanasi allaqachon mavjud, `/abonent/info` ga yangi maydon
qo'shish shart emas.

Kabinet uni shunday oladi: `/tariff/connected` dan `tariff_id == curr_tariff_id`
bo'lgan qatorni topib, `date_begin` ni olish.

Ehtiyot bo'ling:

- `date_begin` — **`Y-m-d H:i:s`**, ya'ni sana emas, **vaqt bilan**
- `date_end` faol tarifda `null`, shuning uchun undan `next_charge_date` chiqmaydi
- Faqat **bitta** qator qaytdi (faol tarif) — bu **tarix emas**, `/tariff/history`
  baribir kerak
- Narx yo'q — `curr_tariff_cost` baribir kerak

## Yopildi — `next_charge_date` ham so'ralmaydi

**Mijoz qoidani aytdi (2026-08-13):** tarif davri **boshlangan kunida tugaydi**,
keyingi oyda. Ya'ni yechish sanasi — har oyning o'sha kuni.

Boshlanish sanasi yuqorida topilgani uchun sanani kabinet **o'zi hisoblaydi**:

```
boshlanish 13.08.2026  →  keyingi yechish 13.09.2026
boshlanish 10.06.2026, bugun 13.08  →  keyingi yechish 10.09.2026
```

`App\Support\ConnectedTariff::nextChargeDate()`. Birinchi yechish — boshlanishdan
**bir oy keyin**, ya'ni bugun ulangan tarif bugun yechilmaydi. Eski tarif uchun
anker kuni oldinga yurgiziladi (2019-yildagi sana qaytarilmaydi).

Aniqlanmagan yagona nuqta: **31-kun**. Qisqa oyda 28/30-ga bosiladi
(`addMonthNoOverflow`) — `ChargeCycle` allaqachon shu talqinda ishlaydi. Buni
xatda tasdiqlash so'ralgan.

Kabinet bu endpointni ilgari umuman chaqirmagan (`SolaClient` da metodi yo'q edi).

## Kod bo'yicha aniqlandi

Quyidagilar `apipc/` manbasidan o'qildi — billing javobini kutish shart emas.

| Savol             | Javob                                                                                                                                                                  | Manba                                    |
| ----------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ---------------------------------------- |
| Xato kodlari      | To'liq spravochnik mavjud: `0` · `100–103` (tizim/BD) · `109–132` (biznes). `109` = noma'lum `action`/`param1`, `110` = abonent topilmadi, `114` = `acc_id` yo'q/xato, `115` = oy yo'q/xato | `constants.php:56–118`                   |
| `status`          | Bu status emas — `wAbonInfo.OffReasonName`, ya'ni **o'chirilish sababi**ning nomi. Abonent faol bo'lsa bo'sh keladi. Erkin matn, enum emas, maks. 50 belgi              | `main.php:318,336` · `functions.php:336` |
| `abonType`        | `< 0` — topilmadi · `0` — **vaqtinchalik**, PK ga ruxsati yo'q · `1` — **bir martalik** · `≥ 2` — **doimiy**: *yuridik* yoki *jismoniy* shaxs (bitta qiymat emas!). `0` uchun xato kodi endpointga qarab farq qiladi: `/identify` → `126`, `/abonent/info` → `121`. Qaysi raqam yuridik/jismoniy — aniqlanmagan | `main.php:179–190, 347–358` + mijoz |
| `address` tili    | `iLang` protsedurga uzatiladi, lekin natija baribir windows-1251 rus matni — tarjima bazada bajarilmayapti. Gateway faqat `iconv` qiladi                                | `main.php:304,322` · `functions.php:342` |
| `phone` qayta ishlanadimi | Yo'q. Bazadan xom holda o'tadi — `iconv` ham, trim ham yo'q. Bind uzunligi 100 (`email` esa 50), ya'ni maydon **ro'yxat** saqlashga mo'ljallangan ko'rinadi     | `main.php:332` · `functions.php:341`     |

## Billing javobi kerak

Bu to'rttasi Oracle'dan keladi, gateway ularni faqat uzatadi — qiymatlar
ro'yxatini kod bilan aniqlab bo'lmaydi.

| Maydon                 | Manba ustuni / bind      | Nima kerak                                                          |
| ---------------------- | ------------------------ | ------------------------------------------------------------------- |
| `payment_status`       | `PAYMENT_STATUS`         | Mumkin bo'lgan qiymatlarning to'liq ro'yxati (izoh ² bilan bog'liq) |
| `readonly`             | `DEVICE_READONLY`        | Qiymatlar va ma'nosi                                                |
| `connect_cost = "-1"`  | `API_PC.DeviceList` → `oSrvCost` | `-1` "ulash mumkin emas"ni bildiradimi yoki "narx yo'q"ni?  |
| `phone` formati        | `wAbonInfo.Phone`        | `"712070807,,"` — vergul bilan ajratilgan ro'yxatmi? Bo'sh elementlar nimani anglatadi? |

## Izoh

Login/parol avtorizatsiyasi (TZ §1.2) hozir talab qilinmaydi — kirish telefon +
SMS orqali, TZ o'zi "без изменений" deydi. Qaror o'zgarsa alohida 4 ta endpoint
kerak bo'ladi.
