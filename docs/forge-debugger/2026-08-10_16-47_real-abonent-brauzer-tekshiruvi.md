# Real abonent bilan brauzer tekshiruvi — topilgan va tuzatilgan nuqsonlar

- **Sana:** 2026-08-10 16:47
- **Muhit:** `http://127.0.0.1:8080/`, foydalanuvchining Chrome sessiyasi
- **Abonent:** TEST PAYMENTS, hisob 1336708 (jonli SOLA billing)
- **SCOPE:** frontend + backend (ko'rsatish qatlami) · **RISK:** false — pul harakati,
  avtorizatsiya va shaxsiy ma'lumot yo'lida hech narsa o'zgarmadi
- **Natija:** `php artisan test` → 72 passed (341 assertions), shundan 5 tasi yangi

Oltita sahifa aylanib chiqildi: `/`, `/tariffs`, `/devices`, `/statistics`,
`/finance`, `/services` — ikkala mavzuda. Pul sarflaydigan tugmalar (tarif
almashtirish, qurilma qo'shish) **bosilmadi**: hisob real.

---

## 1. To'langan to'lovlar kulrang ko'rinardi — eng jiddiysi

**Ko'rilgani:** `/finance` da to'rtala qator `— to'langan` deb, neytral kulrang
kapsulada chiqdi. TZ §6 bo'yicha bu yashil bo'lishi kerak.

**Sababi:** billing status matnini **so'rov tiliga tarjima qilib** qaytaradi.
`lang=uz` da javob `to'langan` bo'lgan, `BillingHistory::paymentTone()` esa
faqat ruscha (`опла…`) va inglizcha (`paid`, `success`) o'zaklarni bilardi.
Mos kelmagan status neytral tonga tushadi — ya'ni nuqson jimgina ishlardi.

Abonent uchun bu shunchaki rang emas: muvaffaqiyatli to'lov kulrang "noaniq"
kapsulada ko'rinsa, u "pulim tushmadimi?" deb o'qiydi.

**Tuzatildi:** `app/Support/BillingHistory.php` — o'zbekcha `to'lan` o'zagi
qo'shildi. Ikkita tuzoq hisobga olindi:

- **Apostrof to'rt xil yoziladi** (`'`, `` ` ``, `ʻ`, `ʼ`) — hammasi taqqoslashdan
  oldin bittaga keltiriladi, aks holda `to\`langan` yana tanilmay qolardi.
- **`to'lanmagan` ichida ham shu o'zak bor.** Salbiy shaklni "to'langan" deb
  ko'rsatish — bu fayldagi yagona xato turi bo'lib, abonentga pul jihatidan
  zarar yetkazadi. Lookahead bilan chiqarib tashlandi.

**Qizil→yashil isbot:**

```
to'langan      eski: NEYTRAL   yangi: ok
to`langan      eski: NEYTRAL   yangi: ok
to'lanmagan    eski: NEYTRAL   yangi: neytral
```

Regressiya testi: `tests/Unit/PaymentToneTest.php` (5 ta test, 17 assertion).

---

## 2. Tarif narxi birligisiz chiqardi

`/tariffs` da har bir qatorda `56 000 000` degan yalang'och raqam turardi —
so'mmi, tiyinmi, oymi, kunigami: yozilmagan. `resources/views/cabinet/tariff.blade.php`
dagi ikkala ro'yxatga (o'qish uchun va tanlash uchun) birlik qo'shildi.

Birlik `text-sm` va xira: raqam asosiy, birlik uni tasdiqlaydi — shu bilan
qo'shilgan kenglik ham eng kam bo'ldi.

---

## 3. Valyuta yozuvi noto'g'ri edi: `So\`m`

`lang/uz/app.php` da `'ye' => 'So\`m'` — bosh harf va **gravis** (`` ` ``), ya'ni
apostrof emas. Ekranda `896 452 So\`m` bo'lib turardi.

Uchala tilda ham tuzatildi va kichik harfga keltirildi (`so'm`, `сум`, `sum`) —
kalit har doim raqamdan **keyin** ishlatiladi, ya'ni jumla boshi emas.

---

## 4. Muddat birliklari jumla o'rtasida bosh harf bilan

`3 Mbit/s · 22 Kun` → `22 kun`. Xuddi shunday `Soat`/`Daqiqa` va ruscha
`Дней`/`Час(ов)`/`Минут`, inglizcha `Days`/`Minute`. Bu kalitlar faqat
`$validity()` ichida, jumla o'rtasida ishlatiladi (tekshirildi).

---

## 5. Bo'sh holat matni eskirgan UI ga murojaat qilardi

`/statistics` va `/finance` da yozuv: "Bu **oy** uchun trafik yozuvlari yo'q.
Boshqa **oyni yuqoridagi tugma** orqali tanlang". Ammo oy tanlagich
(`month-picker`) allaqachon o'chirilgan — endi u yerda **sana oralig'i**
maydonlari turibdi. Abonent mavjud bo'lmagan tugmani qidirardi.

Uchala tilda "davr" va "yuqoridagi maydonlar" ga o'zgartirildi.

---

## 6. Manfiy summa defis bilan yozilardi

`/finance` da tuzatuv qatori `-145 000` ko'rinishida edi. Bosh sahifa allaqachon
haqiqiy minus (`−`) ishlatardi; jadval undan chetda qolgan. Endi umumiy summa va
har bir qator bitta `$money()` yopilmasidan o'tadi.

Saralash buzilmadi: u ko'rinadigan matnni emas, `data-value` dagi xom qiymatni
o'qiydi. CSV eksporti ham buzilmadi — u yerda summa allaqachon bo'shliqli matn
edi, Excel uni ilgari ham raqam sifatida o'qimasdi.

---

## Tasdiqlangan, lekin tuzatilmagan (billing kutilmoqda)

Bosh sahifadagi banner jonli ma'lumot bilan **faqat balansdan** iborat ekani
tasdiqlandi: kun o'lchagichi ham, "Keyingi hisobdan yechish" bloki ham, balans
hukmi ham chizilmadi. Uchalasi ham `next_charge_date` va `curr_tariff_cost` ga
bog'liq, ular esa `/abonent/info` da yo'q (`MISSING_APIS.md`). Foydalanuvchi
bilan kelishuv: **billing kutiladi**, kodda taxminiy sana hisoblanmaydi.

Yon kuzatuv: tarif nomining o'zida narx bor ("Smart 300 - 355 000 сум"). Ya'ni
narx billingda **bor**, faqat alohida maydon sifatida emas. Nomni ajratib olish
mumkin, lekin bu nomlash konvensiyasiga tayanadi va u har lahzada buzilishi
mumkin — shuning uchun qilinmadi.

## Yorug' mavzu — birinchi marta real brauzerda

Hisobotdagi 1-risk yopildi: `/finance` (jadval, kapsulalar, qidiruv) va
`/statistics` (yoy, bo'sh holat) yorug' mavzuda ko'zdan kechirildi, nuqson
topilmadi. Foydalanuvchining tanlovi tekshiruvdan keyin qorong'iga qaytarildi.

## Tegilmagani

- Tarif almashtirish va qurilma qo'shish oqimlari — real hisobda pul sarflaydi.
- `касса`, `Smart 300 - 355 000 сум` kabi ruscha matnlar — bular billingdan
  keladigan ma'lumot, ilova ularni tarjima qilmaydi.
