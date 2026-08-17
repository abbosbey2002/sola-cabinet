# `abonType` — doimiy abonentning bir qismi o'z hisobidan mahrum edi

- **Sana:** 2026-08-13 15:17
- **SCOPE:** backend · **RISK:** false — ruxsat kengaydi, lekin billing baribir
  o'z tekshiruvini qiladi va kontrollerlar pul sarflashdan oldin qayta so'raydi
- **Natija:** `php artisan test` → **85 passed (379 assertions)** · Pint → passed

## 1. Mijoz aytgan gap

> "bir martalik, yuridik yoki jismoniyga"

Ya'ni billing abonent turlari: **vaqtinchalik**, **bir martalik**, va doimiy —
u esa **yuridik shaxs** va **jismoniy shaxs** ga bo'linadi. Doimiy abonent
**bitta qiymat emas**.

## 2. Xato

Yangi kod ruxsatni qat'iy tenglik bilan tekshirardi:

```php
public function isPermanent(): bool
{
    return $this->abonentType() === self::TYPE_PERMANENT;   // === 2
}
```

Blade'larda ham xuddi shu literal ikki marta takrorlangan:
`$isPermanent = (int) $abonentType === 2;`

Agar yuridik/jismoniy `2` va `3` bo'lsa, ulardan **bittasi butunlay**
qurilma boshqaruvi va tarif almashtirishdan mahrum.

Eng yomoni — ziddiyat ko'rinib turardi. `x-account-type` komponenti
**hech qachon** `== 2` ishlatmagan, u `default =>` bilan yozilgan:

```php
0 => tempary,  1 => one_time,  default => current
```

Ya'ni abonent ismi yonida **"Doimiy"** yozuvi turardi, sahifa esa uni rad
etardi.

## 3. Nega bunday bo'lgan

Eski Laravel 5.8 ilovasi buni **to'g'ri** qilgan —
`resources/views/desktop/auth/select_account.blade.php` da:

```php
@if($account['abonType'] == 0)      → tempary
@elseif($account['abonType'] == 1)  → one_time
@else                               → doimiy     ← ochiq uchi
```

Qayta yozishda `@else` ning ochiq uchi `=== 2` ga aylanib qolgan. Komponent
eski mantiqni saqlab qolgan, ruxsat tekshiruvi esa yo'qotgan.

## 4. Tuzatish

`isPermanent()` endi **cheklangan turlar ro'yxati** bo'yicha ishlaydi, tenglik
bo'yicha emas:

```php
return $type !== null && $type >= self::TYPE_PERMANENT;
```

Cheklangan ikkitasi billingning o'ziniki: u ular uchun `121` "Временным не
доступно" va `122` "Временным и разовым не доступно" qaytaradi.

Blade'lardagi ikki nusxa olib tashlandi — `$isPermanent` endi view composer
orqali keladi (`AppServiceProvider`), qoida bitta joyda: `AbonentSession`.

`null` (turi yo'q eski sessiya) — abonent turi emas, shuning uchun **yopiq**
holatda qoladi.

## 5. Test

`CabinetTest::a_permanent_subscriber_is_not_only_type_two` — `2`, `3`, `4`
turlari uchun tarif ulash va qurilma qo'shish.

Test haqiqatan bugni ushlashi tekshirildi: eski shart qaytarilganda **yiqiladi**,
yangisida o'tadi.

## 6. Qoldi

**Qaysi raqam yuridik, qaysi biri jismoniy — noma'lum.** Tuzatish bunga bog'liq
emas (`≥ 2` ikkalasini ham qamraydi), lekin agar kabinet ularni ajratib
ko'rsatishi kerak bo'lsa — masalan "Yuridik shaxs" / "Jismoniy shaxs" nishoni —
aniq moslik kerak. SOLA'ga yuboriladigan xatga savol qo'shildi.

Kuzatish yo'li: `/identify` javobidagi `accs[].abonType` ni ma'lum turdagi
hisoblarda solishtirish.
