# Logotip — plastinka olib tashlandi, mavzuga qarab fayl almashadi

- **Sana:** 2026-08-13 16:19
- **Rejim:** SaaS product + Multi-market · mijozning **o'z** logotipi
- **SCOPE:** frontend · **RISK:** false
- **Natija:** `php artisan test` → **85 passed** · Pint → passed

## 1. Muammo

Logotip qorong'i plastinka ustida turardi (`.u-logo { background: var(--c-meter-bg) }`).
Sababi komponent izohida yozilgan edi: qo'ldagi ikkala fayl ham **oq siyoh**
bilan chizilgan (`logo-wordmark.png` — oq "SOLA"), ya'ni yorug' kartochkada
ko'rinmay qolardi.

Ya'ni plastinka — noto'g'ri faylni yashirish uchun qo'yilgan yamoq edi. Sahifada
boshqa hech bir element plastinkada turmaydi, shuning uchun u stiker kabi
o'qilardi.

## 2. Yechim

Mijoz `public/img/logo-dark.png` ni berdi — bu **qora siyohli** lokap, aynan
yorug' fon uchun chizilgan. Endi fon bo'yalmaydi, **fayl almashadi**:

| Mavzu | Fayl | O'lcham |
|---|---|---|
| Yorug' | `logo-dark.png` (qora "SOLA" + Wi-Fi Operator) | 341×91 |
| Qorong'i | `logo-wordmark.png` (oq "SOLA") | 166×53 |

Almashtirish **CSS orqali**, uch holatli qoida bilan — stylesheet'ning qolgan
qismi ranglar uchun ishlatadigan naqshning aynan o'zi:

```css
.u-logo-on-dark { display: none; }                      /* yorug' — sukut */
@media (prefers-color-scheme: dark) {
    :root:not([data-theme='light']) .u-logo-on-light { display: none; }
    :root:not([data-theme='light']) .u-logo-on-dark  { display: block; }
}
:root[data-theme='dark'] .u-logo-on-light { display: none; }
:root[data-theme='dark'] .u-logo-on-dark  { display: block; }
```

Nega `<picture media="(prefers-color-scheme: dark)">` emas: u tizim
sozlamasini o'qiydi, lekin kabinetdagi **aniq mavzu tanlovini** (`data-theme`)
bilmaydi. Foydalanuvchi qo'lda yorug'ni tanlasa, `<picture>` baribir qorong'i
variantni berardi.

Ikkala `<img>` ham to'g'ri `alt` bilan qoladi — `display:none` ko'rinmayotganini
a11y daraxtidan chiqaradi, shuning uchun takror o'qilmaydi.

## 3. Mobil variant olib tashlandi

Ilgari `sm` dan past ekranda faqat qush (`logo-bird.png`) ko'rsatilardi —
"to'liq lokap menyu tugmasi va hisob chipiga joy qoldirmaydi" deb.

O'lchab ko'rildi: **to'liq lokap sig'adi.** 320px'da logotip 135px egallaydi,
menyu va sozlama tugmalari bilan birga gorizontal siljish yo'q. Shuning uchun
telefonda ham to'liq brend ko'rinadi.

## 4. Tekshirildi

| Kenglik | Mavzu | Sahifa | Natija |
|---|---|---|---|
| 320 | yorug' / qorong'i | `/` | to'g'ri fayl, overflow yo'q |
| 320 | yorug' | `/auth/login` (h-10, 150px) | qator o'raladi, overflow yo'q |
| 360 | yorug' / qorong'i | `/` | to'g'ri fayl |
| 1440 | yorug' | `/` | to'g'ri fayl |

`.u-logo` foni: `rgba(0, 0, 0, 0)` — plastinka yo'q.

## 5. Qoldi

`logo-bird.png` va `bird.png` endi Laravel ilovasida **ishlatilmaydi**
(mobil variant olib tashlangani uchun). Lekin ular `design/` prototipida
hali ishlatiladi, shuning uchun **o'chirilmadi** — bu mijoz qarori.

`logo-mark.png` faqat CSS izohida eslatiladi (brend yashili qayerdan
o'lchangani), haqiqiy havola emas.
