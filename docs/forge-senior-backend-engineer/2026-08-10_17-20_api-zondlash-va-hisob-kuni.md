# API zondlash + keyingi yechim sanasini shartnoma kunidan hisoblash

- **Sana:** 2026-08-10 17:20
- **SCOPE:** backend · **RISK:** false — pul harakati yo'q, faqat o'qish va ko'rsatish
- **Natija:** `php artisan test` → **81 passed (359 assertions)**

## 1. Savol: ishlatilmagan API bormi?

Uch tomondan tekshirildi.

**Hozirgi kod** 10 ta endpointni chaqiradi. **Eski Laravel 5.8 ilovasi**
(`git show HEAD:app/Helpers/Requests.php`) **aynan shu 10 tasini** ishlatgan —
qayta yozishda birorta endpoint yo'qolmagan.

Eski kodda ikkita **arvoh** qolgan: `Http/Requests/Wifi/Password.php`
(`curr_password`, `new_password`) va `Http/Requests/Abonent/Edit.php`
(`phone`, `email`). Ikkalasi ham `Requests.php` ga import qilingan, lekin hech
bir metod ularni ishlatmagan — ya'ni Wi-Fi parolini almashtirish va kontaktni
tahrirlash boshlangan va tashlab ketilgan.

**Javoblardagi ishlatilmayotgan maydonlar** (kelib turibdi, ekranda yo'q):
`status`, `address`, `contract_date`, `phone`, `email` (`/abonent/info`) ·
`pocket_info`, `location_info` (`/traffic/detail`) · `accs[].login`.

## 2. Zondlash — bo'shliqlar haqiqiymi?

Jonli API ga **faqat o'qish** so'rovlari yuborildi. Yozadigan nomlar
(`/wifi/password`, `/abonent/edit`) atayin sinalmadi: hisob real.

**Nazorat birinchi qurildi** — bu ishning asosiy g'oyasi. Mavjud bo'lmagan yo'l
qanday javob berishini bilmasdan turib, natijani o'qib bo'lmaydi:

```
/zzz/nonexistent            → 400 {"code":109,"errMsg":"Неопределенный метод"}
/abonent/info (acc_id siz)  → 400 {"code":114, "…(Лицевой счёт)"}
/acct/payments (oysiz)      → 400 {"code":115, "…(месяц)"}
```

`109` — "bunday metod yo'q". `114`/`115` — "metod bor, parametr yetishmayapti".
Shu farq butun tekshiruvni o'lchovga aylantirdi.

**30 ta nom sinaldi, 29 tasi `109` qaytardi.** Ya'ni `MISSING_APIS.md` dagi
bo'shliqlar — taxmin emas, o'lchangan fakt.

**Bittasi topildi:**

```
POST /acct/balance  {acc_id, lang}  →  200  {"saldo":"89645161"}
```

Hujjatda ham, eski kodda ham yo'q edi. U `saldo` birligi savolini yopdi:

```
/abonent/info  → "896451.61"   (so'm, kopeykasi bilan)
/acct/balance  → "89645161"    (tiyin) = aynan shu qiymat × 100
```

**Sana oralig'i parametrlari:** `date_from`/`date_to` yuborilsa `115` qaytadi,
oy bilan birga yuborilsa oraliq **jimgina e'tiborsiz** qoladi. `MAX_MONTHS = 12`
va har oyga alohida so'rov yechimi kuchida qoladi.

Batafsil: `docs/api/SOLA_API.md` §10.

## 3. Shartnoma sanasi — mijoz qarori

Mijoz "shartnoma qachon tugashini ko'rsatish kerak" dedi. Tekshiruv:

```
contract_date : '2019-07-17'      ← 7 yil oldin, ya'ni BOSHLANISH sanasi
```

Tugash sanasi API da yo'q va zondlash uni **hech qayerdan topmadi**. Bilvosita
yo'l ham yopiq: tarif muddati (`tprd`/`prdu`) `/tariff/available` da bo'lardi,
lekin bu abonentning joriy tarifi (id 1197) o'sha ro'yxatda umuman yo'q.

Uchta variant taklif qilindi (faqat boshlanish sanasi / shartnoma kunidan
hisoblash / billingni kutish). **Mijoz hisoblashni tanladi**, xavfi aytilgandan
keyin: agar billingdagi sikl boshqacha bo'lsa, har bir abonent noto'g'ri sana
ko'radi.

### Amalga oshirilishi

`AbonentProfile::chargeDate()` — billing sanasi bo'lsa **o'sha g'olib**, bo'lmasa
shartnoma kunidan hisoblanadi. `chargeDateIsEstimated()` qaysi biri ekanini
aytadi va bosh sahifa buni ekranda yozadi:

> Sana shartnoma kuni (har oyning 17-sanasi) bo'yicha hisoblangan — billing
> tasdiqlamagan.

Bu jumla — taxminni ko'rsatishning butun narxi. Abonent shu sanaga qarab to'lov
rejalashtiradi; uni operator tasdiqlagan fakt deb o'qishi mumkin emas.

### Qamrab olingan chekka holatlar

| Holat | Xatti-harakat |
|---|---|
| Billing sana bergan | O'sha ishlatiladi, "taxminiy" deb belgilanmaydi |
| Kun hali kelmagan | Shu oyning o'sha kuni |
| Kun o'tib ketgan | Keyingi oy |
| **Bugun aynan o'sha kun** | Bugun (— "bir oy qoldi" deyish eng qimmat xato) |
| **31-sana, fevral** | 28/29 ga **qisqartiriladi**, martga toshmaydi |
| Fevraldan keyin | Yana 31 ga qaytadi, 28 da qolib ketmaydi |
| Shartnoma kelajakda | O'z boshlanish sanasi |
| Shartnoma sanasi yo'q | Hech narsa — o'lchagich chizilmaydi |

**Qizil→yashil isbot** (`min()` olib tashlanganda):

```
⨯ a contract day the month does not have is clamped not rolled over
  -'2026-02-28'  +'2026-03-03'
qaytarilgach: ✓ 8 passed
```

`tests/Unit/EstimatedChargeDateTest.php` (8 ta test) + `CabinetTest` dagi eski
"o'lchagich chizilmaydi" testi yangi shartnomaga moslandi va uning yoniga
"na billing sanasi, na shartnoma sanasi" holati qo'shildi.

### Jonli tekshiruv

Bosh sahifa endi to'liq: `17.07.2026 — 17.08.2026`, "Yechishgacha 7 kun qoldi",
31 chiziqli o'lchagich va ostida taxmin haqidagi izoh.

---

## ⚠️ 4. Yuqoridagi 3-bo'lim O'SHA KUNI ORQAGA QAYTARILDI (17:50)

Mijoz tuzatdi: **17-sana hisob kuni emas.** Pul shartnoma kuniga emas, **oxirgi
tarif almashtirilgan kunga** qarab yechilar ekan.

Ya'ni hisoblashning asosi butunlay yo'q: oxirgi tarif sanasini API bermaydi va
`/tariff/history` mavjud emasligi shu kuni zondlashda isbotlangan. Bu sanani
kabinet **hech qanday mavjud maydondan** chiqara olmaydi.

**Nima qaytarildi:** `chargeDate()`, `chargeDateIsEstimated()`, `chargeDay()`,
`estimatedChargeDate()`, `onDay()`, ekrandagi izoh, uchala tildagi
`dash.charge_estimated` kaliti va `EstimatedChargeDateTest`. Kontroller yana
`nextChargeDate()` ni o'qiydi. `81 → 72 passed`.

**Nima qoldi:**

- `AbonentProfile` da fallback **yo'qligini** tushuntiruvchi izoh — nega yo'qligi
  yozilmasa, bu qisqa yo'l qaytadan yozilishi aniq edi.
- `CabinetTest::no_charge_date_means_no_meter_rather_than_an_invented_one`
  testi: fake profilda `contract_date` **bor**, o'lchagich esa baribir
  chizilmasligi kerak. Aynan shu assertion kelajakda "oson-ku, contract_date
  bor-ku" degan tuzatishni qizil qiladi.

**Nima o'rganildi (bu qolsin):** hisob sanasi oxirgi tarif o'zgarishiga bog'liq.
Demak `/tariff/history` — TZ §12 uchun tarix emas, **hisob sanasiga olib
boradigan ikkinchi yo'l**. Billingga so'rov shu bilan kuchayadi:
`next_charge_date` **yoki** `/tariff/history` — ikkalasidan biri yetadi.

**Xulosa:** taxminni ekranga chiqarish qarori bir necha soat yashadi va domen
faktiga urilib qaytdi. Yaxshi tomoni — u ekranda "billing tasdiqlamagan" degan
yozuv bilan chiqqan edi, ya'ni noto'g'ri sana hech qachon fakt sifatida
ko'rsatilmadi.

## Ochiq qolgani

- `curr_tariff_cost` hali ham yo'q, shuning uchun "Keyingi hisobdan yechish"
  blokida **summa emas, faqat sana** ko'rinadi va balans hukmi ("yetadi /
  yetmaydi") hali chiqmaydi.
- Billing `next_charge_date` bergan kuni taxmin o'z-o'zidan o'chadi —
  `AbonentProfile.php:41` ga bitta kalit qo'shiladi, boshqa hech narsa emas.
- `/acct/balance` topildi, lekin hozircha ishlatilmaydi.
