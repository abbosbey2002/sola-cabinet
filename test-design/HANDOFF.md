# Prototipdan Bladega — ko'chirish shartnomasi

`design/` — dizayn prototipi. Har bir ekran **alohida fayl**, ya'ni Bladedagi
alohida marshrut va view. Bu hujjat uni haqiqiy ilovaga o'tkazish tartibini
belgilaydi.

Prototipni ko'rish uchun (nginx `public/` dan tashqarini bermaydi):

```bash
php -S 127.0.0.1:8090 -t design
# http://127.0.0.1:8090/index.html
# http://127.0.0.1:8090/mobile-check.html   ← 390px, ikkala mavzu yonma-yon,
#                                             sahifa va matn hajmi tanlanadi
```

---

## 0. Bu versiyada nima o'zgardi

Oldingi prototip oltala ekranni bitta faylda saqlar va firuza ("Majolica")
palitrada edi. Uchta talab uni butunlay qayta qurdi:

**Brend.** Logotip endi haqiqiy fayl — `public/img/` dan olingan, chizilgan
emas (§3). Asosiy rang `#087D20`, logotip yashili `#8FD400` esa grafik rang
sifatida qoldi (§4). Firuza ("Majolica") butunlay chiqarildi.

**Yoshi katta va turli sharoitdagi foydalanuvchi.** Ildiz o'lchami 112.5%
(≈18px), foydalanuvchi uchun uch bosqichli "Matn hajmi" boshqaruvi, eng kichik
matn 0.9375rem, uppercase mayda yorliqlar butunlay olib tashlandi, bosiladigan
joylar ≥48px, holat rang + ikonka + so'z bilan beriladi.

**Sahifa boshiga kamroq komponent.** Bitta uzun "Ma'lumotlar" ekrani to'rtta
sahifaga bo'lindi: bosh sahifa faqat balansni va uchta havolani ko'rsatadi,
tarif/qurilmalar/to'lovlar esa o'z sahifasida.

## 1. Fayllar va ular nimaga aylanadi

| Prototip fayli | Blade view | Marshrut |
|---|---|---|
| `login.html` | `auth/login.blade.php` | mavjud |
| `verify.html` | `auth/verify.blade.php` | mavjud |
| `accounts.html` | `auth/select_account.blade.php` | mavjud |
| `index.html` | `cabinet/index.blade.php` + `summary` | mavjud |
| `tariff.html` | `cabinet/tariff.blade.php` | **yangi** |
| `devices.html` | qurilmalar view | **yangi** |
| `statistics.html` | `trafic/index` + `trafic/result` | mavjud |
| `payments.html` | `payment/index` + `payment/result` | mavjud |
| `services.html` | `cabinet/entry-points.blade.php` | **yangi** |

Karkas (topbar, drawer, footer) har bir faylda takrorlangan — bu prototip har
bir sahifa mustaqil ochilishi uchun. Bladeda u **bir marta**
`layouts/app.blade.php` + `partials/topbar.blade.php` ga ko'chadi; mehmon
ekranlari — `layouts/guest.blade.php`.

Nav ro'yxati topbarda va drawerda bir xil ketma-ketlikda takrorlangan —
Bladeda bitta `$items` massivi bo'lsin, aks holda ular vaqt o'tib bir-biridan
uzoqlashadi.

`design/assets/` — umumiy fayllar:

- **`tokens.js`** — bu aslida CSS. `.js` kengaytmasida turishining yagona
  sababi: `@tailwindcss/browser` (prototip uchun CDN quruvchi) tashqi `.css`
  faylni o'qimaydi, u faqat sahifa ichidagi `<style type="text/tailwindcss">`
  blokini ko'radi. **Ikkita teskari apostrof orasidagi hamma narsa sof CSS** —
  uni to'g'ridan-to'g'ri `resources/css/app.css` ga ko'chiring (§2 dagi
  shartlar bilan).
- **`icons.js`** — SVG sprite. Bladeda har bir `<symbol>` `<x-icon>` ning bir
  "case" i bo'ladi.
- **`app.js`** — ko'rinish paneli, drawer, kun o'lchagichi va (faqat prototip
  uchun) balans holatlari.
- **`logo-wordmark.png`, `logo-bird.png`, `favicon.png`** — haqiqiy brend
  fayllari, §3 ga qarang.

## 2. CSS bloki — bu DIFF, fayl almashtirish emas

