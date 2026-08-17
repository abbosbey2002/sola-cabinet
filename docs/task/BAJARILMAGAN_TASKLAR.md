# TZ v1 — bajarilmagan tasklar

**Manba:** `docs/task/tz_v1.docx`
**Sana:** 2026-08-07
**Holat:** quyidagi 9 ta punkt bajarilmadi. Har biri uchun: talab → nega bajarilmadi → nima kerak.

Bajarilganlar ro'yxati eng oxirida.

---

## Umumiy sabab: ilovada ma'lumotlar bazasi yo'q

Kabinet hech qanday DB ishlatmaydi. Ekranda ko'rinadigan **har bir raqam** SOLA
billing API'sidan (`API_IP=172.19.1.101:808`) real vaqtda olinadi. API bermagan
ma'lumotni ilova hech qayerdan ola olmaydi — uni "hisoblab chiqarish" ham
mumkin emas.

Shuning uchun quyidagi tasklarning aksariyati **frontend ishi emas, billing API
tomonidagi ish**. Kod tayyor va kutmoqda — API maydonni bergan kunidan boshlab
UI o'zi to'ldiriladi.

---

## 1. Договор № (shartnoma raqami) — qisman

**Talab (TZ §2, §12):** sarlavhada abonentning shartnoma raqamini ko'rsatish
(maketda `Договор: ТТР19000036`).

**Nega bajarilmadi:** `/abonent/info` javobida shartnoma raqami maydoni
topilmadi. Eski kabinet ham uni hech qachon ko'rsatmagan — mavjud 10 ta
maydonda (`name`, `email`, `phone`, `address`, `contract_date`, `status`,
`saldo`, `curr_tariff_name`, `device_count`, `device_active_count`) u yo'q.
Faqat `contract_date` (ulanish **sanasi**) bor, raqam emas.

**Nima kerak:**
1. Billing'dan `/abonent/info` javobiga shartnoma raqamini qo'shishni so'rash.
2. Maydon nomi ma'lum bo'lgach — `app/Support/AbonentProfile.php` dagi
   `CANDIDATE_CONTRACT` ro'yxatiga qo'shish. **Boshqa hech narsa
   o'zgartirilmaydi**, UI o'zi ko'rina boshlaydi.

> UI tayyor: maydon bo'lmasa sarlavhada shunchaki ko'rinmaydi, "—" chiqmaydi.

---

## 2. Следующий тариф (keyingi tarif) — qisman

**Talab (TZ §3.1, §3.2, §13):** joriy tarif yonida keyingi tarifni va uning
narxini ko'rsatish; tarif almashtirilgach, yangi tarif "Следующий тариф"
sifatida chiqishi kerak.

**Nega bajarilmadi:** API'da keyingi tarif maydoni yo'q. `/tariff/connect`
so'rovi tarifni navbatga qo'yadi, lekin `/abonent/info` javobida navbatdagi
tarif qaytmaydi — ya'ni ilova "qaysi tarif kutmoqda" degan savolga javob
beradigan manbaga ega emas.

**Nima kerak:**
1. Billing `/abonent/info` ga `next_tariff_name` + `next_tariff_cost` (tiyinda)
   qo'shsin.
2. `AbonentProfile::CANDIDATE_NEXT_TARIFF` va `CANDIDATE_NEXT_TARIFF_COST` ga
   haqiqiy nomlarni yozish.

> Blok tayyor: hozir maydon bo'lmasa "Не выбран" chiqadi (TZ §3.2 talabidek).

---

## 3. Дата следующего списания — qisman

**Talab (TZ §3.1, §14):** keyingi hisobdan yechish sanasini ko'rsatish.

**Nega bajarilmadi:** API'da bunday maydon yo'q. Uni `contract_date` dan
hisoblab chiqarish **mumkin emas** — bu billing sikli, to'lov tarixi va tarif
davomiyligiga bog'liq, ilova bilmaydigan biznes-logika.

**Nima kerak:**
1. Billing `/abonent/info` ga `next_charge_date` (`Y-m-d`) qo'shsin.
2. `AbonentProfile::CANDIDATE_NEXT_CHARGE` ga nomini yozish.

> Hozir bu katakcha "Нет данных" ko'rsatadi — soxta sana chiqarilmaydi.

---

