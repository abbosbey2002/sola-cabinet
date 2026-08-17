# UI/UX → Tailwind ko'chirish

**Sana:** 2026-08-07 · **Branch:** `laravel-13-upgrade`

## Qamrov

Butun abonent kabinetining frontendi 2019-yilgi Bootstrap 4 + jQuery shablonidan
Tailwind CSS 4 + Vite ga ko'chirildi. Foydalanuvchi tanlagan uchta yo'nalish:

1. **Build:** Vite + npm (standart Laravel yo'li)
2. **Struktura:** `desktop/` va `mobile/` shablon to'plamlari **birlashtirildi**
3. **Dizayn:** logotip ranglariga moslash + berilgan fintech dashboard
   reference'lari (deep pine ground, floating rounded panel, amber ikkilamchi)

## Dizayn rejasi

### Palitra

Yashillar Sola kolibri logotipidan piksel darajasida olingan
(`main-logo.png`: `#88D000` tana, `#C8F878` yorug'lik). Qolgan to'q ranglar —
o'sha ohangning yorug'ligi tushirilgani, shuning uchun fon "chuqur pine" bo'lib
o'qiladi, "umumiy dark mode kulrangi" emas.

| Token | Qiymat | Roli |
|---|---|---|
| `--color-pine-950` | `#0B2019` | Sahifa zamini (panel ortida) |
| `--color-pine-900` | `#103029` | Topbar, to'q kartochkalar, drawer |
| `--color-canvas` | `#F1F5EE` | Suzuvchi panel foni |
| `--color-surface` | `#FFFFFF` | Kartochkalar |
| `--color-leaf-500` | `#88D000` | **Brend** — grafik to'ldirishlar, aktiv nav |
| `--color-leaf-300` | `#C8F878` | Yorug'lik, pine ustidagi matn |
| `--color-leaf-700` | `#3F6E0C` | Yorug' fonda **o'qiladigan** yashil |
| `--color-amber-brand` | `#F5C243` | Ikkilamchi ma'lumot qatori (yuborilgan trafik) |
| `--color-clay` | `#D9503F` | Manfiy balans, o'chirish |

> **Muhim qoida:** `leaf-500` ning canvas ustidagi kontrasti ~1.9:1 — u faqat
> grafik rang. Yorug' fonda matn hech qachon `leaf-500` bo'lmaydi, `leaf-700`.

### Tipografika

**Inter Variable** (`@fontsource-variable/inter`, o'z serverimizdan, CDN yo'q).
Kirill subset majburiy — kabinet rus tilida ham ishlaydi.

- Display / raqamlar: 700, tracking `-0.03em`, `tabular-nums`
- Body / UI: 400–500
- Micro-label (`u-label`): 11px, uppercase, tracking `+0.09em`

`body` da global `font-variant-numeric: tabular-nums` — kabinetning har bir
ekrani raqam ustuni, tabular figuralar ularni ustundan ustunga sakrashdan
saqlaydi.

*Rad etilgan:* mavjud Museo Sans Cyrl. Faqat bitta og'irlik (500) bor —
ierarxiya qurish imkonsiz.

### Layout konsepsiyasi

Reference'dagidek: to'q pine zamin ustida butun ilova bitta
`rounded-[2rem]` canvas panel bo'lib "suzadi". Telefonda panel full-bleed
bo'ladi (gorizontal joy qimmat), zamin faqat status bar ortida qoladi.

Navigatsiya — canvas ustidagi to'q pine **pill bar**; aktiv element yagona
to'liq brend-yashil element.

### Signature element

Logotipdagi `)))` konsentrik yoylar motifi. Uch joyda takrorlanadi:

1. **Arc gauge** (`<x-arc>`) — trafik in/out ulushi. 240° yoy, `pathLength="360"`
   bilan normallashtirilgan (1 birlik = 1 gradus), shuning uchun shablonda
   trigonometriya yo'q. Ichida nuqtali halqa.
2. **Aktiv nav belgisi** (`<x-signal>`) — pill ichidagi glif.
3. **Watermark** — balans va to'lovlar hero kartochkalarida.

### Harakat

Bitta uyushtirilgan lahza: sahifa yuklanganda kartochkalar 70 ms stagger bilan
ko'tariladi (`u-rise`), gauge yoyi o'zini chizadi (`u-draw`,
`stroke-dashoffset`). Boshqa hech narsa o'z-o'zidan harakatlanmaydi.
Hammasi `prefers-reduced-motion` ortida.

## O'zgargan narsalar

### Qo'shildi

- `package.json`, `vite.config.js` — Vite 7 + Tailwind 4 + `@tailwindcss/vite`
- `resources/css/app.css` — `@theme` tokenlari, `u-*` komponent klasslari
- `resources/js/` — jQuery/Bootstrap JS o'rniga 6 ta vanilla modul
  (`disclosure`, `modal`, `confirm`, `nav`, `paginate`, `tariff`, `toast`)
- `resources/views/components/` — `arc`, `signal`, `icon`, `modal`, `empty`,
  `month-picker`, `lang-switch`, `account-menu`, `account-type`
- `public/img/` — logotip assetlari

### O'chirildi

- `resources/views/desktop/` va `resources/views/mobile/` — 26 ta ikkilangan
  shablon o'rniga 8 ta sahifa + 2 layout + 1 partial + 9 komponent
- `app/Support/DeviceView.php` — User-Agent bo'yicha shablon tanlash
- `mobiledetect/mobiledetectlib` (composer.json + lock + vendor)
- `public/vendor/` (9.7 MB): Bootstrap, jQuery, Swiper, Chart.js, DataTables,
  toastr, touch-sideswipe, 2019-yilgi HTML shablon nusxalari
- `public/css/`, `public/js/` (eski Laravel stub'lari)

### PHP tomonida

- `Controller` endi `DeviceView` o'rniga `Illuminate\Contracts\View\Factory`
  oladi. Chaqiruv imzosi bir xil (`$this->view->make(...)`), shuning uchun
  controller'lar deyarli tegilmadi.
- `TrafficController` va `PaymentController` ikkala kirish nuqtasi uchun bitta
  view render qiladi (`trafic.detail`, `payment.history`) — `index`/`month`
  shablonlari bir-birining aynan nusxasi edi.
- `AppServiceProvider` dan `MobileDetect` binding'i olib tashlandi.

### Tarjimalar

`lang/{uz,ru,en}/app.php` ga **112 ta kalit** to'liq qo'shildi.

> **Topilgan eski bug:** `app.actions`, `app.detele`, `app.header.mac`,
> `add_device_sure` va yana 9 ta kalit faqat `ru` da bor edi. O'zbek va ingliz
> tilida qurilmalar jadvali xom kalit nomlarini chiqarardi (`app.header.mac`).
> Endi uchala tilda to'liq.

Shuningdek `modal.now`, `modal.month`, `services.add_devices`, `header.choose`
CAPS dan sentence case ga o'tkazildi — ular endi ro'yxat elementi sarlavhasi,
katta baqiruvchi tugma emas.

## Verifikatsiya

| Nima | Natija |
|---|---|
| `php artisan test` | **29/29** o'tdi |
| `./vendor/bin/pint --test` | passed |
| `npm run build` | CSS 46 KB (gzip 8.9), JS 6.5 KB (gzip 2.4) |
| Desktop 1440px | cabinet, traffic, payment, tariffs, services, login, select-account |
| **Mobil 390px (haqiqiy viewport, iframe)** | gorizontal scroll yo'q (`scrollWidth === clientWidth`) |
| Interaktiv | drawer, til menyusi, hisob menyusi (+Escape/aria-expanded), oy tanlash modali, tarif oqimi, jadval paginatsiyasi |
| Bo'sh holatlar | payment-empty, traffic-empty, cabinet-empty, tariffs-empty |

**Preview usuli** (kelgusi dizayn passlari uchun): API yo'q, shuning uchun
vaqtinchalik `tests/Feature/PreviewRenderTest.php` yozildi — `Http::fake()` bilan
har bir sahifani render qilib `public/__preview/*.html` ga yozadi, keyin ular
`localhost:8080` orqali brauzerda ochiladi. Mobil uchun `mobile.html` — 390px
kenglikdagi `<iframe>` (media query'lar haqiqiy viewport'ga qarshi ishlaydi,
chunki oyna o'lchamini o'zgartirish WM fullscreen'da ishlamadi). Yetkazishdan
oldin ikkalasi ham o'chirildi.

> Eslatma: `Http::fake()` qayta chaqirilganda stub'lar **qo'shiladi** va birinchi
> mos kelgani yutadi — bo'sh holat fixture'lari alohida test metodida bo'lishi
> shart.

## Tuzatilgan nuqsonlar (verifikatsiya davomida topilgan)

1. **Mobil gorizontal scroll** — grid item'ning default `min-width: auto` si
   sababli ichidagi `overflow-x-auto` jadval butun qatorni viewport'dan
   kengaytirardi. `min-w-0` qo'shildi.
2. **Attribut ichidagi `@lang()`** — `@lang` escape qilmaydi; tarjimadagi qo'shtirnoq
   atributdan chiqib ketishi mumkin edi. 10 ta joyda `{{ __() }}` ga o'tkazildi.
3. `data-tariff-name-slot` — sahifada ikkita modal bor, `querySelector` faqat
   birinchisini topardi.
4. Hisob tanlash ro'yxatida nom va tur pill'i bir xil matnni takrorlardi.
5. `accounts.choose_hint` tarjimasi yo'q edi — xom kalit ko'rinardi.
6. Modal'da `role="dialog"` bor edi, lekin nomsiz modallar (confirm) uchun
   accessible name yo'q edi — `:label` prop qo'shildi.

## Rad etilgan yo'nalishlar

- **Cabinet sahifasidagi arc gauge.** Avval qurilma slotlari ham 240° yoy bilan
  chizilgan edi. Ikki sabab bilan olib tashlandi: (a) hero qatorini keraksiz
  baland qilib, balans kartochkasida katta bo'sh joy qoldirardi; (b) bitta
  trikni ikki marta ishlatish signature element'ni arzonlashtiradi. Slotlar
  sanaladigan va kam — segment chiziqlari "yana ikkitasi bor" ni tezroq
  o'qitadi. Yoy faqat trafikda qoldi, u yerda miqdor haqiqatan uzluksiz.
- **Museo Sans Cyrl saqlash** — yuqoriga qarang, bitta og'irlik.
- **DataTables saqlash** — jQuery'ni bitta xususiyat uchun olib qolish kerak
  edi. 60 qatorli vanilla paginator o'rniga yozildi, xatti-harakat bir xil
  (20 qator/sahifa).

## Foydalanuvchi bajarishi kerak

1. **Deploy'ga `npm ci && npm run build` qo'shish.** `public/build/` gitignore'da.
   Busiz ilova `Vite manifest not found` bilan ishlamaydi. `DOCKER.md` ga
   yozildi. Serverda Node bo'lmasa — lokalda yig'ib, `public/build/` ni
   nusxalash kifoya.
2. **Trafik yorliqlarini tasdiqlash.** Eski doughnut chartda `traffic_output`
   "qabul qilindi" deb, `traffic_input` "yuborildi" deb belgilangan edi —
   bu jadval sarlavhalariga **teskari** (`traffic.inn` = "Qabul qilindi" →
   `traffic_input`). Yangi UI jadvalga ergashdi, chunki jadval API'ga qarshi
   tekshirilgan. Agar chart to'g'ri bo'lgan bo'lsa — `trafic/detail.blade.php`
   dagi `$input`/`$output` yorliqlarini almashtirish kerak.
3. **`main-logo-h.png` sifati.** 166×53 px, retina ekranda yumshoq ko'rinadi.
   SVG yoki 2x PNG bo'lsa yaxshi bo'lardi.

## Kelgusi ish uchun (bu passda qilinmadi)

- Qurilma qo'shish/o'chirish hali ham **GET** havolalari
  (`/devices/add`, `/devices/delete/{id}`). Holat o'zgartiruvchi GET —
  prefetch yoki link-scanner uni tasodifan ishga tushirishi mumkin. Bu backend
  marshrut masalasi, UI ko'chirish qamrovidan tashqarida; POST + `@csrf` ga
  o'tkazish tavsiya etiladi (confirm dialogi allaqachon `target.form.submit()`
  ni qo'llab-quvvatlaydi).
- `payment_status` API'dan erkin matn keladi — hozir neytral pill. Mumkin
  qiymatlar ro'yxati ma'lum bo'lsa, yashil/amber holat ranglari qo'shiladi.
- Tariflar va "Qo'shimcha xizmatlar" sahifalari ishlaydi, lekin navigatsiyada
  yo'q (eski shablonlarda ham izohga olingan edi). Ataylab shunday qoldirildi.