`tokens.js` ichidagi `@theme inline` va `@layer components` bloklari
`app.css` ga ko'chiriladi. Lekin prototipda faqat o'zi ishlatadigan sinflar
bor — mavjud `app.css` da qolgan hammasi **saqlanishi kerak**, aks holda
ilovaning bir qismi jimgina sinadi.

Prototipda yo'q, lekin ishlatilayotgan (o'chirilmasin):

| Sinf / token | Qayerda ishlatilyapti | Nega muhim |
|---|---|---|
| `.is-busy` | `resources/js/modules/ajax.js` | AJAX blok yangilanayotgani ko'rinmay qoladi |
| `.u-sortable` + `[aria-sort]` | jadval sarlavhalari | Saralash ko'rsatkichi yo'qoladi |
| `.u-label-on-pine`, `.u-btn-leaf`, `.u-btn`, `.on-pine :focus-visible` | topbar, drawer, modal | Eski qorong'i yuza sinflari |
| `pine-*`, `leaf-*`, `canvas`, `amber-brand`, `line-dark` | ~68 marta | Eski palitra |

Ikki yo'l bor:

- **Bosqichma-bosqich (tavsiya):** yangi tokenlarni eski palitra yoniga
  qo'shing, keyin view'larni bittalab ko'chiring, oxirida eski tokenlarni
  o'chiring. Har bosqichda ilova ishlab turadi.
- **Bir zarbada:** CSS va barcha view'lar bitta commitda. Tezroq, lekin
  oraliq holatda ilova buziladi.

`.u-scroll` — bu `app.css` dagi `.u-scrollbar-thin` ning yangi nomi.
Ikkalasini qoldirmang, bittasini tanlang. `.u-no-print` va `@media print`
prototipda bor va **ko'chiriladi** — to'lov tarixi chop etiladigan yagona
narsa.

## 3. Logotip — haqiqiy fayl, chizilgan emas

Prototip `public/img/` dagi fayllarni ishlatadi, ular `design/assets/` ga
nusxalangan:

| Fayl | Manba | Qayerda |
|---|---|---|
| `logo-wordmark.png` | `public/img/logo-wordmark.png` | ≥640px ekranda |
| `logo-bird.png` | `logo-mark.png` ning yuqori qismi (0–111 qator) | <640px ekranda |
| `favicon.png` | `public/img/favicon.png` | `<link rel="icon">` |

`logo-bird.png` — **kesilgan, chizilgan emas**. `logo-mark.png` da qush va
matn orasida toza bo'shliq bor (112–123 qatorlar butunlay shaffof), qush aynan
shu chiziqdan ajratilgan. Bladeda `{{ asset('img/logo-wordmark.png') }}`
bo'ladi; qush kesimini `public/img/logo-bird.png` sifatida saqlab qo'yish
kerak.

**Nega logotip qora yuzada turadi.** Ikkala fayl ham qorong'i yer uchun
chizilgan: "SOLA" yozuvi va qushning tanasi OQ. Yorug' mavzuda ular oq
kartada shunchaki yo'qoladi. Shuning uchun `.u-logo` logotipga o'z yerini
beradi — kun o'lchagichi bilan bir xil qora yuza (`--c-meter-bg`). Qorong'i
mavzuda bu yuza fon bilan deyarli qo'shilib ketadi, ya'ni bitta qoida ikkala
mavzuda ham to'g'ri ishlaydi.

**Ikkita narsa mijozdan so'ralsin:**

