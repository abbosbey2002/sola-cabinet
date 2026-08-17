# CabinetTest — sahifa bo'linishi va GET→POST

- **Sana:** 2026-08-10 12:45
- **Fayl:** `tests/Feature/CabinetTest.php`

## Nima qamrab olindi

Kabinet to'rtta sahifaga bo'lingani va uchta pul amali POST ga o'tgani
uchun oltita test o'zgargan shartnomaga moslandi, oltitasi yangi yozildi.

**Moslashtirilgan:** bosh sahifa endi MAC ro'yxatini ko'rsatmaydi (u
`/devices` da); navigatsiya sinovi oltita bo'limni aylanadi; trafik ulushi
endi `width: 50%` chiziq emas, 240° yoyning 120° segmenti; joriy tarif
`/tariffs` da; ulash narxi `/devices` da; `/tariffs` endi yo'naltirish emas,
sahifa.

**Yangi:**

| Test | Nimani ushlaydi |
|---|---|
| `the_money_spending_actions_cannot_be_triggered_by_a_link` | Uchala amal GET bilan yetib bo'lmaydi (405), eski URL shakli 404, va billingga hech narsa ketmagan |
| `an_unknown_tariff_timing_never_reaches_billing` | `timing` marshrut segmenti emas, tana maydoni bo'lgach validatsiya o'sha yukni ko'taradimi |
| `a_tariff_switch_without_a_selection_is_rejected` | Tarif tanlanmasdan yuborilgan forma |
| `the_chosen_timing_decides_the_connection_date_sent_to_billing` | `now` → bugun, `month` → keyingi oyning 1-sanasi. Xato bo'lsa abonent bir oy erta hisobdan yechiladi |
| `the_day_meter_counts_the_days_to_the_next_charge` | 31 ta chiziq, bittasi "bugun", bittasi "yechim", 7 tasi o'tgan |
| `no_charge_date_means_no_meter_rather_than_an_invented_one` | Billing sana bermasa o'lchagich umuman chizilmaydi |
| `a_device_permit_is_released_and_the_subscriber_is_told` | To'g'ri `permit_id` ketdi va abonentga xabar berildi |

## Qizil→yashil isbot

`routes/web.php` da `/devices/add` vaqtincha GET ga qaytarildi:

```
--- GET holatida:  ⨯ the money spending actions cannot be triggered by a link (1 failed)
--- qaytarilgach:  ✓ the money spending actions cannot be triggered by a link (5 assertions)
```

## Ataylab qamrab olinmagan

- **CSRF 419** — Laravel test yugurtirgichni `VerifyCsrfToken` dan ozod qiladi,
  shuning uchun feature testda 419 ni ko'rsatib bo'lmaydi. Tuzatishning
  strukturaviy yarmi (GET bilan umuman yetib bo'lmasligi) qamrab olindi;
  419 ning o'zi brauzerda curl bilan qo'lda tasdiqlandi.
- **Doimiy bo'lmagan abonent tarifni almashtira oladimi** — serverda hech
  qachon tekshirilmagan (ilgari ham), bu mening o'zgartirishim emas. Auditorga
  yo'naltirildi.

## Darvozadan keyin qo'shilgan testlar

`tests/Unit/ChargeCycleTest.php` (yangi, 7 ta test) — kun o'lchagichi
arifmetikasi: oy oxiri toshib ketishi, fevral va kabisa fevrali, boshlanmagan
davr, yechim kunining o'zi, muddat o'tgani.

`CabinetTest` ga uchta ruxsat testi: vaqtinchalik abonent qurilma ruxsatini
sotib ololmasligi, tarifni almashtira olmasligi, va taklif qilinmagan tarif
id si rad etilishi — uchalasida ham billingga hech narsa yuborilmagani
tekshiriladi.

## Qizil→yashil isbot (2)

```
subMonthNoOverflow() → subMonth():
  ⨯ a month end charge date does not overflow into the short month
    -'2026-02-28'  +'2026-03-03'
  qaytarilgach: ✓ (3 assertions)

DeviceController dan abort_unless olib tashlanganda:
  ⨯ a temporary subscriber cannot buy a device permit (1 failed)
  qaytarilgach: ✓ (5 assertions)
```

## Natija

```
php artisan test
Tests:  67 passed (324 assertions)
```

Vaqt muzlatiladi (`Carbon::setTestNow`) — kun o'lchagichi ham, ulash sanasi
ham "bugun" dan hisoblanadi; `tearDown` da bo'shatiladi.
