# Qorong'i mavzu va matn hajmi boshqaruvi — prototipdan ilovaga

- **Sana:** 2026-08-10 11:22
- **SCOPE:** frontend · **RISK:** false (ko'rinish sozlamasi; server tomonida hech narsa saqlanmaydi)

## Nima uchun

TZ §1.1 bo'yicha fon neytral to'q kulrangga o'tkazilgandan keyin ilovada
qorong'i mavzu ham, matn hajmi boshqaruvi ham yo'q edi — ular faqat `design/`
prototipida bor edi. Foydalanuvchi ularni ilovada ham so'radi.

## Scope

**Yangi:**
- `resources/views/components/view-settings.blade.php` — panel (mavzu + matn hajmi)
- `resources/views/components/view-boot.blade.php` — `<head>` dagi inline skript
- `resources/js/modules/prefs.js`
- `tests/Feature/ViewSettingsTest.php`

**O'zgargan:**
- `resources/css/app.css` — tokenlar `--c-*` o'zgaruvchilariga, `@theme` → `@theme inline`,
  qorong'i bloklar, `.u-choice`, `[data-text]` pog'onalari, `color-scheme`
- `resources/js/app.js` — `initPrefs()`
- `layouts/app.blade.php`, `layouts/guest.blade.php` — `<x-view-boot/>` + panel
- `partials/topbar.blade.php` — panel
- `components/icon.blade.php` — `view`, `sun`, `moon`, `auto`
- `lang/{uz,ru,en}/app.php` — `view.*`
- 14 ta joyda `text-leaf-700` → `text-action`

## Qarorlar

**Nega `--c-*` + `@theme inline`.** Tailwind `@theme` dagi qiymatlar statik —
ular media query ichida qayta aniqlanmaydi. Shuning uchun mavzuga bog'liq har
bir qiymat oddiy CSS custom property'ga chiqarildi va `@theme inline` orqali
Tailwind'ga uzatildi. Bu prototipdagi naqshning aynan o'zi.

**Nega `leaf-*` mavzuga bog'liq emas.** Asosiy tugma ikkala mavzuda ham
`leaf-700` + oq yozuv (5.75:1). Abonent tugmani mavzudan qat'i nazar bir xil
tanishi kerak. Mavzu bilan o'zgaradigan yagona yashil — `--c-action` (havola,
ikonka, faol menyu), chunki `#4A7100` qorong'i kartochkada 2.9:1 ga tushadi va
uni logotipning o'z `#90D600` iga yoritish shart (8.99:1).

**Nega uch holat, "system" atribute'siz.** Bo'sh qiymat atributni **o'chiradi**,
uchinchi kalit so'z yozilmaydi. Shunda "tizim bo'yicha" va "oddiy hajm" haqiqiy
standart holat bo'lib qoladi va CSS o'zi `prefers-color-scheme` ga tushadi.

**Nega inline boot skript.** Bundle'ga qo'yilsa u birinchi paint'dan keyin
ishlaydi — qorong'i mavzudagi abonent har bir o'tishda oq chaqnashni ko'radi.
Test buni pinlaydi: skript `<link rel="stylesheet">` dan **oldin** turishi shart.

**Nega serverda saqlanmaydi.** Bu qurilma sozlamasi. Billingga yuborish shrift
o'lchamini o'zgartirish uchun tarmoq so'rovi degani.

**`h-full` on `.u-choice`.** Ruscha "Самый крупный" ikki qatorga tushadi;
usiz o'sha chip qo'shnilaridan baland bo'lib, qator notekis ko'rinardi.

## Kontrast (o'lchangan, taxmin emas)

| | Yorug' | Qorong'i |
|---|---|---|
| ink / surface | 17.46:1 | 13.25:1 |
| muted / surface | 7.26:1 | 6.52:1 |
| action / surface | 5.75:1 | 8.99:1 |
| oq / leaf-700 (tugma) | 5.75:1 | 5.75:1 |
| line-strong / surface | 3.86:1 | 3.19:1 |
| pill-warn (alfa kompozit) | 5.23:1 | 5.23:1 |
| btn-danger (alfa kompozit) | 6.39:1 | 6.04:1 |

Qorong'ida `.u-card` ga chegara qo'shildi: `#1f2225` kartochkani `#16181a`
panel ustida faqat soya ajrata olmaydi.

## Tekshiruv

- `npm run build` — xatosiz, CSS 48.35 kB (gzip 9.43).
- `php artisan test` — **51 passed (250 assertions)**, shundan 3 tasi yangi.
- Headless Chrome: qorong'i (tizim bo'yicha) va yorug'
  (`--blink-settings=preferredColorScheme=1`) — piksel bilan tasdiqlandi
  (kartochka `#FFFFFF` / fon `#151916`).
- Panel ochiq holatda ikkala mavzuda render qilindi.
- View'lardagi barcha `u-*` sinflar bundle'da mavjudligi skript bilan tekshirildi.

## Qolgani

`design/` prototipi hali logotip-yashil fonda — ilova esa neytral kulrangda.
Ikkalasi yana bir-biridan uzoqlashdi; prototip tokenlarini ham neytralga
o'tkazish kerak (`design/assets/tokens.js`, ~30 qator).

## ⚠️ Risks

- Qorong'i mavzu **birinchi marta** joriy qilinyapti — hozirgacha faqat
  login/kabinet ekranlari headless bilan ko'rildi. To'lovlar jadvali,
  statistika va modal oynalar qorong'ida real brauzerda ko'rilishi kerak.
- `prefers-color-scheme` bo'yicha ishlaydigan abonentlar endi hech narsa
  bosmasdan qorong'i mavzuni oladi. Agar bu kutilmagan bo'lsa, standart holatni
  `light` ga qotirib qo'yish mumkin (bitta qator).