1. **SVG versiya.** Hozirgi PNG 164×52 — sahifada ~128px kenglikda
   ko'rsatiladi, ya'ni Retina/2× ekranda u biroz xira chiqadi. SVG (yoki
   hech bo'lmasa 2×/3× PNG) kerak.
2. **Yorug' yer uchun variant** — yozuvi qora bo'lgan logotip. U bo'lsa yorug'
   mavzuda qora yuzani olib tashlash mumkin: `.u-logo { background: none }`.

## 4. Ranglar

Ikkita yashil ikkita ishni bajaradi va ular aralashtirilmaydi:

**`--c-primary` `#087D20` — amal rangi.** Asosiy tugma, havolalar, ikonkalar,
faol menyu, fokus halqasi, radio tugmalar. Asosiy tugma ikkala mavzuda ham
aynan bir xil: to'q yashil fon, oq yozuv.

**`--c-lime` `#8FD400` — logotip rangi, GRAFIK.** Oq ustida 1.81:1, ya'ni hech
qachon matn ko'tarmaydi va tugma ham bo'lmaydi. U faqat ikki joyda: kun
o'lchagichining o'tgan kunlarida va logotipning o'zida — ikkalasida ham qora
yuzada (9.89:1). Logotipdagi munosabat aynan shu: qorong'ida yashil.

| Nima | Yorug' | Qorong'i | Kontrast |
|---|---|---|---|
| Tugma foni / yozuvi | `#087D20` / oq | bir xil | 5.30:1 |
| Tugma gardishi | `#066418` | `#3AAE4E` | 7.38:1 / 6.52:1 |
| Havola, ikonka | `#087D20` | `#45C25E` | 5.30:1 / 7.16:1 |
| Yumshoq fon | `#E8F5E9` | `#14301B` | ustida havola 4.71:1 / 6.22:1 |
| Asosiy matn | `#12190F` | `#E9EFE4` | 17.9:1 / 14.1:1 |
| Ikkilamchi matn | `#4B5645` | `#A5B09C` | 7.7:1 / 7.3:1 — AAA |
| Kun o'lchagichi | `#8FD400` qora yuzada | bir xil | 9.89:1 |

Qorong'i mavzuda havola rangi yoritiladi, chunki `#087D20` qora yuzada
3.5:1 dan oshmaydi — bu tugma foni uchun yetarli, matn uchun emas.

Yangi rang qo'shsangiz uni `--c-*` sifatida `:root` ga, keyin `@theme inline`
da `--color-*` ga bog'lang, va ikkala mavzuda kontrastini o'lchang.

## 5. `--c-*` va `--color-*` farqi

`@theme inline` tufayli Tailwind `--color-*` o'zgaruvchilarini **chiqarmaydi**
— utilita sinflari qiymatni to'g'ridan-to'g'ri `var(--c-*)` ga bog'laydi.

Ya'ni `style=""`, `stroke=""`, `fill=""` va `[...]` ichida **faqat `--c-*`**
ishlaydi:

```blade
{{-- to'g'ri --}}   stroke="var(--c-action)"    accent-[var(--c-action)]
{{-- sinadi --}}    stroke="var(--color-action)" accent-[var(--color-leaf-600)]
```

Hozir ikkita fayl `var(--color-*)` ga tayanadi — ko'chirishda tuzatilsin:
`resources/views/components/arc.blade.php`, `resources/views/cabinet/tariff.blade.php`.

## 6. Shriftlar — busiz dizayn yo'q

Prototip Google Fonts CDN dan oladi. Ilova shriftlarni o'zida saqlaydi. CDN
havolasi ko'chirilmaydi, o'rniga:

```bash
npm i @fontsource-variable/manrope        # Inter allaqachon bor
```

```css
/* resources/css/app.css — Inter yonida */
@import '@fontsource-variable/manrope';
```

**Inter** — butun interfeys matni. U allaqachon `app.css` da ulangan va
`cyrillic` qamroviga ega, ya'ni kabinetning ruscha versiyasi ham ishlaydi.
`font-feature-settings: 'ss02'` yoqilgan — Inter ning "farqlash" to'plami,
`1`, `l` va `I` bir-biriga o'xshamay qoladi. Ko'zi xiralashgan o'quvchi uchun
bu shartnoma raqamini o'qishning yarmi.

**Manrope** — faqat `.u-display` va `.u-figure` da: sarlavhalar, logotip
so'zi, balans raqami. U yuklanmasa Inter o'rnini bosadi, x-balandligi yaqin,
ya'ni maket sezilarli siljimaydi.

Manrope `cyrillic` + `latin-ext` bilan keladi — tekshirilgan.

## 7. Ko'rinish paneli — mavzu va matn hajmi

Har bir sahifaning `<head>` idagi kichik blokirovkalovchi skript **majburiy**
— busiz tizimi qorong'i foydalanuvchi avval oq sahifani ko'radi, matn hajmi
esa sahifa yuklangach sakrab kattalashadi.

Ikkala sozlama ham uch holatli va bir xil naqsh bo'yicha ishlaydi:
`data-theme="light" | "dark" | atribut yo'q (tizim)`, va
`data-text="lg" | "xl" | atribut yo'q (oddiy)`. `<html>` ga server tomonidan
hech qanday `data-theme`/`data-text` qo'yilmasin.

Matn hajmi **foizda** beriladi (`112.5% / 125% / 140%`), px da emas — shu
tariqa brauzerda shriftni kattalashtirib qo'ygan foydalanuvchining sozlamasi
saqlanadi va biznikiga ko'paytiriladi. Butun maket `rem` da o'lchangani uchun
oraliqlar ham matn bilan birga o'sadi.

Prototip tanlovni `localStorage` da saqlaydi. Ilovada til allaqachon `lang`
cookie'sida (`AbonentSession::setLocale`) — mavzu va matn hajmini ham
cookie'ga qo'ysangiz, server birinchi renderdayoq to'g'ri holatni bera oladi
va skript ham kerak bo'lmaydi. Qaysi biri — o'zingiz hal qiling, lekin
ikkalasini aralashtirmang.

Panel markupi `app.js` ichida bir marta yozilgan (`VIEW_PANEL`) va
`[data-view-mount]` ga joylanadi — Bladeda bu `<x-view-settings>` komponenti.

## 8. Jadvallar — @media emas, @container

`.u-table-wrap` `container-type: inline-size` o'rnatadi, `.u-table-cards` esa
`@container (max-width: 40rem)` da kartaga aylanadi. Ikki sabab bor:

1. qurilmalar jadvali 1920px ekranda ham tor ustunda turishi mumkin;
2. foydalanuvchi matnni 140% ga kattalashtirsa jadval kengayadi, ekran esa
   o'zgarmaydi — chegara `rem` da bo'lgani uchun `@container` buni o'zi
   hisobga oladi, `@media` hech qachon hisobga olmaydi.

Yangi jadval qo'shsangiz uni ham `.u-table-wrap` ichiga oling.

Kartaga aylanganda har bir katak sarlavhasini `data-label` dan chop etadi —
Bladeda bu atribut `@lang` bilan to'ldirilsin, aks holda ruscha versiyada
telefonda sarlavhalar inglizcha qolib ketadi.

## 9. Dizayn qilinmagan holatlar

Bular ilovada bor, prototipda yo'q — konvertatsiyada o'chirib yubormang,
dizaynini so'rang:

- `<x-table-nav>` — qidiruv, sahifalash, Excelga yuklash. Ikkala jadval ham
  production'da sahifalanadi.
- `.u-sortable` — saralanadigan ustun sarlavhasi.
- Toast xabarlari (`data-toast`), tasdiqlash modali (`x-modal`).
- Davr qisqartirilgani / ba'zi oylar olinmagani haqidagi ogohlantirishlar
  (`app.dash.clamped`, `app.dash.incomplete`).
- Forma xatolari: matn bilan, `aria-describedby` orqali bog'langan, submitda
  fokus birinchi xatoga ko'chadi.

To'lovlar jadvalidagi "Uzum / Payme / Click" — hozir oddiy matn. Agar ular
brend belgisi sifatida ko'rsatilsa, rasmiy press-kit dan olingan haqiqiy SVG
kerak bo'ladi; qo'lda chizilgani yoki taxminiy ikonka fishing sahifa taassurot
qoldiradi.

## 10. Ma'lumot Bladega tushganda

`setState()` matnni `textContent` orqali qo'yadi, `innerHTML` orqali emas —
shu joyga abonentning ismi va summasi tushadi. Bladeda ham `{{ }}` ishlatilsin,
`{!! !!}` emas. Hozir `{!! !!}` faqat bitta joyda o'rinli: `app.footer.copy`
(ichida `<br>` bor).

Prototipda **bitta ham `onclick=`/`onsubmit=` yo'q** — bu ataylab. `{{ }}` JS
satr kontekstida himoya qilmaydi: `e()` `'` ni `&#039;` ga aylantiradi, HTML
parseri esa uni JS parseriga yetkazguncha yana `'` ga qaytaradi. Abonent ismi
billingdan keladi, ya'ni `onclick="pick('{{ $account['abonName'] }}')"` — bu
saqlangan XSS. Qiymatlar `data-*` orqali uzatilsin, modul `el.dataset.x` dan
o'qisin (ilovada `resources/js/modules/` shunday ishlaydi).

Kun o'lchagichi (`buildMeter`) `total`/`used`/`charge` ni raqam sifatida
oladi — serverda hisoblanib, `data-*` orqali berilsin va `total` 1–31 ga
qisilsin.

## 11. Amal tugmalari — GET emas, POST

Prototipdagi to'rtta tugma (tarifni o'zgartirish, qurilma qo'shish, ikkita
o'chirish) `<form method="post">` ichida turadi. Bu ataylab, chunki **hozirgi
marshrutlar GET**:

```php
// routes/web.php:37,44,45 — hammasi holatni o'zgartiradi, ikkitasi pul turadi
Route::get('/connect/{id}/{type}', [TariffController::class, 'connect'])
Route::get('/add', [DeviceController::class, 'store'])->name('devices.add');
Route::get('/delete/{permitId}', [DeviceController::class, 'destroy'])->name('devices.delete');
```

GET so'rovda CSRF tokeni yo'q. Abonent kirgan holda begona sahifaga o'tsa,
o'sha sahifa `cabinet.sola.uz/devices/add` ga yo'naltirishi kifoya — cookie
o'zi bilan ketadi va abonentga 15 000 so'm yoziladi.

Konvertatsiyada:

```php
Route::post('/connect/{id}/{type}', ...)
Route::post('/add', ...)->name('devices.add');
Route::post('/delete/{permitId}', ...)->name('devices.delete');
```

```blade
<form method="post" action="{{ route('devices.delete', $device['permit_id']) }}">
    @csrf
    <button type="submit" class="u-btn-danger u-btn-sm"
            data-confirm="{{ __('app.header.are_you_cancel') }}">…</button>
</form>
```

`resources/js/modules/confirm.js` allaqachon `target.form?.submit()` ni
qo'llab-quvvatlaydi, ya'ni tasdiqlash modali shundoq ishlayveradi.

Shu bilan birga `bootstrap/app.php` ga `frame-ancestors 'none'` sarlavhasini
qo'shish arziydi — hozir ilovada hech qanday framing himoyasi yo'q.

## 12. `<head>` dan yo'qotib qo'ymaslik kerak bo'lganlar

Prototipning `<head>` i faqat shrift va stil qatorlarini almashtiradi. Mavjud
`layouts/app.blade.php` dan bular **qoladi**:

- `<meta name="csrf-token" content="{{ csrf_token() }}">` — `ajax.js` shuni
  o'qiydi; busiz davr formalari 419 qaytaradi
- `<html>` dagi `data-close-label` / `data-error-label`
- `<meta name="theme-color">` — `app.js` uni mavzuga qarab yangilaydi

Mavzu skripti inline bo'lgani uchun CSP qo'shsangiz unga `nonce` yoki hash
kerak bo'ladi.

## 13. Ko'chirilmaydigan narsalar

- `index.html` pastidagi punktir ramkadagi "Prototip — balans holati" bloki
- `cdn.jsdelivr.net` va `fonts.googleapis.com` havolalari
- `design/mobile-check.html` — tekshiruv asbobi
- Namunaviy ma'lumotlar (Alisher Karimov, D-100145, MAC manzillar) — barchasi
  o'ylab topilgan, real abonent emas

## 14. Marshrutlar va gating

Har bir sahifa o'z marshrutida bo'lsin, gating esa `abonent.verified`
middleware'ida — aks holda tasdiqlanmagan tashrifchiga abonent ma'lumotlari
javob tanasida yuboriladi.

Prototipdagi havolalar `*.html` ga ishora qiladi; Bladeda ular `route()` ga
almashadi. Drawer hook nomlari ataylab `resources/js/modules/nav.js` bilan bir
xil (`data-nav-drawer`, `data-nav-scrim`, `data-nav-close`, `id="nav-drawer"`)
— markupni ko'chirsangiz, `app.js` dagi drawer bloki keraksiz bo'ladi.

## 15. Ochiq savol

Ikkinchi til (ruscha) markupda emas, `lang/` fayllarida turadi. Prototip
faqat o'zbekcha yozilgan, lekin maket eng uzun satrga moslab tekshirilgan:
`u-btn-*` va `u-pill-*` da qat'iy kenglik yo'q, `whitespace-nowrap` ataylab
olib tashlangan, `overflow-wrap: break-word` esa "Qo'llab-quvvatlash" kabi
uzun so'zlarni 320px ekranda ham konteyner ichida ushlab turadi.

Ruscha satrlar odatda 15–40% uzunroq — birinchi ruscha renderdan keyin
`services.html` va `payments.html` ni 320px da qayta ko'ring.
