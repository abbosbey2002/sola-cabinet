# Reja: harakatlar jurnali, statistika va tarif almashtirish tarixi (admin panel)

**Sana:** 2026-09-23 · **Versiya:** 2 (CEO javoblaridan keyin) · **Holat:** reja, kod yozilmagan
**RISK:** `true` (shaxsiy ma'lumot saqlanadi, auth, pul oqimi, admin rollari)

## 0. CEO qarorlari (2026-09-23)

| # | Savol | Qaror |
|---|---|---|
| 1 | "Odam" nima? | **Hisob (accId)** bo'yicha sanaladi |
| 2 | Log fayl kerakmi? | **Yo'q.** Hammasi faqat bazaga yoziladi va **admin panelda** ko'rinadi |
| 3 | Kim ko'radi? | 3 ta rol: **admin**, **analitik**, **sotuv menejeri** |
| 4 | Brauzer harakatlari | **Chop etish** va **qidiruv** yoziladi |
| 5 | Qancha ma'lumot saqlanadi | **IP va iloji boricha ko'p ma'lumot**, hash qilinmaydi |
| 6 | Tashqi analitika | **Kerak emas** |
| 7 | Tarif almashtirish | Adminda **alohida sahifa**. Hisobning barcha ma'lumoti, eski tarif, yangi tarif va **natija** (muvaffaqiyat, puli yetmadi va hokazo) saqlanadi |

### ⚠️ 5 va 7-qarorlarning oqibatlari

1. **`AGENTS.md` qoidasi o'zgaradi.** Hozir unda "Subscriber data is not stored here, SQLite faqat `admins`, `enabled_tariffs`" deyilgan. Endi abonent ma'lumoti saqlanadi, shuning uchun `AGENTS.md` va `.cursor/rules/architecture.mdc` yangilanadi. Qoida shunday bo'ladi: saqlangan ma'lumot **faqat tarix/audit uchun**, kabinetda abonentga ko'rsatiladigan raqamlar esa avvalgidek faqat billing API'dan real vaqtda olinadi.
2. **Qonun.** O'zbekistonning "Shaxsga doir ma'lumotlar to'g'risida"gi qonuni (ZRU-547) bo'yicha telefon, FIO va IP shaxsga doir ma'lumot hisoblanadi. Buning uchun (a) foydalanuvchi shartnomasi yoki maxfiylik siyosatida bu yozilgan bo'lishi, (b) ma'lumot O'zbekistondagi serverda turishi kerak. Bu **yuridik xulosa emas**: kompaniya yuristi tasdiqlashi kerak (12-bo'lim, 1-savol).
3. **Saqlash muddati** endi muhim, chunki ma'lumot cheksiz yig'ilmasligi kerak (12-bo'lim, 2-savol).
4. **Rollar bo'yicha kirish** majburiy bo'ladi, chunki sotuv menejeri va analitik telefon va FIO'ni ko'radi. Kim nimani ko'rishi 7-bo'limda.

## 1. Maqsad

1. Kabinetdagi **har bir menyu va action** bo'yicha: necha marta bajarilgan, **necha xil hisob** bajargan, nechtasi muvaffaqiyatli, nechtasi xato.
2. **Tarif almashtirish tarixi**: har bir urinish to'liq tafsilot bilan alohida admin sahifada.
3. **Hisob bo'yicha tarix**: bitta hisob kabinetda nima qilganini ko'rish (sotuv menejeri uchun).
4. Ma'lumot faqat **o'z serverimizda**, tashqi servislarga chiqmaydi.

## 2. Hozirgi holat (kodda tekshirilgan)

| Nima | Holat |
|---|---|
| Harakatlar yozuvi | Yo'q. Faqat `TopUpController:50` da fayl logi |
| DB | SQLite (`admins`, `enabled_tariffs`, lokal Telescope) |
| Adminlar | `admins(username, password)`, **rol yo'q**. `php artisan admin:create`, `AdminSeeder` |
| Admin panel | Faqat `/admin/tariffs` |
| Queue / cron | `sync`, serverda cron yo'q |
| Balans yetmasligi | Billing `/tariff/connect` → **code 129** "Баланс не позволяет изменить тариф". **`lang/*/errors.php` da 128–132 kodlari yo'q**, shuning uchun abonent noaniq xato ko'radi. Bu shu ish ichida tuzatiladi |
| Geo IP | `app/Support/IpLocation.php` bor (tashqi provayder orqali, `config/geoip.php`) |

## 3. Qism A: harakatlar jurnali (`activity_events`)

### 3.1 Event katalogi (yopiq ro'yxat)

**Menyu (sahifa ochish):**
`page.home`, `page.tariffs`, `page.devices`, `page.statistics`, `page.finance`, `page.services`, `page.topup`

**Server actionlari:**

| Event | Qayerda |
|---|---|
| `auth.sms_requested` | login POST (hisob hali yo'q, telefon yoziladi) |
| `auth.verified` | verify POST |
| `auth.account_selected` / `auth.account_switched` | hisob tanlash |
| `auth.logout` | chiqish |
| `tariff.connect` 💰 | tarif almashtirish (to'liq tafsiloti Qism B'da) |
| `device.add` 💰 / `device.delete` | qurilmalar |
| `topup.initiated` 💰 | iWon'ga yo'naltirish |
| `filter.statistics` / `filter.finance` | davr filtri |
| `locale.changed` | til almashtirish |
| `rate_limited` | 429 javobi |

**Brauzer actionlari (4-qaror):**

| Event | Qayerda | Qo'shimcha |
|---|---|---|
| `ui.print` | `/finance`, `/statistics` jadvali "PDF / Chop etish" | qaysi jadval, nechta qator |
| `ui.search` | jadval qidiruvi | **qidiruv matni**, natija soni. Har bir harf emas: 800 ms pauzadan keyin bitta yozuv |
| `ui.tariff_dialog_cancelled` | tarif oynasini yopish | tanlangan tarif. Konversiya uchun: nechtasi oynani ochib, almashtirmagan |
| `ui.device_dialog_cancelled` | qurilma tasdiqlashini bekor qilish | |

Brauzer eventlari `POST /activity` beacon orqali keladi: `abonent.verified`, CSRF, `throttle:60,1`, faqat katalogdagi nomlar qabul qilinadi, qolganlari 422 oladi. JS: `resources/js/modules/activity.js` (delegation, markup bo'lmasa hech narsa qilmaydi, loyiha uslubida).

### 3.2 Har bir eventda saqlanadigan ma'lumot (5-qaror: iloji boricha ko'p)

| Guruh | Maydonlar |
|---|---|
| Vaqt | `occurred_at` (UTC), `duration_ms` (so'rov qancha davom etgani) |
| Event | `event`, `outcome` (`ok`/`fail`/`denied`/`invalid`), `error_code`, `error_message` (billing `errMsg`) |
| Hisob | `account_id` (accId), `billing_login` (shartnoma №), `phone`, `full_name`, `abon_type`, `is_legal_entity` |
| Tarmoq | `ip`, `ip_forwarded` (`X-Forwarded-For` zanjiri), `geo_country`, `geo_city` (6-bo'limga qarang) |
| Qurilma | `user_agent`, `browser`, `browser_version`, `os`, `os_version`, `device_type` (mobile/tablet/desktop), `device_model` |
| Ekran (beacon'dan) | `screen_w`, `screen_h`, `viewport_w`, `theme` (light/dark/system), `text_size` |
| So'rov | `route`, `method`, `path` (query'siz), `http_status`, `referrer`, `session_id`, `request_id`, `locale` |
| Qo'shimcha | `meta` (json): eventga xos maydonlar (tarif id, summa, qidiruv matni, davr va hokazo) |

**Baribir saqlanmaydi:** SMS kodi, cookie qiymatlari, `X-Access-Token` va parollar, billing javobining to'liq tanasi. Karta ma'lumoti bizga umuman kelmaydi (iWon).

## 4. Qism B: tarif almashtirish tarixi (`tariff_changes`), alohida sahifa

Har bir "Сменить тариф" urinishi bitta qator bo'ladi, **natija qanday bo'lishidan qat'i nazar**: muvaffaqiyat, balans yetmadi (129), billing xatosi, bizning tekshiruvda rad etilgani (yuridik shaxs, ruxsatsiz tarif) va billing javob bermagani (503).

### 4.1 Maydonlar

| Guruh | Maydonlar |
|---|---|
| Qachon | `created_at` (UTC), `billing_duration_ms` |
| Hisob (o'sha paytdagi holat) | `account_id`, `billing_login` (shartnoma №), `phone`, `full_name`, `abon_type`, `is_legal_entity`, `account_status`, `address`, `email`, `contract_date` |
| Pul (o'sha paytda) | `balance_before` (so'm, `/abonent/info.saldo`), `next_charge_date` |
| **Eski tarif** | `old_tariff_id`, `old_tariff_name`, `old_tariff_price` (so'm), `old_tariff_connected_at` |
| **Yangi tarif** | `new_tariff_id`, `new_tariff_name`, `new_tariff_price` (so'm), `new_tariff_speed`, `new_tariff_period` |
| So'rov | `timing` (`now` / `month`), `effective_date` (billingga yuborilgan `tariff_conndate`) |
| **Natija** | `result`: `success` / `insufficient_funds` / `billing_error` / `denied` / `unavailable`. Qo'shimcha `error_code`, `error_message` (billing `errMsg` aynan), `denied_reason` (`legal_entity` / `not_allowed` / `not_permanent` / `not_in_available`) |
| Keyin | `balance_after`, `pending_tariff_after` (muvaffaqiyatdan keyingi `/abonent/info`. Faqat `success` da, bitta qo'shimcha o'qish so'rovi) |
| Kontekst | `ip`, `user_agent`, `device_type`, `locale`, `session_id` |
| Xom snapshot | `profile_snapshot` (json): o'sha paytdagi `/abonent/info` javobi to'liq. Kelajakda kerak bo'lib qoladigan maydon yo'qolmasligi uchun |

Pul birliklari `AGENTS.md` qoidasi bo'yicha: `saldo` va `tariff_price` so'mda keladi, `/tariff/available.cost` tiyinda keladi va saqlashdan oldin `/100` qilinadi. Barcha narxlar jadvalda **so'mda** saqlanadi.

### 4.2 Yozish tartibi (`TariffController@connect`)

1. Validatsiya o'tdi → `tariff_changes` qatori `result=pending` bilan yaratiladi (billingga yuborishdan **oldin**).
2. Rad etilsa (403/404) → `denied`, `denied_reason`.
3. Billing javob berdi → `success` yoki `error_code` bo'yicha: `129` → `insufficient_funds`, boshqa kodlar → `billing_error`.
4. Billing javob bermadi (`SolaUnavailableException`) → `unavailable`.
5. Hech bir qator `pending` holatida qolib ketmaydi: `finally` bloki bor, qolib ketganlarini esa `activity:cleanup` "noma'lum" deb belgilaydi.

Nega avval `pending`? Billing tarifni almashtirib, javob kelmay qolgan holatda ham urinish izi qoladi. Pul oqimi auditida bu eng muhim holat.

### 4.3 Admin sahifa `/admin/tariff-changes`

- **Ro'yxat:** sana, hisob (accId + shartnoma №), FIO, telefon, eski tarif → yangi tarif, narx farqi, timing, balans, **natija rangli belgi bilan** (✅ muvaffaqiyat, 💸 pul yetmadi, ❌ xato, 🚫 rad etildi, ⏳ javobsiz).
- **Filtrlar:** davr, natija, eski/yangi tarif, hisob raqami yoki telefon bo'yicha qidiruv, abonent turi.
- **Tafsilot sahifasi** `/admin/tariff-changes/{id}`: barcha maydonlar va `profile_snapshot`.
- **Xulosa kartochkalari:** davr ichida jami urinishlar, muvaffaqiyat foizi, "pul yetmadi" soni (sotuv uchun: kimga to'ldirishni eslatish mumkin), eng ko'p tanlangan tariflar, qaysi tarifdan qaysi tarifga ko'p o'tilgani (matritsa).
- **CSV eksport** (rolga qarab, 7-bo'lim).

## 5. Qism C: statistika sahifalari

| Sahifa | Mazmuni |
|---|---|
| `/admin/stats` | Davr filtri. **Menyu jadvali:** ochilishlar va unikal hisoblar. **Action jadvali:** urinishlar, unikal hisoblar, ✅/❌/🚫, muvaffaqiyat foizi. Chop etish va qidiruv sonlari. Kunlik grafik |
| `/admin/stats/funnel` | Login voronkasi: SMS so'raldi → tasdiqlandi → hisob tanlandi → bosh sahifa. Tarif voronkasi: `/tariffs` ochildi → oyna ochildi → bekor qilindi / yuborildi → muvaffaqiyat |
| `/admin/stats/segments` | Til, qurilma turi, brauzer, OS, abonent turi, shahar kesimlari |
| `/admin/stats/errors` | Billing `error_code` bo'yicha top xatolar, qaysi action'da |
| `/admin/accounts/{accId}` | **Hisob tarixi:** shu hisobning barcha eventlari va tarif almashtirishlari vaqt bo'yicha |
| `/admin/accounts` | Hisob yoki telefon bo'yicha qidiruv → hisob tarixi |

"Unikal hisob" = `COUNT(DISTINCT account_id)`, har doim xom jadvaldan tanlangan davr bo'yicha hisoblanadi, shuning uchun kunlik sonlarni qo'shib yuborish xatosi bo'lmaydi. Tezlik uchun `activity_daily` yig'indi jadvali bor: kunlik hits va uniques, kechasi cron bilan to'ldiriladi. Uzun davrlar uchun oylik yig'indi ham bo'ladi.

## 6. Geo (IP → shahar)

Mavjud `IpLocation` **tashqi provayderga** so'rov yuboradi. 6-qaror (tashqi analitika kerak emas) bilan ziddiyat bor. Taklif: **offline baza** (MaxMind GeoLite2 yoki DB-IP Lite `.mmdb` fayli serverda). IP tashqariga chiqmaydi va har bir so'rov sekinlashmaydi. Faylni oyiga bir marta yangilash kerak (devops). Bu qaror qabul qilinmaguncha `geo_*` maydonlari bo'sh qoladi (12-bo'lim, 5-savol).

## 7. Rollar (3-qaror)

`admins` jadvaliga `role` ustuni qo'shiladi: `admin` / `analyst` / `sales`. Mavjud adminlarning roli `admin` bo'ladi. `admin:create {username} --role=`.

| Imkoniyat | admin | analyst | sales |
|---|---|---|---|
| `/admin/stats*` (agregat statistika) | ✅ | ✅ | ✅ |
| `/admin/tariff-changes` ro'yxati | ✅ | ✅ | ✅ |
| Telefon va FIO'ni **to'liq** ko'rish | ✅ | ❌ niqoblangan `+998 90 *** ** 67` | ✅ |
| `/admin/accounts/{accId}` hisob tarixi | ✅ | ❌ | ✅ |
| IP, user-agent, `profile_snapshot` | ✅ | ✅ | ❌ |
| CSV eksport (shaxsiy maydonlar bilan) | ✅ | ✅ niqoblangan | ✅ |
| `/admin/tariffs` (tarif ruxsatlari) | ✅ | ❌ | ❌ |
| Adminlar va rollarni boshqarish | ✅ | ❌ | ❌ |

Bu **default taklif** (12-bo'lim, 3-savol). Tekshiruv Laravel `Gate` bilan server tomonda qilinadi. Blade'da tugmani yashirish faqat qulaylik uchun, `AGENTS.md` dagi "UI gate is a courtesy" qoidasi bilan bir xil.

Qo'shimcha: **admin harakatlari ham yoziladi** (`admin_audit`): kim qaysi sahifani ochdi, qaysi hisob tarixini ko'rdi, qanday CSV eksport qildi. Shaxsiy ma'lumotga kirish izsiz bo'lmasligi kerak.

## 8. Arxitektura

```
Brauzer ──(print/search/cancel)──► POST /activity ─┐
So'rov ──► RecordPageView middleware ──────────────┤
Controller ──► ActivityRecorder::record() ─────────┼─► defer() ─► activity_events
TariffController ──► TariffChangeRecorder ─────────┴───────────► tariff_changes
Admin so'rovlari ──► AdminAudit middleware ────────────────────► admin_audit
Cron (kechasi) ──► activity:rollup ─► activity_daily / activity_monthly
               └─► activity:prune  (saqlash muddati o'tganlarni o'chiradi)
```

| Fayl | Vazifa |
|---|---|
| `app/Support/Activity/ActivityEvent.php` | Event katalogi (enum) |
| `app/Support/Activity/RequestContext.php` | IP, UA, qurilma, geo va sessiyani bir joyda yig'adi (UA parser: `jenssegers/agent` yoki `whichbrowser`, lokal kutubxona) |
| `app/Support/Activity/ActivityRecorder.php` | `record()`, `AbonentSession` va `AbonentProfile` dan hisob maydonlarini oladi |
| `app/Support/Activity/TariffChangeRecorder.php` | `pending` → yakuniy natija (4.2) |
| `app/Http/Middleware/RecordPageView.php` | Menyu GET'lari |
| `app/Http/Controllers/ActivityBeaconController.php` + `ActivityBeaconRequest` | Brauzer eventlari |
| `app/Http/Controllers/Admin/{Stats,TariffChange,Account}Controller.php` | Admin sahifalar |
| `app/Policies` / `Gate` | Rollar |
| `resources/js/modules/activity.js` | Beacon (`navigator.sendBeacon`) |

**Tamoyillar:**
- **Yozuv abonentni sekinlashtirmaydi va sindirmaydi.** Yozuv `defer()` bilan javobdan keyin bajariladi va `try/catch` ichida bo'ladi. Istisno: `tariff_changes` ning `pending` qatori sinxron yoziladi, chunki u pul auditi.
- **Profil qayta so'ralmaydi.** Controller allaqachon `/abonent/info` ni o'qigan, recorder o'sha javobni ishlatadi. Qo'shimcha billing so'rovi faqat tarif muvaffaqiyatli almashgandan keyin bitta.
- **Controller'lar ingichka qoladi**: har bir action'ga bitta qator qo'shiladi.

## 9. Ma'lumotlar bazasi

**SQLite yetadimi?** Har bir sahifa ochilishida bitta yozuv bo'ladi. Kuniga ~50 ming eventgacha SQLite (WAL rejimi) bemalol ishlaydi. Undan ko'p bo'lsa yoki analitiklar og'ir so'rovlar yuritsa, **MySQL yoki PostgreSQL** kerak bo'ladi. Buning uchun kunlik faol abonentlar sonini bilishimiz kerak (12-bo'lim, 4-savol). Model'lar alohida `activity` connection'dan foydalanadi, shuning uchun keyin ko'chirish faqat `.env` o'zgarishi bo'ladi.

**Jadvallar:** `activity_events`, `tariff_changes`, `activity_daily`, `activity_monthly`, `admin_audit` va `admins.role` ustuni.

**Indekslar:** `activity_events(occurred_at)`, `(event, occurred_at)`, `(account_id, occurred_at)`, `(phone)`. `tariff_changes(created_at)`, `(account_id)`, `(result, created_at)`.

## 10. Bosqichlar

| # | Bosqich | Mas'ul (Forge) |
|---|---|---|
| 0 | Yurist xulosasi (ZRU-547), maxfiylik siyosatiga matn | CEO / yurist |
| 1 | `AGENTS.md` va cursor qoidalarini yangilash, migratsiyalar, `admins.role`, `admin:create --role` | `forge-senior-backend-engineer` |
| 2 | `RequestContext`, `ActivityRecorder`, menyu middleware'i, server actionlari | `forge-senior-backend-engineer` |
| 3 | `tariff_changes` va `TariffChangeRecorder`, `errors.php` ga 128–132 kodlari (ru/uz/en) | `forge-senior-backend-engineer` |
| 4 | `POST /activity` beacon va `activity.js` (print, search, bekor qilish) | `forge-senior-backend-engineer` |
| 5 | Rollar (`Gate`), `admin_audit`, admin controller'lar va querylar | `forge-senior-backend-engineer` |
| 6 | Admin sahifalar UI: stats, tariff-changes, accounts | `forge-frontend-design` |
| 7 | Cron (`schedule:run`), rollup/prune, GeoLite yangilanishi, DB qarori | `forge-devops-engineer` |
| 8 | Testlar | `forge-test-writer` |
| 9 | Yakuniy gate: `forge-code-reviewer` va `forge-security-auditor` (parallel) | agentlar |
| 10 | Brauzerda QA (roller bo'yicha kirish, tarif oqimi 129 bilan) | `forge-qa-browser` |

**Tavsiya etilgan tartib:** avval 1 → 3 → 5 (faqat tarif sahifasi) → 6 (faqat tarif sahifasi). Tarif almashtirish tarixi eng qimmatli va eng aniq qism, uni birinchi bo'lib chiqarish mumkin. Keyin statistika (2, 4, qolgan 5–6).

## 11. Testlar (asosiylari)

- Tarif: `success`, `129 → insufficient_funds`, boshqa kod → `billing_error`, 403 → `denied` + sabab, `SolaUnavailableException` → `unavailable`. Har biri **bitta** qator va `pending` qolmaydi.
- Eski va yangi tarif narxlari **so'mda**: `cost` tiyin → `/100`, `tariff_price` o'zgarishsiz.
- Bitta hisob 5 marta harakat qilsa: `hits=5`, `uniques=1`. Bitta telefonda 2 ta hisob bo'lsa: `uniques=2`.
- Beacon: katalogdan tashqari nom → 422, CSRF'siz → 419, mehmon → redirect, throttle → 429.
- Rollar: analitik `/admin/accounts/*` da 403 oladi va telefonni niqoblangan ko'radi. Sotuv menejeri `/admin/tariffs` da 403 oladi.
- Recorder xatosi (DB qulflangan) kabinet sahifasini 500 qilmaydi.
- SMS kodi va token hech bir jadvalga tushmaydi.
- Rollup idempotent. Prune faqat muddati o'tganlarni o'chiradi, `tariff_changes` ga tegmaydi.

## 12. Ochiq savollar (default javob bilan)

| # | Savol | Default |
|---|---|---|
| 1 | Yurist ZRU-547 bo'yicha tasdiqladimi? Maxfiylik siyosatiga matn kim yozadi? | Kod yoziladi, lekin **production'ga yoqish** yurist "ha" degandan keyin (`ACTIVITY_ENABLED` flag) |
| 2 | Saqlash muddati | `activity_events`: **12 oy**. `tariff_changes` va `admin_audit`: **3 yil** (pul auditi). Yig'indilar: muddatsiz |
| 3 | Rollar jadvali (7-bo'lim) to'g'rimi? | Yuqoridagi jadval |
| 4 | Kuniga taxminan nechta faol abonent bor? (SQLite yoki MySQL) | <5 000 bo'lsa SQLite, ko'p bo'lsa MySQL |
| 5 | Geo: offline GeoLite2 bazasi mumkinmi? | Ha, offline baza. Tashqi provayder ishlatilmaydi |
| 6 | Qidiruv matni to'liq saqlansinmi? (unda abonentning to'lov ID'lari bo'lishi mumkin) | Ha, 100 belgigacha |
| 7 | Adminlar ro'yxatini boshqarish UI'da kerakmi yoki `artisan` yetarlimi? | Birinchi bosqichda `artisan`, UI keyin |

## 13. Scope'dan tashqari

- Real vaqt dashboard.
- Log fayllar (2-qaror).
- Tashqi analitika (6-qaror).
- Statistikadan abonentga avtomatik SMS yoki qo'ng'iroq yuborish (masalan "pul yetmadi" bo'lganlarga). Sotuv menejeri ro'yxatni qo'lda ishlatadi, avtomatlashtirish keyinroq alohida vazifa.
- Kabinet ekranlarida saqlangan ma'lumotni ko'rsatish. Abonent avvalgidek faqat billing'dan real vaqtda keladigan raqamlarni ko'radi.
