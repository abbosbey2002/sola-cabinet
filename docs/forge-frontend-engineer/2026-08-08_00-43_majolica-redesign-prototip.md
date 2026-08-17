# "Majolica" — kabinet dizaynini qayta ko'rish (prototip)

**Sana:** 2026-08-08 00:43 · **Natija:** `design/cabinet.html`, `design/HANDOFF.md`

Buyurtmachi: *"desing yaxshi bo'magan, ko'zni o'gritadi. dark va light rejimlarni
qo'shing. html va tailwindda qilaylik, officega borib bladega o'giramiz."*

Shuning uchun bu bosqichda **Bladega tegilmadi** — ilova kodi o'zgarmagan.
Prototip alohida `design/` katalogida turadi.

## Qamrov va kelishilgan qarorlar

Uchta savol berildi, javoblar:

| Savol | Tanlov |
|---|---|
| Ekranlar | **Hammasi** — login, SMS, hisob tanlash, dashboard, statistika, moliya |
| Palitra | **To'liq erkinlik** — logo rangi faqat markada qoladi |
| Karkas | **Qorong'i ramka olib tashlansin** |

## Muammoning tashxisi

Ko'zni qiynayotgan narsa uchta edi, uchalasi ham o'lchanadigan:

1. **Sandvich kontrast.** `pine-950` (#0b2019) ramka ichida `canvas` (#f1f5ee)
   panel — yonma-yon ikki katta yuza orasida ~14:1 farq. Ko'z har surilganda
   qayta moslashadi.
2. **Katta yuzalarda acid-lime.** `#88d000` — grafik rang, lekin u navigatsiya
   pillasi, tugmalar va ikonka fonlarida ishlatilgan edi.
3. **Yagona shrift.** Hamma narsa Inter'da — ierarxiya faqat o'lcham bilan
   berilgan, shuning uchun sahifa "tekis" o'qiladi.

## Dizayn rejasi

**Yo'nalish: "Majolica"** — palitra Registon/Buxoro koshinlaridan. Sirlangan
ko'k-firuza katta yuzada tinch, za'faron esa yagona iliq aksent. Bu ataylab
tanlangan: mahalliy va tinch, ya'ni shikoyatning aynan aksi.

**Palitra** (semantik tokenlar, mavzu bilan almashadi):

| Token | Light | Dark | Vazifasi |
|---|---|---|---|
| `--c-bg` | `#f1f4f3` | `#071e27` | Sahifa yuzasi |
| `--c-surface` | `#ffffff` | `#0f2c35` | Karta |
| `--c-ink` | `#0b2530` | `#e4edec` | Matn |
| `--c-muted` | `#5a7076` | `#93aaaf` | Ikkilamchi matn |
| `--c-tile` | `#16646b` | `#5bc6c4` | Asosiy amal |
| `--c-saffron` | `#d98e2b` | `#edb059` | Ma'lumot aksenti |
| `--c-clay` | `#b4472f` | `#f08a6e` | Xato / manfiy |

Kontrast skript bilan hisoblangan, hammasi AA dan o'tadi. Bitta istisno
hujjatlashtirilgan: **`--c-saffron` oq ustida 2.67:1** — grafik rang, mayda
matn ko'tarmaydi; matn uchun `--c-saffron-ink` (5.09:1).

**Shriftlar:** **Unbounded** (display — sarlavha, balans, jamilar) +
**Onest** (matn, jadval). Ikkalasi ham to'liq kirill va latin-ext.
Kabinetning butun mazmuni raqam, shuning uchun brend yuzasi ham raqamlar —
Unbounded faqat shu yerlarda, oddiy matnda hech qachon.

**Imzo element — "hisob davri chizig'i".** Abonentning yagona savoli:
*"hisobim yetadimi?"*. Raqamning o'zi bunga javob bermaydi. Hero kartada
balans ostida davr chizig'i: o'tgan kunlar to'ldirilgan, oxirida za'faron
belgi — keyingi yechim sanasi va summasi. Balans yetmasa qancha yetishmasligi
aniq yoziladi. Yuklanishda bir marta suriladi.

Uch holat ham qilingan (`ok` / `low` / `neg`) — prototip panelidan
almashtiriladi. Faqat "hammasi joyida" holatida ishlaydigan dizayn — dizayn emas.

## Rad etilgan yo'nalishlar

- **Deep-teal + acid aksent.** Bu AI-standart ko'rinish #2 va ayni paytdagi
  dizaynning o'zi. Aksent ataylab bo'g'iq firuzaga tushirildi, dark fon esa
  qora emas, aniq ko'k-firuza (`#071e27`) qilindi.
- **Hero'da "katta raqam + gradient"** — shablon javob. Raqam qoldi, lekin
  gradient yo'q; farqni davr chizig'i beradi.
- **Logotipdagi qushni qo'lda chizish.** 22px da dog'day o'qildi. `)))` yoylari
  + Unbounded'dagi "SOLA" qoldirildi — yoylar kompaniya sotadigan narsaning
  o'zi va ikkala mavzuda ham ishlaydi. Login ekranidagi qush PNG'i esa
  hozircha olib tashlandi (u oq matnli, faqat qorong'i fonda ishlaydi).

## Tekshirilgani

| Nima | Qanday | Natija |
|---|---|---|
| Light / dark / tizim | Uchala holat brauzerda | ✅ |
| Telefon 390px | `mobile-check.html` — haqiqiy 390px iframe, ikkala mavzu | ✅ gorizontal scroll yo'q |
| Jadval → karta (<640px) | Skrinshot | ✅ |
| Klaviatura fokusi | Haqiqiy Tab, `:focus-visible` o'lchandi | ✅ `2px solid #16646b` |
| Kontrast | PHP skript, WCAG formulasi | ✅ (saffron istisnosi yozildi) |
| `prefers-reduced-motion` | CSS bor | ⚠️ ko'z bilan tekshirilmagan |

> **Eslatma.** Chrome extension'ning `resize_window` asbobi ta'sir qilmadi —
> viewport 1920 da qoldi. Shuning uchun telefon ko'rinishi iframe orqali
> tekshirildi: bu haqiqiy render, "media so'rov to'g'ri yozilgan" degan
> taxmin emas.

## Quvur natijalari

**forge-code-reviewer** — 3 blocker, 14 warning. Blockerlarning uchalasi ham
prototipning o'zida emas, **Bladega ko'chirishda** portlaydigan narsalar edi:

1. CSS bloki almashtirish emas, diff ekani (`.is-busy`, `.u-no-print`,
   `.u-sortable` va eski palitra tushib qolardi) → `HANDOFF.md` §1
2. Drawer hook nomlari `nav.js` bilan mos emasligi → **tuzatildi**
   (`data-nav-drawer` / `data-nav-scrim` / `data-nav-close`)
3. Shriftlar CDN'da qolib ketishi → `HANDOFF.md` §2, npm paketlari tekshirildi

Prototipda tuzatilganlar: `replay()` inline `animation-delay` ni o'chirib
yuborardi (yoy segmentlari ketma-ket emas, birdaniga chizilardi) · mavzu
chaqnashi (`<head>` ga blokirovkalovchi skript) · telefonda jadval
kataklaridan birlik yo'qolishi (`data-label` ↔ `<th>`) · `u-table-cards` da
`td:empty` va `min-width` yo'qligi · drawer'da `role="dialog"`, fokus
qaytarish, body scroll lock, desktopga reset · skip-link · radio guruhga
`fieldset/legend` · til/hisob tugmalariga nom va `aria-haspopup` · 30+ ikonkaga
`aria-hidden` · `setState()` `innerHTML` o'rniga DOM API (bu joyga keyin real
abonent ismi tushadi) · o'lik kod.

Hal qilinmagan (dizayn kerak, `HANDOFF.md` §5): `x-table-nav` (qidiruv,
sahifalash, Excel), `.u-sortable`, toast'lar, modal, davr ogohlantirishlari.

**forge-security-auditor** — prototipning o'zida critical yo'q: sir yo'q, real
abonent ma'lumoti yo'q (hammasi `FakeSolaServer` fiksturalari), `innerHTML`
yo'q, cookie'larga tegilmaydi. Ikkita high topildi:

1. **Inline `on*=` atributlari** (23 ta) — Bladega ko'chganda ularning ichiga
   billingdan kelgan ism tushadi, `{{ }}` esa JS satr kontekstida himoya
   qilmaydi. → **tuzatildi**, endi 0 ta; navigatsiya `data-*` + delegatsiya.
2. **Pul sarflaydigan amallar forma tashqarisida** → tugmalar POST forma
   ichiga olindi va `HANDOFF.md` §10 yozildi.

> ### ⚠️ Mavjud ilovadagi zaiflik — bu sessiyada tuzatilmadi
>
> Tekshiruv paytida prototipdan mustaqil, **hozirgi production kodidagi**
> muammo tasdiqlandi (`routes/web.php:37,44,45`):
>
> ```php
> Route::get('/connect/{id}/{type}', [TariffController::class, 'connect'])
> Route::get('/add', [DeviceController::class, 'store'])
> Route::get('/delete/{permitId}', [DeviceController::class, 'destroy'])
> ```
>
> Uchalasi ham holatni o'zgartiradi, ikkitasi abonentga pul yozadi (qurilma
> ulash — 15 000 so'm). GET so'rovda CSRF tokeni yo'q: kirgan abonent begona
> sahifaga o'tsa, o'sha sahifa `cabinet.sola.uz/devices/add` ga yo'naltirishi
> kifoya. Ilovada framing himoyasi ham yo'q (`X-Frame-Options` /
> `frame-ancestors` hech qayerda), ya'ni clickjacking ham ochiq.
>
> Tuzatish `Route::post` + `@csrf` + view'larda forma — ya'ni aynan Blade
> konvertatsiyasi paytida qilinadigan ish. Shuning uchun bu yerda faqat
> qayd etildi, kodga tegilmadi. **Konvertatsiyada birinchi qilinsin.**

Boshqa hal qilinmaganlar: CDN va Google Fonts havolalari production'ga
o'tmasligi (`HANDOFF.md` §2, §8) · `<meta name="csrf-token">` ni yo'qotib
qo'ymaslik (§11) · ekranlarni bitta hujjatda saqlash prototipga xos ekani
(§12) · `design/` `.gitignore` da yo'q — commit qilishdan oldin ataylab qaror
qiling.

## Keyingi qadam

Officeda: `design/HANDOFF.md` ni ochib §1 dan boshlang. Eng xavflisi — CSS
blokini `app.css` ustiga nusxalash. U **diff**, almashtirish emas.

Konvertatsiya tartibi: §10 (GET → POST, xavfsizlik) → §1 (CSS diff) →
§2 (shriftlar) → §7 (ekran → view) → §4 (komponentlarni qayta ishlatish).