## 4. Joriy tarif narxi — qisman

**Talab (TZ §3.2, maket):** `Smart 50 — 125 000 сум`.

**Nega bajarilmadi:** `/abonent/info` faqat tarif **nomini** (`curr_tariff_name`)
qaytaradi, narxini emas. `/tariff/available` ro'yxatidan olib qo'yish ishonchsiz:
u "o'tish uchun mavjud" tariflar ro'yxati va joriy tarif unda bo'lmasligi mumkin.

**Nima kerak:** billing `curr_tariff_cost` (tiyinda) qo'shsin →
`AbonentProfile::CANDIDATE_CURRENT_TARIFF_COST`.

---

## 5. Программа лояльности — mazmuni bajarilmadi

**Talab (TZ §8):** blok ko'rsatsin:
- bonuslar soni;
- mijoz darajasi;
- mavjud imtiyozlar;
- "Подробнee" tugmasi.

**Nega bajarilmadi:** SOLA API'da sodiqlik dasturiga oid **hech qanday
endpoint yoki maydon yo'q**. Bonus, daraja, imtiyoz tushunchalari billingda
umuman mavjudligi noma'lum. Soxta raqam chiqarish (masalan "0 bonus") mijozni
chalg'itadi, shuning uchun ataylab qilinmadi.

