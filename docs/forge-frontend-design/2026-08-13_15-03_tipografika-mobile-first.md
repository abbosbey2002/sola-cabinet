# Tipografikani qayta kalibrlash — mobile-first

- **Sana:** 2026-08-13 15:03
- **Rejim:** SaaS product + Mobile-first consumer + Multi-market (uz/ru/en)
- **SCOPE:** frontend · **RISK:** false — token va markup, pul/auth yo'lida emas
- **Natija:** `php artisan test` → **84 passed** · Pint → passed · 360px'da
  6 sahifada gorizontal overflow **yo'q**

## 1. Tashxis

Matn ikki qavat kattalashtirilgan edi:

1. `html { font-size: 118.75% }` → `1rem = 19px`
2. Shkalaning o'zi ham ko'tarilgan: `--text-base: 1.125rem`

Ikkisi ko'paytirilib, tana matni **21.4px**, eng katta figura **54.6px** bo'lgan.
360px ekranda bu devor: balans raqami va valyuta alohida qatorlarga tushardi.

## 2. Nega kattalashtirilgan edi va nega qaytarildi

Bu tasodif emas edi — CSS izohida yozilgan: auditoriya nafaqaxo'rlar, kunduzi
telefonda hisob to'laydi.

Lekin **foizli root foydalanuvchi sozlamasiga ko'paytiriladi.** Android'da
matnni kattalashtirgan odam 19px × o'z koeffitsiyentini olardi — ya'ni eng ko'p
yordamga muhtoj o'quvchi eng buzuq sahifani ko'rardi. Sahifa uning o'rniga qaror
qabul qilib, u allaqachon aytgan tanlovni bekor qilardi.

Endi root **100%**: telefon qulay deb bilgan o'lcham — kabinetning o'lchami.
Kattaroq xohlaganlar uchun sahifada **allaqachon** boshqaruv bor (`data-text`
lg/xl, ko'rinish paneli) — bu bir marta qilinadigan va saqlanadigan tanlov.
Ya'ni imkoniyat yo'qolmadi, o'quvchining qo'liga qaytdi.

## 3. Yangi shkala

Mobile-first: pastdagi px — 360px Android'da ko'rinadigan o'lcham.

| Token | Avval | Endi | @360 | @1440 |
|---|---|---|---|---|
| `xs` | 19.0px | `0.875rem` | 14 | 14 |
| `sm` | 20.2px | `0.9375rem` | 15 | 15 |
| `base` | 21.4px | `1rem` | **16** | 16 |
| `lg` | 23.8px | `1.125rem` | 18 | 18 |
| `xl` | 27.3px | `1.25rem` | 20 | 20 |
| `2xl` | 34.4px | `clamp()` | 22 | 28 |
| `3xl` | 42.8px | `clamp()` | 26 | 36 |
| `4xl` | 54.6px | `clamp()` | **30** | 48 |

Tana **16px** — iOS'da maydonga fokus tushganda sahifani kattalashtirmaydigan
chegara. O'qish qadamlari (xs…xl) qat'iy: viewport bilan o'sadigan izoh
o'quvchiga hech narsa bermaydi. Faqat **display qadamlari** `clamp()` bilan
o'sadi — ikkala uchi ham `rem` da, shuning uchun `data-text` ularni baribir
kattalashtiradi; faqat o'rta had viewport'ga bog'liq. Shu narsa buni
"desktop dizayni kichraytirilgan" emas, **mobile-first egri chiziq** qiladi.

`data-text` qadamlari: 131.25%/145% → **112.5%/125%** (18px / 20px tana).

## 4. Topilgan mobil bug

Tarif ro'yxati `radio | nom | narx` uch ustunli qator edi, narx `shrink-0`.
360px'da nomga ~130px qolib, billing bergan uzun nom (`"SSID - 56 000 000 сум"`)
**har bir so'z alohida qatorga** tushardi.

Yechim breakpoint'siz: nom va narx `flex-wrap` + `justify-between` bilan bitta
qatorni bo'lishadi — sig'masa narx o'z qatoriga tushadi, **istalgan kenglikda**.
`items-center` → `items-start`, chunki spetsifikatsiya qatori telefonda 2-3
qatorga cho'ziladi va radio tanlayotgan nomidan uzoqlashib ketardi.

`.u-btn-sm` (paginatsiya, qurilma o'chirish) — 44px, e'lon qilingan minimum,
lekin `@media (pointer: coarse)` da to'liq 48px oladi: telefonda aynan shu ikki
tugma eng ko'p xato bosiladi.

## 5. Tekshiruv (haqiqiy 356px viewport, iframe orqali)

| Sahifa | Overflow | Eng kichik matn |
|---|---|---|
| `/` `/tariffs` `/devices` `/statistics` `/finance` `/services` | **yo'q** (hammasi) | **14px** (hammasi) |

- To'lovlar va qurilmalar jadvali telefonda **kartochka rejimida** (`display:block`,
  `thead` yashirin) — yon tomonga siljish yo'q
- 44px'dan kichik yagona element — `sr-only` "kontentga o'tish" havolasi, fokusgacha
  yashirin, to'g'ri xulq
- Tarif radiolari 20px, lekin `<label>` butun qatorni o'rab turibdi — haqiqiy
  tegish maydoni to'liq qator
- Ruscha (eng uzun satrlar) va o'zbekcha, yorug' va qorong'i mavzu — hammasi tekshirildi

## 6. Hal qilinmagan — mijoz qarori kerak

Billing tarif nomining **ichiga narxni yozib yuboradi**:
`"SSID - 56 000 000 сум"`. Kabinet esa yonida `cost` maydonidan narxni yana
ko'rsatadi. Natijada har bir qatorda narx **ikki marta** chiqadi va telefonda
bitta qator behuda ketadi.

Nomdan narxni kesib tashlash mumkin, lekin bu billing satrlariga heuristika
qo'llash bo'ladi (`"Region new 10 - 100 000 sum"`, `"20 Мбит/с - 400 000 сум"` —
shakl bir xil emas). Kod bazasi bunday taxminlardan qochadi. Ikki yo'l bor:
SOLA'dan nomni narxsiz berishni so'rash, yoki `cost` ustunini ro'yxatdan olib
tashlash. Qaror mijozniki.
