# test-design/ — Payme ilovasi tilida redizayn

**Sana:** 2026-08-10 12:15
**Rejim:** Mobile-first consumer (asosiy) + SaaS + Multi-market
**Reference:** foydalanuvchi bergan ikkita skrinshot (Payme ilovasi: dashboard va PIN ekrani)

## Boshlang'ich holat

`test-design/` — `design/` ning bayt-ma-bayt nusxasi (`diff -rq` farq topmadi),
ichidagi `test/` bo'sh. Ya'ni "nusxa olish uchun" yangi manba yo'q edi; ish
reference **rasmlardan** olindi.

## Reference'dan olingan narsalar

| Rasmda | Bu yerda |
|---|---|
| Yer ustiga ko'tarilgan yumaloq varaq + tortish dastagi | `.u-sheet` |
| Hairline ajratgichli qatorlar: qalin sarlavha, xira tavsif, o'ngda ikonka | `.u-row` / `.u-row-icon` |
| Gorizontal karta karuseli | `.u-rail` (scroll-snap) |
| Pastda suzuvchi oq pill + doira tugmalar | `.u-dock` / `.u-dock-pill` / `.u-dock-btn` |
| Fon ortidagi ambient yorug'lik | `.u-ambient` (konus-gradient, rasm emas) |
| Rangli konturli PIN katakchalari | `.u-code-slot` |

**Olinmagani:** fotografik fon. U tarmoqqa bog'liq (offline qoidasi) va har
lokalga qayta tekshirilishi kerak. O'rniga logotipning konsentrik yoylari
gradient sifatida — 0 bayt, offline ishlaydi.

## Anti-default tekshiruvi

Reference "qora fon + bitta aksent" — bu AI-default ko'rinishlardan biri.
Undan chiqish uchun uchta narsa qilindi:

1. **Uch pog'onali balandlik** yassi qora o'rniga: yer `#0a0b0c` → varaq
   `#141618` → kartochka `#1e2124`. Yassi qora yo'q.
2. Aksent umumiy "acid green" emas — logotipdan **o'lchab olingan** `#8FD400`
   va uning yorug'lik pog'onalari.
3. Struktura umumiy "dark landing" emas, aniq artefaktdan: varaq + dastak +
   hairline qatorlar + dock. Bu fintech ilova idiomasi.

## Ma'no bo'yicha qaror

Balans varaqning **ustida emas, ortida**. Eng muhim raqam eng yuqori qatlamda
emas, eng chuqurida turadi; varaq esa uning ustiga ko'tariladi. Sabab mazmunda:
balans — **holat**, varaqdagi qatorlar — **harakat**. Reference'dagi munosabat
ham aynan shu.

Kartochkalar panjarasi bitta ro'yxatga aylantirildi: telefonda ko'z pastga tik
yuradi, har bir qator bir joydan boshlanadi — to'rt xil joydan boshlanadigan
panjaradan tez o'qiladi.

## Kod ekrani

Bitta uzun maydon → to'rtta katakcha. Lekin kiritish uchun baribir **bitta**
`<input>`: u shaffof qilib katakchalar ustiga qo'yiladi. Sabab — to'rtta alohida
input `autocomplete="one-time-code"` SMS avtoto'ldirishni buzadi va ekran
o'quvchiga "4 ta alohida maydon" bo'lib eshitiladi. Katakchalar `aria-hidden`:
ular bezak, ma'no inputda.

## Kontrast (o'lchangan, 16 juftlik — 0 ta muvaffaqiyatsizlik)

| | Yorug' | Qorong'i |
|---|---|---|
| dock pill | 17.80:1 | 16.44:1 |
| qator sarlavhasi | 17.80:1 | 16.44:1 |
| qator tavsifi | 7.55:1 | 6.95:1 |
| qator ikonkasi (soft ustida) | 5.09:1 | 8.41:1 |
| rel kartochkasi tavsifi | 7.02:1 | 6.19:1 |
| katakcha gardishi | 4.22:1 | 3.62:1 |

## Tuzatilgan nuqsonlar (render paytida ko'rindi)

- Dock pill 414px da ikki qatorga tushardi. Telefonda doira tugmalar
  yashiriladi (qo'ng'iroq — drawer'da, Speed Test — navigatsiyada), pill esa
  qolgan kenglikni to'liq egallaydi. "Bir ekranda bitta asosiy harakat".
- Desktopda qatorlar 1240px ga cho'zilib, o'ngdagi ikonka uzoqda qolardi.
  Varaq ustuni `46rem` ga cheklandi.

## Tekshiruv

- `node --check` — `tokens.js` va `app.js` sintaksis toza; CSS qavslari teng.
- Render: 320 / 390 / 414 / 1280 px, yorug' va qorong'i mavzu.
- Qolgan ekranlar (`payments`, `statistics`, `tariff`) buzilmadi — yangi
  palitrani avtomatik oldi (fon `#0A0B0C`).
- Mavzu almashtirgich va matn hajmi boshqaruvi ishlashda qoldi.

## Qolgani

Faqat `index.html` va `verify.html` yangi tilga to'liq o'tkazildi. Qolgan
oltita ekran yangi **palitrani** oldi, lekin **strukturasi** eskicha
(kartochkalar panjarasi). Ularni ham varaq + qator tiliga o'tkazish kerak.

Jonli Laravel ilovasiga bu ishda **tegilmadi**.
