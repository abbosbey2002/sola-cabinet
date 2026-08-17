# Palitrani logotipga moslash + shrift shkalasini kattalashtirish

**Sana:** 2026-08-10 10:49
**Xodim:** forge-frontend-design
**Rejim:** SaaS product + Multi-market (uz/ru/en)
**SCOPE:** frontend · **RISK:** false (rang va shrift; pul/auth/shaxsiy ma'lumot yo'lida o'zgarish yo'q)

## Talab

1. Primary ranglarni logotipga moslash.
2. Shriftlarni kattalashtirish.
3. Bu qanday dastur, unga reference dizaynlar bormi.

## Muammoning ildizi — o'lchangan, taxmin qilinmagan

Logotipning yashili `public/img/logo-mark.png` dan olindi:

| Rang | OKLCH | Izoh |
|---|---|---|
| `#8FD400` | L 0.793 · C 0.211 · **H 129.4°** | logotip bargi (asosiy) |
| `#A0E048` | H 129.6° | yorug' qirra |
| `#C8F878` | H 126.5° | eng yorug' nuqta |

Ilovadagi "primary"lar esa:

| Qayerda | Qiymat | Ohang | Logotipdan chetlanish |
|---|---|---|---|
| `app.css` (jonli ilova) | `#103029` | 176.7° | **47°** — firuza |
| `design/tokens.js` (prototip) | `#087D20` | 144.8° | **15°** — sovuqroq yashil |

Ya'ni ko'z brend rangini tanimasdi: yon-mayonda turgan logotip va tugma
ikki xil oiladan edi.

## Yechim — bitta ohang, u ham logotipniki

Hamma yashil `H = 129.4°` chizig'ining yorug'lik pog'onalariga aylantirildi.
Qiziq fakt: sRGB da shu chiziqning eng to'yingan nuqtasi `L=0.79` da va u
aynan `#8FD400` — logotip o'z ohangining cho'qqisida turibdi.

| Token | Yorug' | Qorong'i | Kontrast |
|---|---|---|---|
| `primary` / `action` | `#4A7100` | `#4A7100` | oq yozuv 5.75:1 |
| `primary-hover` | `#3B5D00` | `#3B5D00` | 7.63:1 |
| `primary-rim` | `#3B5D00` | `#73AD00` | qorong'ida 6.92:1 |
| `action` (qorong'i) | — | `#90D600` | yuzada 9.27:1 |
| `lime` (GRAFIK) | `#8FD400` | `#8FD400` | oqda 1.81:1 — matn ko'tarmaydi |
| `ink` | `#141D09` | `#E5EDDD` | 17.4:1 / 13.7:1 |
| `muted` | `#505B46` | `#AAB69E` | 7.2:1 / 7.8:1 |
| `line-strong` | `#778966` | `#6B7B5B` | 3.78:1 / 3.62:1 |

Qorong'i rejimda `--c-action` = `#90D600` — bu deyarli logotipning o'zi.
Ya'ni qorong'ida brend rangi matn bo'la oladi; yorug'da esa yo'q, shuning
uchun u yerda `#4A7100` ishlaydi. Logotipdagi munosabat aynan shu: yashil
qorong'i yuzada yashaydi.

`warn` (`#8A5A00`) va `danger` (`#A32014`) atayin yashil oiladan tashqarida
qoldirildi — brend ichidagi ogohlantirishni hech kim sezmaydi.

## Shrift

| | Oldin | Hozir |
|---|---|---|
| ildiz | 112.5% (18px) | **118.75% (19px)** |
| `text-xs` | 0.9375rem | **1rem (19px)** |
| `text-base` | 1.0625rem | **1.125rem (21.4px)** |
| `text-3xl` | 2.125rem | **2.25rem (42.8px)** |
| "Matn hajmi" | 125% / 140% | **131.25% / 145%** |

Shkalada kichik pog'ona umuman yo'q: tasodifan yozilgan `text-xs` ham
o'qilishi mumkin bo'lgan matn beradi.

## Yo'l-yo'lakay tuzatilgan nuqsonlar

- `.u-btn-primary` jonli ilovada `bg-pine-900` (qorong'i korpus rangi) edi —
  endi brend yashili `leaf-700`.
- Fokus halqasi `leaf-500` bilan chizilardi: oq ustida 1.81:1, ya'ni deyarli
  ko'rinmas edi. Endi `leaf-700`, 3px, 3px offset.
- `--color-clay` = `#D9503F` → oqda **4.06:1**, AA dan past bo'lsa ham xato
  matni shu rangda edi. `#A32014` (7.57:1) ga almashtirildi.
- 11px `uppercase` + `tracking` mayda yorliqlar butunlay olib tashlandi
  (`.u-label`, jadval sarlavhalari, mobil karta yorliqlari) — kirill va
  o'zbek yozuvida bosh harflar so'z shaklini yo'qotadi va kenglik yeydi.
- `.u-field` endi `min-h-3rem` (57px) va `border-line-strong` — forma
  elementi o'z chegarasini bildirishi kerak.
- Blade'dagi `text-[#8a6400]`, `#0b2019`, `text-[0.9375rem]` literallari
  tokenlarga ko'chirildi.

## Bonus: bundle 15% kichraydi

Tailwind ning avtomatik manba topishi `design/` prototipini ham skanerlar
ekan — prototipdagi har bir sinf va hatto izohdagi tasodifiy so'zlar
production CSS ga tushib turgan. `@source not '../../design'` qo'shildi:

```
56.41 kB → 48.23 kB   (gzip 10.24 → 9.15 kB)
```

## Tekshiruv

- `npm run build` — xatosiz.
- View'larda ishlatilgan barcha `u-*` sinflar bundle da mavjudligi
  skript bilan tekshirildi — biror komponent tushib qolmagan.
- `php artisan test` — **48 passed (231 assertions)**.
- Headless Chrome bilan render: 1280px yorug'/qorong'i, 390px, 320px —
  gorizontal toshish yo'q, tugma pikseli aynan `#4A7100`.

## Ochiq qolgani

`design/` prototipi hali to'liq Blade'ga ko'chirilmagan (HANDOFF.md §1):
`tariff.html`, `devices.html`, `services.html` sahifalari va foydalanuvchi
uchun "Ko'rinish" paneli (mavzu + matn hajmi boshqaruvi) jonli ilovada yo'q.
Bu ishda faqat palitra va shrift shkalasi ikkala tomonga birdek qo'llandi.