**Bajarilgani:** dashboarddagi kirish kartochkasi ("Программа лояльности →
Перейти") tayyor va `SOLA_LOYALTY_URL` sozlangach ko'rinadi.

**Nima kerak:**
1. Qaror: sodiqlik dasturi qayerda yashaydi — billingdami, alohida servisdami?
2. Agar billingda: `/loyalty/info` kabi endpoint (bonus, daraja, imtiyozlar
   ro'yxati).
3. Agar tashqi servis: uning URL manzili → `.env` da `SOLA_LOYALTY_URL`
   (kartochka darhol ishlaydi, ichki sahifa esa alohida task).

---

## 6. Акции и скидки — mazmuni bajarilmadi

**Talab (TZ §9):** blok ko'rsatsin:
- amaldagi aksiyalar;
- maxsus takliflar;
- shaxsiy chegirmalar.

**Nega bajarilmadi:** "shaxsiy chegirma" abonentga bog'langan ma'lumot — u
billingdan kelishi kerak, API'da yo'q. "Amaldagi aksiyalar" esa marketing
kontenti — uni saqlaydigan joy (CMS yoki admin panel) loyihada yo'q, DB ham
yo'q.

**Bajarilgani:** kirish kartochkasi tayyor (`SOLA_PROMO_URL`).

**Nima kerak:**
1. Aksiyalar kontenti qayerda saqlanadi degan qaror (eng oson yo'l —
   `sola.uz` saytidagi mavjud sahifaga havola).
2. Shaxsiy chegirmalar uchun billing endpointi.

---

## 7. Онлайн-чат — faqat integratsiya nuqtasi tayyor

**Talab (TZ §10):** "Чат с поддержкой" tugmasi, bosilganda ichki chat yoki
tashqi servis (Jivo, LiveChat va h.k.) ochilsin.

**Nega to'liq bajarilmadi:** chat provayderi tanlanmagan va akkaunt yo'q.
Widget skripti provayder bergan kalit bilan ishlaydi — kalitsiz yozib qo'yish
mumkin emas. O'z chatimizni yozish esa alohida katta loyiha (real-time
transport, operator paneli, tarix saqlash — DB kerak).

**Bajarilgani:**
- Kartochka (`SOLA_CHAT_URL`).
- Widget skriptini yuklash mexanizmi: `.env` da `SOLA_CHAT_SCRIPT` ko'rsatilsa,
  skript har sahifada avtomatik ulanadi (`layouts/app.blade.php`).

**Nima kerak:** provayderni tanlash (Jivo tavsiya etiladi — O'zbekistonda keng
tarqalgan) va `.env` ga ikki qatorni yozish:
```
SOLA_CHAT_URL=https://...
SOLA_CHAT_SCRIPT=https://code.jivosite.com/widget/XXXXXXXX
```

---

## 8. История смены тарифов — bajarilmadi

**Talab (TZ §12):** billingdan olinadigan ma'lumotlar ro'yxatida
"история смены тарифов" bor.

**Nega bajarilmadi:** API'da tarif almashtirish tarixini qaytaradigan endpoint
yo'q. Mavjud endpointlar: `/identify`, `/verify`, `/abonent/info`,
`/device/list`, `/acct/payments`, `/traffic/detail`, `/tariff/available`,
`/tariff/connect`, `/device/new`, `/device/delete` — tarix yo'q.

**Nima kerak:** billingdan `/tariff/history` (sana, eski tarif, yangi tarif,
kim o'zgartirgan) endpointi. Keyin dashboardga "Тариф и устройства" blokining
ichiga yig'iladigan ro'yxat qo'shiladi (~2 soatlik ish).

---

## 9. Login + parol bilan avtorizatsiya — ataylab qilinmadi

**Talab (TZ §1.2):** "Авторизация без изменений. Авторизация осуществляется по:
логину; паролю."

**Nega bajarilmadi:** TZ o'zi bilan ziddiyatli. Hozirgi kabinetda login/parol
umuman yo'q — avtorizatsiya **telefon raqami + SMS kod** orqali ishlaydi
(`/identify` SMS yuboradi, `/verify` kodni tekshiradi). Billing API'da
login/parol tekshiradigan endpoint mavjud emas.

Siz bilan kelishilgan qaror: **"без изменений" so'zi asosiy** — avtorizatsiyaga
tegilmadi, telefon+SMS o'z holicha qoldi.

**Nima kerak (agar login/parol haqiqatan kerak bo'lsa):**
1. Billingda parol tekshiradigan endpoint.
2. Parolni tiklash oqimi (unutgan bo'lsa nima bo'ladi?).
3. Bu ~1 haftalik alohida task va xavfsizlik ko'rib chiqishini talab qiladi.

---

## Kichik og'ishlar (bajarildi, lekin eslatma bilan)

### A. Sana formati дд.мм.гггг — jadvalларda bajarildi, kalendar maydonida yo'q

Jadvallardagi va bloklardagi barcha sanalar `дд.мм.гггг` formatida (TZ §11).
Lekin "Период с … по …" maydonlari `<input type="date">` — brauzer ularni
**operatsion tizim tili** bo'yicha chizadi (masalan `08/01/2026`). Bunga CSS
yoki HTML orqali ta'sir qilib bo'lmaydi.

**Variantlar:** (a) shunday qoldirish — brauzer foydalanuvchi tiliga moslashadi;
(b) o'z kalendar komponentini yozish (~4 soat, +8 KB JS). Hozir (a) tanlandi.

### B. Statistika davri — API oy bo'yicha ishlaydi

TZ ixtiyoriy sana oralig'ini so'raydi, API esa faqat bitta oyni
(`detail_month=Y-m`) qabul qiladi. Yechim: oraliqqa tushgan har bir oy alohida
so'raladi, natijalar birlashtiriladi va kunlar bo'yicha qirqiladi —
foydalanuvchi uchun bu haqiqiy sana oralig'i.

**Cheklov:** har bir oy alohida HTTP so'rov (~250 ms), shuning uchun oraliq
**12 oy** bilan chegaralangan (`App\Support\Period::MAX_MONTHS`). Uzunroq
oraliq so'ralsa, UI buni ochiq aytadi ("Период сокращён до 12 месяцев"), jimgina
qirqib tashlamaydi.

**Yaxshilash:** billing `/traffic/detail` ga `date_from`/`date_to` qo'shsa,
12 ta so'rov 1 taga tushadi va cheklov olib tashlanadi.

### C. To'lov statuslari

TZ 4 ta status nomlaydi: оплачено / ожидает оплаты / ошибка / отменено.
API `payment_status` ni erkin matn sifatida qaytaradi. Matn bo'yicha moslash
qilindi (`BillingHistory::paymentTone`), tanilmagan qiymat neytral rangda
o'z matni bilan ko'rsatiladi — soxta "оплачено" yozilmaydi.

**Nima kerak:** billingdan mumkin bo'lgan statuslar ro'yxati. Shunda moslash
matn taxminiga emas, aniq kodga asoslanadi.

### E. Sahifalar tuzilishi

TZ §1.1 «максимально использовать единый Dashboard» deydi, lekin TZ'ning
birinchi maketida (wireframe) navigatsiyadan **alohida ekranlarga** strelka
chizilgan: «Данные» → bitta ekran, «Статистика» → boshqa ekran.

Shu sababli har bir bo'lim o'z sahifasida:
`/` (Данные) · `/statistics` · `/finance` · Speed Test (tashqi).

Eski 5 ta sahifa (`/tariffs`, `/services` alohida edi) 3 taga tushdi —
tariflar va qurilmalar endi «Данные» ichida. Eski URL'lar redirect qilinadi.

### D. Excel eksporti

TZ'da "(опционально)" deb belgilangan — bajarildi. CSV (UTF-8 BOM bilan, Excel
to'g'ri ochadi). Haqiqiy `.xlsx` kerak bo'lsa kutubxona qo'shish kerak
(+40 KB JS yoki server tomonida PhpSpreadsheet).

---

## Bajarilganlar

| TZ | Task | Holat |
|---|---|---|
| §1.1 | Adaptiv dizayn (Desktop/Tablet/Mobile) | ✅ |
| §1.1 | Firma ranglari saqlangan | ✅ |
| §1.1 | Sahifalar soni minimallashtirildi | ✅ 5 → 3 (izoh E) |
| §1.1 | Ma'lumotlar sahifani qayta yuklamasdan yangilanadi (AJAX) | ✅ |
| §1.2 | Avtorizatsiya o'zgarmadi | ✅ |
| §2 | Yuqori navigatsiya: Данные/Статистика/Финансы/Speed Test | ✅ |
| §2 | O'ng tomonda: ФИО, лицевой счёт, til, Call Center | ✅ (№ договора — 1-punkt) |
| §3.1 | Yuqori blok: balans, joriy tarif, qurilmalar soni | ✅ |
| §3.2 | Joriy/keyingi tarif jadvali | ✅ (ma'lumot — 2-punkt) |
| §3.2 | Mavjud tariflar ro'yxati | ✅ |
| §3.2 | "Сменить тариф" + tasdiqlash oynasi | ✅ |
| §4 | Qo'shimcha qurilma bloki + ulash tugmasi | ✅ |
| §5 | Statistika: davr tanlash + "Сформировать" | ✅ (izoh B) |
| §5 | Входящий / Исходящий трафик МБ | ✅ |
| §6 | To'lovlar jadvali: sana, tizim, summa, status | ✅ |
| §6 | Statuslar ranglar bilan | ✅ (izoh C) |
| §6 | Pagination | ✅ |
| §6 | Saralash | ✅ |
| §6 | Davr bo'yicha filtr | ✅ |
| §7 | Speed Test o'zgarmadi | ✅ |
| §11 | Sana formati дд.мм.гггг | ✅ (izoh A) |
| §11 | Pul formati `120 000 сум` | ✅ |
| §11 | Jadvallarda qidiruv | ✅ |
| §11 | Excel eksporti | ✅ (izoh D) |
| §11 | Mobil: kartochkalar vertikal | ✅ |
| §11 | Mobil: jadvallar kartochkaga aylanadi | ✅ |
| §11 | Mobil: tugmalar to'liq kenglikda | ✅ |
| §13 | Tarif almashtirish: tasdiqlash → billingga yuborish | ✅ |
| §13 | Qurilma ulash: billingga so'rov → sonni yangilash | ✅ |

---

## Xulosa: buyurtmachidan nima so'rash kerak

Bitta xat bilan billing jamoasidan so'ralsa, 1–4 va 8-punktlar yopiladi:

> `/abonent/info` javobiga quyidagi maydonlarni qo'shish iltimosi:
> - shartnoma raqami
> - keyingi tarif nomi va narxi (tiyinda)
> - joriy tarif narxi (tiyinda)
> - keyingi hisobdan yechish sanasi (`Y-m-d`)
>
> Qo'shimcha: `/tariff/history` endpointi va `/traffic/detail`,
> `/acct/payments` uchun `date_from`/`date_to` parametrlari.
>
> Shuningdek: `payment_status` maydonining mumkin bo'lgan qiymatlari ro'yxati.

5, 6, 7-punktlar buyurtmachining biznes qarorini talab qiladi (sodiqlik dasturi
qayerda yashaydi, aksiyalar kim tomonidan boshqariladi, chat provayderi qaysi).
