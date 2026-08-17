# QA CHECKLIST — SOLA shaxsiy kabinet (TZ v1 qabul testi)

**Manba:** `docs/task/tz_v1.docx` (14 bo'lim) · **Bloklanganlar:** `MISSING_APIS.md`, `docs/task/BAJARILMAGAN_TASKLAR.md`
**Egasi:** `forge-qa-browser` skill · **Oxirgi tahrir:** 2026-08-14

Bu ro'yxat **brauzerda qo'lda** bajariladi. Har bir qator uchun verdikt:
`PASS` / `FAIL` (nuqson raqami bilan) / `BLOCKED` (billing API'da maydon yo'q — manba ko'rsatiladi) /
`N/A` (env flagi o'chirilgan — qaysi flag yoziladi) / `NOT TESTED` (sababi bilan).

---

## 0. Muhit — testdan oldin tasdiqlanadi

| # | Tekshiruv | Kutilgan |
|---|---|---|
| 0.1 | `APP_URL` ochiladimi | `http://192.168.0.109:8080` (yoki docker `8080:80`) javob beradi |
| 0.2 | Billing ulanishi | `API_IP=172.19.1.201:808`, `SOLA_FAKE=false` — **jonli billing** |
| 0.3 | Assets yangimi | `npm run build` bajarilgan; eski bundle bilan test qilingan natija **hisobga olinmaydi** |
| 0.4 | Abonent | Real hisob (masalan `TEST PAYMENTS`, l/s `1336708`) — ism va raqam hisobotga yoziladi |
| 0.5 | Konsol toza holatda ochiladi | DevTools console har sahifada o'qiladi; xato = nuqson |

> **Pul harakati:** CEO ruxsat bergan (2026-08-14). Tarif almashtirish va qurilma qo'shish
> **bajariladi**, lekin har biri: (1) bosishdan oldingi qiymatlar, (2) tasdiqlash oynasi matni,
> (3) bosishdan keyingi qiymatlar — uchalasi hisobotning «State changes» bo'limiga yoziladi.
> **Har oqim bir pass'da bir marta.** Qayta bosish = ikkinchi to'lov.

---

## A. Avtorizatsiya (TZ §1.2)

| # | Qadam | Kutilgan natija | V |
|---|---|---|---|
| A.1 | `/auth/login` ochiladi | Telefon maydoni (`name="login"`), autofocus, `type=tel` | |
| A.2 | Noto'g'ri/mavjud bo'lmagan raqam | Xato matni chiqadi, kod **yuborilmaydi** (billing `code 110`) | |
| A.3 | To'g'ri raqam | SMS ketadi, `/auth/verify` ga o'tadi | |
| A.4 | 5 martadan ko'p yuborish (1 daqiqada) | `throttle:5,1` ishlaydi — 429, SMS bombardimon qilib bo'lmaydi | |
| A.5 | Noto'g'ri SMS kod | Xato matni, sahifada qoladi | |
| A.6 | 10 martadan ko'p kod urinishi | `throttle:10,1` ishlaydi — 429 | |
| A.7 | To'g'ri kod, bir nechta hisob | `/auth/select/account` — hisoblar ro'yxati chiqadi | |
| A.8 | Hisob tanlanadi | `/` ga o'tadi, tanlangan hisob topbar'da ko'rinadi | |
| A.9 | Kirmasdan `/finance` ga urinish | `/auth/login` ga qaytaradi (`abonent.verified`) | |
| A.10 | Kirgandan keyin `/auth/login` | Kabinetga qaytaradi (`abonent.guest`) | |
| A.11 | `/auth/logout` | Cookie tozalanadi; orqaga tugmasi bilan kabinetga qaytib bo'lmaydi | |
| A.12 | Hisob almashtirish (`/select/account/{id}`) | Boshqa hisob ma'lumoti chiqadi — **eski hisob raqamlari qolib ketmaydi** | |

> **A.12 kritik:** hisob almashgach balans/tarif/to'lovlar to'liq yangilanishi shart.
> Bitta eski qiymat qolsa — bu `CRITICAL` (hisoblararo ma'lumot oqishi).

---

## B. Tuzilish va topbar (TZ §2)

| # | Tekshiruv | Kutilgan | V |
|---|---|---|---|
| B.1 | Navigatsiya yuqorida | Bandlar: Данные · Статистика · Финансовая статистика · Speed Test | |
| B.2 | Joriy band ajratilgan | `aria-current` yoki vizual holat | |
| B.3 | ФИО | Abonent ismi ko'rinadi | |
| B.4 | № договора | **BLOCKED** — `contract_number` `/abonent/info` da yo'q (`MISSING_APIS.md` §1.1). Maydon bo'lmasa **umuman ko'rinmaydi**, «—» chiqmaydi | |
| B.5 | Лицевой счет | Hisob raqami ko'rinadi | |
| B.6 | Til almashtirgich | uz / ru / en — uchalasi ishlaydi | |
| B.7 | Call Center | `1130` bosiladigan `tel:` havola; `+99871 207 08 06` menyuda | |
| B.8 | Mobil menyu (390px) | Burger ochiladi, ichida til + hisob + call center bor | |

---

## C. «Данные» — bosh sahifa `/` (TZ §3.1, §3.2)

| # | Maydon | Kutilgan | V |
|---|---|---|---|
| C.1 | Balans | Joriy qoldiq, **so'm birligi bilan**, minus `−` belgisi (defis emas) | |
| C.2 | Текущий тариф | Faol tarif nomi | |
| C.3 | Joriy tarif narxi | **BLOCKED** — `curr_tariff_cost` yo'q (`MISSING_APIS.md` Task 2.2) | |
| C.4 | Следующий тариф | **BLOCKED** — `next_tariff_name/cost` yo'q. UI «Не выбран / Tanlanmagan» ko'rsatishi shart | |
| C.5 | Дата следующего списания | **BLOCKED** — `next_charge_date` yo'q. ⚠️ **Hisoblab chiqarilgan sana ko'rinsa — bu `HIGH` nuqson**, 2026-08-10 da ataylab olib tashlangan | |
| C.6 | Количество устройств | Faol qurilmalar soni; `/devices` dagi son bilan **mos** | |
| C.7 | «Тариф и устройства» bloki | Joriy tarif + qurilmalar, TZ §3.2 tartibida | |
| C.8 | Kirish kartochkalari | Statistика / Финансы / Speed Test ga havolalar ishlaydi | |
| C.9 | Bo'sh holat | Tarif yo'q bo'lsa — halol bo'sh holat, `0` yoki `—` emas | |

---

## D. Tariflar `/tariffs` (TZ §3.2, §13) — 💰 pul

| # | Qadam | Kutilgan | V |
|---|---|---|---|
| D.1 | Ro'yxat ochiladi | O'tish mumkin bo'lgan tariflar; har birida **narx + birlik** («355 000 so'm»), yalang'och raqam emas | |
| D.2 | Tarif shartlari | Tezlik / muddat / hajm ko'rinadi (`3 Mbit/s · 22 kun` — **kichik harf**) | |
| D.3 | «Сменить тариф» bosiladi | **Tasdiqlash oynasi ochiladi** (TZ §13) — tarif nomi va narxi oynada aniq yozilgan | |
| D.4 | Cancel | Hech narsa o'zgarmaydi | |
| D.5 | Tasdiqlash 💰 | So'rov billingga ketadi, muvaffaqiyat xabari chiqadi. **Oldin/keyin qiymatlar yoziladi** | |
| D.6 | Tasdiqdan keyin «Следующий тариф» | **BLOCKED** — billing navbatdagi tarifni qaytarmaydi (`MISSING_APIS.md`). Joriy tarif faol qolishi kerak | |
| D.7 | GET orqali urinish | `/tariffs/connect` faqat POST; GET → 405. CSRF'siz POST → 419 | |
| D.8 | История смены тарифов (TZ §12) | **BLOCKED** — `/tariff/history` endpoint yo'q | |

---

## E. Qo'shimcha qurilmalar `/devices` (TZ §4, §13) — 💰 pul

| # | Qadam | Kutilgan | V |
|---|---|---|---|
| E.1 | Ulangan qurilmalar soni | `/` dagi son bilan mos | |
| E.2 | Qurilmalar jadvali | Har bir qurilma ko'rinadi | |
| E.3 | Ulanish narxi | `connect_cost = -1` bo'lsa — **narx ko'rsatilmaydi**, `-1` ekranga chiqmaydi | |
| E.4 | «Подключить» bosiladi | Forma/tasdiqlash ochiladi | |
| E.5 | Tasdiqlash 💰 | Qurilma qo'shiladi, **soni yangilanadi** (TZ §13). Oldin/keyin yoziladi | |
| E.6 | Limitdan oshirish | Billing rad etsa — xato matni halol ko'rsatiladi, oq sahifa emas | |
| E.7 | O'chirish | `POST /devices/delete/{permitId}` — tasdiqlash so'raladi, ⚠️ native `confirm()` **bo'lmasligi** kerak | |

---

## F. Statistika `/statistics` (TZ §5)

| # | Tekshiruv | Kutilgan | V |
|---|---|---|---|
| F.1 | Davr maydonlari | `start` va `end` (`type=date`), «Сформировать» tugmasi | |
| F.2 | Davr tanlanadi | AJAX — **sahifa qayta yuklanmaydi** (TZ §1.1), faqat blok almashadi | |
| F.3 | Входящий / Исходящий | MB/GB birligi bilan | |
| F.4 | Sessiyalar jadvali | Sana, davomiylik, hajm | |
| F.5 | 12 oydan uzun oraliq | Qirqiladi va **ochiq aytiladi** (`Period::MAX_MONTHS`), jimgina emas | |
| F.6 | Bir oy tushib qolsa | «Ba'zi oylar uchun ma'lumot olinmadi» — butun sahifa 502 bo'lmaydi | |
| F.7 | `end < start` | Validatsiya xatosi, oq sahifa emas | |
| F.8 | Bo'sh davr | Bo'sh holat matni **mavjud** boshqaruvga ishora qiladi («davr maydonlari»), o'chirilgan «oy tugmasi» ga emas | |
| F.9 | JS o'chirilganda | Forma oddiy POST qiladi va blok qaytadi (progressive enhancement) | |
| F.10 | Eski URL `/traffic/detail` | `/statistics` ga redirect | |

---

## G. Moliyaviy statistika `/finance` (TZ §6)

| # | Tekshiruv | Kutilgan | V |
|---|---|---|---|
| G.1 | Jadval ustunlari | Дата · Платежная система · Сумма · Статус | |
| G.2 | Sana formati | **дд.мм.гггг** (TZ §11) — uchala tilda ham | |
| G.3 | Summa formati | `120 000 сум` — birlik bilan, probel bilan ajratilgan | |
| G.4 | Manfiy summa | Haqiqiy minus `−`, defis `-` emas — **jadvalda ham, umumiy summada ham** | |
| G.5 | «Оплачено» rangi | ✅ **Yashil** — neytral kulrang emas. Bu 2026-08-10 da topilgan `HIGH` nuqson edi | |
| G.6 | G.5 uchala tilda | `to'langan` (uz, 4 xil apostrof), `оплачено` (ru), `paid` (en) — **hammasi yashil** | |
| G.7 | «To'lanmagan» | ⚠️ **Yashil bo'lmasligi kerak** — inkor shaklni muvaffaqiyat deb ko'rsatish pul jihatidan zarar | |
| G.8 | Boshqa statuslar | ожидает / ошибка / отменено — TZ §6 bo'yicha o'z tonida | |
| G.9 | Saralash | Har bir ustun asc/desc, `aria-sort` to'g'ri; summa **matn bo'yicha emas, `data-value` bo'yicha** saralanadi | |
| G.10 | Qidiruv | «Payme» → faqat mos qatorlar | |
| G.11 | Pagination | «1 / N» to'g'ri, oxirgi sahifa ishlaydi | |
| G.12 | Eksport/Chop etish | Toolbar'da `data-table-print` bor. ⚠️ **Tekshirilsin:** `BAJARILMAGAN_TASKLAR.md` §D CSV eksportini «bajarildi» deydi, `table.js` da esa faqat `print` bor — hujjat va kod **mos emas**. Qaysi biri to'g'ri ekani hisobotda aytiladi | |
| G.13 | Chop etishda | Barcha filtrlangan qatorlar chiqadi, faqat joriy 10 tasi emas | |
| G.14 | Filtr davr bo'yicha | F.1–F.7 bilan bir xil qoidalar | |
| G.15 | Eski URL `/payment/history` | `/finance` ga redirect | |

---

## H. Speed Test, Loyallik, Aksiyalar, Chat (TZ §7–§10) — `/services`

| # | Blok | Kutilgan | V |
|---|---|---|---|
| H.1 | Speed Test | `SOLA_SPEEDTEST_URL` (default `sola.speedtestcustom.com`) — kartochka ko'rinadi, havola ochiladi | |
| H.2 | Программа лояльности (§8) | **BLOCKED + N/A** — `/loyalty/info` endpoint yo'q; `SOLA_LOYALTY_URL` `.env` da o'rnatilmagan → kartochka **yashirin**. O'lik havola chiqmasligi kerak | |
| H.3 | Акции / скидки (§9) | **BLOCKED + N/A** — `/abonent/discounts` yo'q; `SOLA_PROMO_URL` o'rnatilmagan | |
| H.4 | Онлайн-чат (§10) | **N/A** — `SOLA_CHAT_URL` o'rnatilmagan. URL berilsa kartochka o'zi paydo bo'lishi kerak | |

> H.2–H.4: kartochkaning **yo'qligi** — bu nuqson emas, konfiguratsiya. Nuqson bo'ladigan holat:
> URL o'rnatilmagan bo'lsa ham kartochka chiqib, hech qayerga olib bormasa.

---

## I. Umumiy talablar (TZ §11)

| # | Tekshiruv | Kutilgan | V |
|---|---|---|---|
| I.1 | Sana formati | `дд.мм.гггг` — **hamma sahifada**, hamma tilda | |
| I.2 | Pul formati | `120 000 сум` — hamma joyda bir xil | |
| I.3 | Valyuta yozuvi | `so'm` / `сум` / `sum` — **kichik harf**, gravis (`` ` ``) emas apostrof | |
| I.4 | Jadvallar | Saralash + qidiruv + pagination — uchala jadvalda ham | |
| I.5 | **390px: gorizontal scroll yo'q** | `document.scrollWidth === document.clientWidth` — **har bir sahifada**. FAIL = `HIGH` | |
| I.6 | 390px: kartochkalar vertikal | Info-kartochkalar ustma-ust | |
| I.7 | 390px: jadval → kartochka | `u-table-cards`; har katak `data-label` bilan o'z sarlavhasini ko'rsatadi | |
| I.8 | 390px: tugmalar | To'liq kenglikda | |
| I.9 | Firma ranglari (§1.1) | To'q kulrang fon · yashil aksentlar · oq info-bloklar | |
| I.10 | AJAX (§1.1) | Davr formalari sahifani qayta yuklamaydi | |

---

## J. Matritsa — har bir sahifa 4 o'q bo'yicha

Funksional o'tish bir tilda + bir mavzuda. Keyin **ma'lumot ko'rsatuvchi** ekranlar
(`/`, `/finance`, `/statistics`, `/tariffs`) qolgan kombinatsiyalarda qayta ko'riladi.

| Sahifa | uz/dark | ru/dark | en/dark | uz/light | ru/light | en/light | 390px |
|---|---|---|---|---|---|---|---|
| `/` | | | | | | | |
| `/tariffs` | | | | | | | |
| `/devices` | | | | | | | |
| `/statistics` | | | | | | | |
| `/finance` | | | | | | | |
| `/services` | | | | | | | |

Qo'shimcha, har bir katak uchun:
- **Konsol xatolari** — 404 asset ham nuqson
- **Tarjimasiz matn** — lekin manbani ayting: billingdan kelgan matn (`касса`, `Smart 300 - 355 000 сум`) **tarjima nuqsoni emas**, u ma'lumot
- **Matn hajmi** (`sola-text` radio) — kattalashtirilganda tartib buzilmasin

---

## K. Bloklanganlar — nuqson sifatida yozilmaydi

Quyidagilar **billing tomonda**, kabinetda emas. Ekranda halol bo'sh holat ko'rinishi — **to'g'ri xatti-harakat**:

| TZ | Nima yo'q | Manba |
|---|---|---|
| §2, §12 | `contract_number` | `MISSING_APIS.md` Task 1.1 |
| §3.1, §13 | `next_charge_date` | Task 2.1 — hisoblab chiqarish **taqiqlangan** |
| §3.2 | `next_tariff_name`, `next_tariff_cost` | Task 2.2 |
| §3.2 | `curr_tariff_cost` | Task 2.2 |
| §5, §6 | `date_from`/`date_to` (oy-bazali API) | Task 3.1, 3.2 |
| §6 | `payment_status_code` (status matni tarjima qilinadi) | Task 3.2 |
| §8 | `/loyalty/info` | Task 4.2 |
| §9 | `/abonent/discounts` | TZ §9 |
| §12 | `/tariff/history` | TZ §12 |

⚠️ Aksincha: bu maydonlardan birortasi **to'ldirilgan** ko'rinsa (ayniqsa C.5 — hisobdan yechish sanasi),
bu `HIGH` nuqson — kimdir taxminiy qiymat hisoblab qo'ygan.

---

## Hisobot shakli

1. **Qamrov jadvali** — yuqoridagi har bir `#` uchun verdikt
2. **Nuqsonlar** — `F-N · SEVERITY · URL · til · mavzu · viewport` + Ko'rilgani / Kutilgani / Skrinshot / Egasi
3. **State changes** — 💰 belgili har bir bajarilgan harakat, oldin/keyin qiymatlari bilan
4. **Tekshirilmagani va sababi** — bo'sh qolmaydi

Sessiya izohi: `docs/forge-qa-browser/<YYYY-MM-DD_HH-MM>_<task>.md`
