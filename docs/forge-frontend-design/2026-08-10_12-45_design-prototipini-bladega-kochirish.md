# design/ prototipini Blade view'lariga ko'chirish

- **Sana:** 2026-08-10 12:45
- **Rejim:** SaaS product + Multi-market (uz/ru/en)
- **Shartnoma:** `design/HANDOFF.md`
- **RISK:** true — §11 bo'yicha pulga tegadigan uchta marshrut GET dan POST ga o'tdi

## Nima qilindi

**Palitra bir zarbada almashdi.** `resources/css/app.css` dagi neytral kulrang
"shell" oilasi (`shell-*`, `leaf-*`, `canvas`, `clay`, `amber-brand`,
`line-dark`) butunlay olib tashlandi va o'rniga `tokens.js` dagi logotip
ohangidagi palitra keldi. Saqlab qolinganlar: `.is-busy` (ajax.js),
`.u-sortable` + `[aria-sort]` (table.js), chop etish bloki.
`.u-scrollbar-thin` → `.u-scroll` (HANDOFF §2 bo'yicha bittasi tanlandi).

**Karkas.** `layouts/app` — yopishqoq yorug' sarlavha, `max-w-[1240px]`;
`partials/topbar` — bitta `$items` massividan ham tepa qator, ham drawer
chiqadi; `layouts/guest` — markazlashgan ustun. Eski `on-shell` qorong'i
ramka yo'q.

**Bitta uzun ekran to'rtta sahifaga bo'lindi.** Bosh sahifa endi bitta
savolga javob beradi — "balansim yetadimi?" — va qolgan uchtasini havola
qiladi. `/tariffs`, `/devices`, `/services` — yangi marshrut va view.

**Imzo elementi ishlaydi.** Kun o'lchagichi serverda `App\Support\ChargeCycle`
da hisoblanadi va Bladeda `@for` sikli bilan chiziladi — JS da emas, chunki u
sahifadagi birinchi narsa va birinchi bo'yashdayoq joyida bo'lishi kerak.
Billing yechim sanasini bermasa o'lchagich umuman chizilmaydi.

## Qabul qilingan qarorlar

- **Manrope o'zida saqlanadi** (`@fontsource-variable/manrope`), CDN havolasi
  ko'chirilmadi. Kirill + `U+02BB` (oʻ, gʻ) qamrovi tekshirildi.
- **Mehmon ekranlarida til almashtirgich qoldirildi**, prototipda yo'q
  bo'lsa ham: ruszabon abonent tilni kirishdan *oldin* almashtira olishi kerak.
- **To'lov tizimlari oddiy matn** (Uzum/Payme/Click). Chizilgan yoki taxminiy
  belgi fishing sahifa taassurotini beradi — rasmiy press-kit SVG si kerak.
- **`view-settings` yorlig'i 2xl dan yashiriladi** (sr-only bo'lib qoladi).
  Ruscha "Отображение" tepa qatorni siqib, abonentning ismini "Alis…" ga
  qirqar edi — matn kengayishi muammosi, prototip faqat o'zbekchada sinalgan.
- **`signal` va `month-picker` komponentlari o'chirildi** — yangi dizaynda
  ishlatilmaydi.

## Tekshirildi

- Brauzerda: 6 ta kabinet sahifasi + prototipning o'zi yonma-yon. Maket,
  yoy, o'lchagich, oraliqlar mos.
- Uchala til kaliti to'liq (skript bilan tasdiqlandi).
- `npm run build` toza; `php artisan view:cache` toza.

## Ochiq qolgan savollar (mijozdan)

1. **Logotipning SVG versiyasi** — hozirgi PNG 166×53, ~128px da ko'rsatiladi,
   ya'ni 2× ekranda xira. Yorug' yer uchun qora yozuvli variant ham kerak —
   u bo'lsa `.u-logo` ning qora yuzasini olib tashlash mumkin.
2. **Qo'llab-quvvatlash ish vaqti** — `services.call_hours` da prototipdagi
   "08:00–22:00" yozilgan. Bu tasdiqlanmagan, lang faylidan o'zgartiriladi.
3. **`/auth/logout` hali ham GET** — §11 uni sanamagan, shuning uchun
   tegilmadi, lekin u ham holatni o'zgartiradi.

## Yakuniy darvoza natijasi

Ikkalasi parallel ishladi (RISK=true). Ikkalasi ham "avval tuzat" dedi;
quyidagilar tuzatildi va qayta sinaldi.

**Reviewer — bloker:**

1. `tariff.js` da `dataset.tariffConfirmed` bir marta qo'yilib hech qachon
   tozalanmasdi — ya'ni bu bir martalik token emas, tasdiqlash oynasini
   **butunlay chetlab o'tish** edi va bfcache dan qaytganda ham saqlanardi.
   Endi oddiy `let confirmed` va u submit o'tayotganda tozalanadi.
2. Pul sarflaydigan POST larda yuborish qulfi yo'q edi → `submitting` bayrog'i
   va tugmani o'chirish (ajax.js ning o'z konvensiyasi).

**Reviewer — ogohlantirish:**

3. `ChargeCycle::endingAt()` da `subMonth()` qisqa oyga **toshib ketardi**:
   31-mart minus bir oy = 3-mart, 28-fevral emas. 29–31-sanada hisobdan
   yechiladigan **har bir abonent** noto'g'ri chiziqli o'lchagich va noto'g'ri
   davr boshi ko'rardi. `subMonthNoOverflow()` ga o'tkazildi; regressiya testi
   qizil→yashil isbotlandi (`2026-03-03` → `2026-02-28`).
4. `CabinetController` `devices()` ni chaqirib, natijasini ishlatmasdi — eng
   ko'p ochiladigan sahifada behuda billing so'rovi. Olib tashlandi.
8. `hasPassed()` yechim kunining o'zida ham rost edi, ya'ni pul yechiladigan
   kuni ertalab "muddat o'tdi" deb yozardi. `isOverdue()` + `isChargeDay()`
   ga bo'lindi, `dash.charge_today` kaliti qo'shildi.

**Auditor — High:**

1. **"Faqat doimiy abonent" qoidasi faqat view'da edi.** `isPermanent()`
   mavjud, lekin hech qayerda chaqirilmagan. Vaqtinchalik abonent oddiy
   kirib, istalgan sahifadan CSRF tokenini olib `/devices/add` ga POST
   qilsa — ~15 000 so'mlik ruxsat. Endi `DeviceController::store/destroy`
   va `TariffController::connect` da `abort_unless`. Bu men kiritgan nuqson
   emas edi, lekin men POST ga o'tkazgan pul yo'lida turardi.
2. **`tariff` "har qanday butun son" edi** — abonentga taklif qilingan
   ro'yxatga solishtirilmasdi. Endi `availableTariffs($accountId)` ga a'zolik
   tekshiriladi. Doimiy bo'lmagan abonent uchun `timing` serverda `now` ga
   normallashtiriladi.

## 390px tekshiruvi (darvozadan keyin)

Brauzer oynasi kichrayishdan bosh tortgani uchun 390px lik iframe stendi
qurildi (`design/mobile-check.html` ning shu ilova uchun varianti). Ikkita
haqiqiy nuqson chiqdi:

- **`td:empty` qoidasi hech qachon ishlamasdi.** Karta rejimida har bir katak
  sarlavhasini `data-label` dan chop etadi, va bo'sh "Amallar" katagini
  yashiradigan qoida `:empty` ga tayanadi — u esa Blade `@if` i qoldirgan
  yangi qatorni ham bola deb sanaydi. Telefonda ostida hech narsasi yo'q
  "Действия" yorlig'i qolib ketardi — HANDOFF §8 aynan shundan ogohlantirgan.
  Katak endi `@if`/`@else` bilan chinakam bo'sh chiqariladi.
- **Drawer da uchta fakt ikki marta** ko'rinardi: yuqoridagi `<dl>` va uning
  ostidagi hisob bloki bir xil ism va hisob raqamni takrorlardi. Hisob
  bloki endi faqat *boshqa* hisoblarni ko'rsatadi.

## Tuzatilmagan, mijozga/keyingi ishga qoldirildi

- **Auditor Medium 4** — pul harakatlari uchun audit jurnali yo'q
  (`SolaClient` ga `Log::info`). Bu men tegmagan fayl va PII siyosati bilan
  bog'liq arxitektura qarori.
- **Auditor Medium 5, Low 6** — `GET /select/account/{id}` va `GET /auth/logout`
  ham holatni o'zgartiradi. HANDOFF §11 uchtasini sanagan, bular unda yo'q.
  Egalik tekshiriladi (403), ya'ni IDOR emas.
- **Auditor Low 7, 8** — pul POST larida `throttle` yo'q;
  `SESSION_SECURE_COOKIE` `.env.example` da yo'q.
- **Auditor Medium 3 ning yarmi** — `confirm.js` da `pending = null`
  allaqachon ikkinchi bosishni to'xtatardi; auditor buni bo'rttirgan.
  Baribir tugmani o'chirish qo'shildi.
