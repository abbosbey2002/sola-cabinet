/* ===========================================================================
   Sola Kabinet — dizayn tokenlari
   ---------------------------------------------------------------------------
   BU FAYL — CSS. U .js kengaytmasida turadi faqat bitta sabab bilan:
   @tailwindcss/browser (prototip uchun ishlatiladigan CDN quruvchi) tashqi
   .css faylni o'qimaydi, u faqat sahifa ichidagi
   <style type="text/tailwindcss"> blokini ko'radi. Shuning uchun quyidagi
   satrlar sahifa tahlil qilinayotgan paytda o'sha blok sifatida joylanadi.

   ILOVAGA KO'CHIRISH: pastdagi ikkita teskari apostrof orasidagi hamma narsa
   — sof CSS. Uni to'g'ridan-to'g'ri resources/css/app.css ga ko'chiring
   (batafsil shartlar HANDOFF.md §1 da).
   =========================================================================== */

const TOKENS = `
/* ===========================================================================
   Sola Kabinet — dizayn tokenlari
   ---------------------------------------------------------------------------
   BITTA OHANG — LOGOTIPNIKI. Logotipning yashili o'lchab olindi:
   #8FD400 = OKLCH(0.793 0.211 129.4°). Sahifadagi HAR BIR yashil — shu
   129.4° ning yorug'lik bo'yicha bir pog'onasi, ya'ni "logotipning o'zi,
   qoraytirilgani". Oldingi #087d20 (144.8°) va undan avvalgi firuza
   (176.7°) logotipdan chetda edi — ko'z ularni brend rangi deb tanimasdi.

   Qiziq tomoni: 129.4° chizig'ida eng to'yingan rang aynan L=0.79 da
   uchraydi va u — #8FD400 ning o'zi. Ya'ni logotip o'z ohangining eng
   yuqori nuqtasida turibdi; qolgan hamma narsa undan pastga tushadi.

   Logotip yashili — GRAFIK rang: oq ustida 1.81:1, hech qachon matn
   ko'tarmaydi. U faqat to'ldirish sifatida, qora yuzada (10.02:1)
   ishlatiladi. Yorug' rejimda o'qiladigan yashil — --c-action #4A7100
   (5.75:1). Qorong'i rejimda esa logotipning o'z yashili matn bo'la oladi:
   --c-action #90D600 (9.27:1) — bu deyarli #8FD400 ning o'zi.

   Bu redizaynning asosiy talabi — yoshi katta va turli sharoitdagi
   foydalanuvchi qiynalmasin:
     · ildiz o'lchami 118.75% (≈19px) + foydalanuvchi uchun uch bosqichli
       "Matn hajmi" boshqaruvi — foizda, ya'ni brauzerning o'z sozlamasi
       ustidan yozilmaydi;
     · hech bir matn 1rem dan kichik emas (uppercase mayda yorliqlar
       butunlay olib tashlandi — ular kirill yozuvida ayniqsa yomon o'qiladi);
     · asosiy matn kontrasti AAA (7:1) atrofida, ikkilamchi matn ham 6.5:1 dan
       past emas;
     · holat faqat rang bilan emas, rang + ikonka + so'z bilan beriladi;
     · bosiladigan joylar kamida 48px.
   =========================================================================== */

:root {
    /* Ground & surfaces — neytral kulrang emas, logotip ohangiga bo'yalgan */
    --c-bg: #f1f6ed;
    --c-surface: #ffffff;
    --c-surface-2: #e3ebdc;
    --c-line: #d1dcc7;          /* bezak chizig'i */
    --c-line-strong: #778966;   /* forma elementlari gardishi — 3.78:1 */

    /* Text */
    --c-ink: #141d09;           /* 17.4:1 oq ustida */
    --c-muted: #505b46;         /* 7.2:1 oq ustida — ikkilamchi matn ham AAA */

    /* Asosiy rang — logotip yashilining L=0.50 pog'onasi. Ikkala mavzuda ham
       AYNAN bir xil: to'q yashil fon, oq yozuv (5.75:1). Abonent tugmani
       mavzudan qat'i nazar bir xil taniydi. */
    --c-primary: #4a7100;
    --c-primary-hover: #3b5d00;
    --c-primary-on: #ffffff;
    --c-primary-rim: #3b5d00;

    /* Havola, ikonka, faol menyu — asosiy rangning o'sha o'zi. Qorong'i
       mavzuda u yoritiladi, chunki #4a7100 qora yuzada 3.5:1 dan oshmaydi. */
    --c-action: #4a7100;
    --c-action-hover: #3b5d00;
    --c-action-soft: #e1f5ce;

    /* Logotip yashilining o'zi — GRAFIK rang: oq ustida 1.81:1, ya'ni hech
       qachon matn ko'tarmaydi. Faqat kun o'lchagichida va logotipning
       o'zida, ikkalasida ham qora yuzada (10.02:1). */
    --c-lime: #8fd400;

    /* Holat ranglari — atayin boshqa ohangda: yashil oilaning ichidagi
       ogohlantirish ko'zga tashlanmaydi. */
    --c-warn: #8a5a00;
    --c-warn-soft: #faeed8;
    --c-danger: #a32014;
    --c-danger-soft: #fbe7e3;

    /* O'lchagich yo'lagi — ikkala mavzuda ham bir xil qora yuza.
       Logotipdagi munosabat shu: qorong'ida yashil. */
    --c-meter-bg: #101808;
    --c-meter-idle: #424b3a;

    --shadow-card: 0 1px 2px rgba(20, 29, 9, .06), 0 12px 28px -20px rgba(20, 29, 9, .30);
}

/* Tizim sozlamasi qorong'i, foydalanuvchi aniq "light" tanlamagan bo'lsa */
@media (prefers-color-scheme: dark) {
    :root:not([data-theme='light']) {
        --c-bg: #0d1307;
        --c-surface: #1b2113;
        --c-surface-2: #272f1e;
        --c-line: #343e2a;
        --c-line-strong: #6b7b5b;

        --c-ink: #e5eddd;
        --c-muted: #aab69e;

        --c-primary-rim: #73ad00;

        /* Qorong'ida logotipning o'z yashili matn bo'la oladi: #90D600 —
           #8FD400 dan deyarli farq qilmaydi, yuzada 9.27:1. */
        --c-action: #90d600;
        --c-action-hover: #a2f000;
        --c-action-soft: #203206;

        --c-warn: #f0c24a;
        --c-warn-soft: #2c2412;
        --c-danger: #ff8f79;
        --c-danger-soft: #31201c;

        --c-meter-bg: #050903;
        --c-meter-idle: #47513e;

        --shadow-card: 0 1px 0 rgba(255, 255, 255, .04), 0 18px 40px -26px rgba(0, 0, 0, .9);
    }
}

/* Foydalanuvchi qo'lda tanlagani har doim ustun */
:root[data-theme='dark'] {
    --c-bg: #0d1307;
    --c-surface: #1b2113;
    --c-surface-2: #272f1e;
    --c-line: #343e2a;
    --c-line-strong: #6b7b5b;

    --c-ink: #e5eddd;
    --c-muted: #aab69e;

    --c-primary-rim: #73ad00;

    --c-action: #90d600;
    --c-action-hover: #a2f000;
    --c-action-soft: #203206;

    --c-warn: #f0c24a;
    --c-warn-soft: #2c2412;
    --c-danger: #ff8f79;
    --c-danger-soft: #31201c;

    --c-meter-bg: #050903;
    --c-meter-idle: #47513e;

    --shadow-card: 0 1px 0 rgba(255, 255, 255, .04), 0 18px 40px -26px rgba(0, 0, 0, .9);
}

@theme inline {
    /* Inter ilovada allaqachon o'zida saqlangan. Manrope faqat sarlavha va
       raqamlarda — u yuklanmasa Inter o'rnini bosadi, x-balandligi yaqin,
       ya'ni maket sezilarli siljimaydi. */
    --font-sans: 'Inter', ui-sans-serif, system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
    --font-display: 'Manrope', 'Inter', ui-sans-serif, system-ui, sans-serif;

    /* Shkala yana bir pog'ona kattalashtirildi. text-xs ham 1rem (19px) —
       ya'ni tasodifan ishlatilgan "mayda" sinf ham kichkina matn hosil
       qilmaydi; shkalada umuman kichik pog'ona yo'q.

       px qiymatlar ildiz 118.75% bo'lganda. Yirik pog'onalarda qatorlar
       oralig'i qisqaradi — katta kegldagi keng interlinyaj sarlavhani
       ikkiga bo'lib yuboradi. */
    --text-xs: 1rem;              /* 19.0px */
    --text-xs--line-height: 1.5;
    --text-sm: 1.0625rem;         /* 20.2px */
    --text-sm--line-height: 1.55;
    --text-base: 1.125rem;        /* 21.4px */
    --text-base--line-height: 1.6;
    --text-lg: 1.25rem;           /* 23.8px */
    --text-lg--line-height: 1.5;
    --text-xl: 1.4375rem;         /* 27.3px */
    --text-xl--line-height: 1.4;
    --text-2xl: 1.8125rem;        /* 34.4px */
    --text-2xl--line-height: 1.25;
    --text-3xl: 2.25rem;          /* 42.8px */
    --text-3xl--line-height: 1.15;
    --text-4xl: 2.875rem;         /* 54.6px */
    --text-4xl--line-height: 1.05;

    --color-bg: var(--c-bg);
    --color-surface: var(--c-surface);
    --color-surface-2: var(--c-surface-2);
    --color-line: var(--c-line);
    --color-line-strong: var(--c-line-strong);

    --color-ink: var(--c-ink);
    --color-muted: var(--c-muted);

    --color-action: var(--c-action);
    --color-action-hover: var(--c-action-hover);
    --color-action-soft: var(--c-action-soft);

    --color-primary: var(--c-primary);
    --color-primary-hover: var(--c-primary-hover);
    --color-primary-on: var(--c-primary-on);
    --color-lime: var(--c-lime);

    --color-warn: var(--c-warn);
    --color-warn-soft: var(--c-warn-soft);
    --color-danger: var(--c-danger);
    --color-danger-soft: var(--c-danger-soft);

    --color-meter-bg: var(--c-meter-bg);
    --color-meter-idle: var(--c-meter-idle);

    --radius-card: 1.125rem;
    --ease-out-soft: cubic-bezier(0.22, 1, 0.36, 1);
}

@layer base {
    /* Foizda, px da emas: brauzerda shriftni kattalashtirib qo'ygan
       foydalanuvchining sozlamasi shu tariqa saqlanadi va biznikiga
       ko'paytiriladi. */
    html {
        font-size: 118.75%;
        -webkit-text-size-adjust: 100%;
        color-scheme: light;
    }

    :root[data-text='lg'] { font-size: 131.25%; }
    :root[data-text='xl'] { font-size: 145%; }

    :root[data-theme='dark'] { color-scheme: dark; }

    @media (prefers-color-scheme: dark) {
        :root:not([data-theme='light']) { color-scheme: dark; }
    }

    body {
        background-color: var(--c-bg);
        color: var(--c-ink);
        font-family: var(--font-sans);
        font-size: var(--text-base);
        line-height: 1.6;
        /* ss02 — Inter ning "farqlash" to'plami: 1, l va I bir-biriga
           o'xshamay qoladi. Ko'zi xiralashgan o'quvchi uchun bu shartnoma
           raqamini o'qishning yarmi. */
        font-feature-settings: 'ss02' 1;
        font-variant-numeric: tabular-nums;
        /* O'zbekcha "Qo'llab-quvvatlash" kabi uzun so'zlar 320px ekranda
           yoki matn 140% ga kattalashtirilganda konteynerdan chiqib ketadi.
           break-word ularni faqat boshqa iloji qolmaganda bo'ladi. */
        overflow-wrap: break-word;
        -webkit-font-smoothing: antialiased;
        transition: background-color .3s var(--ease-out-soft), color .3s var(--ease-out-soft);
    }

    /* Yo'g'on va ikki qatlamli: halqa har qanday fonda ko'rinadi. */
    :focus-visible {
        outline: 3px solid var(--c-action);
        outline-offset: 3px;
        border-radius: 8px;
    }

    table { border-collapse: separate; border-spacing: 0; }

    ::selection { background: var(--c-primary); color: var(--c-primary-on); }
}

@layer components {
    /* Yorliq: kichik HARFLAR, sentence case. Eski 11px uppercase yorliqlar
       olib tashlandi — kirillda ular ayniqsa yomon o'qiladi va yoshi katta
       foydalanuvchi uchun deyarli ko'rinmas edi. */
    .u-label {
        @apply text-xs font-semibold text-muted;
    }

    .u-display {
        font-family: var(--font-display);
        @apply font-extrabold tracking-[-0.02em];
    }

    .u-figure {
        font-family: var(--font-display);
        @apply font-extrabold tracking-[-0.01em] tabular-nums;
    }

    .u-scroll {
        scrollbar-width: thin;
        scrollbar-color: var(--c-line-strong) transparent;
    }

    .u-card {
        @apply rounded-card border border-line bg-surface p-5 sm:p-6;
        box-shadow: var(--shadow-card);
    }

    /* ---- Tugmalar ---------------------------------------------------------
       Asosiy shakl selektor ro'yxati orqali bir marta yoziladi: Tailwind v4 da
       @apply bilan boshqa custom sinfni kengaytirib bo'lmaydi.
       min-height 3rem — ildiz 19px da 57px, ya'ni 44px talabidan yuqori. */
    .u-btn-primary, .u-btn-ghost, .u-btn-danger {
        @apply inline-flex min-h-[3rem] select-none items-center justify-center gap-2.5
               rounded-full px-6 py-3 text-base font-semibold leading-tight no-underline
               transition-[background-color,color,border-color,box-shadow,transform] duration-200;
        transition-timing-function: var(--ease-out-soft);
    }

    .u-btn-primary:active, .u-btn-ghost:active, .u-btn-danger:active { @apply translate-y-px; }

    /* Asosiy tugma ikkala mavzuda ham bir xil: #4a7100 fon, oq yozuv
       (5.75:1). Gardish faqat chekkani belgilaydi — qorong'i mavzuda to'q
       yashil fon qora sahifada 3.4:1 ga tushadi, gardish (#73ad00) esa
       6.92:1: tugmaning chegarasi qorong'ida ham yo'qolmaydi. */
    .u-btn-primary {
        background: var(--c-primary);
        color: var(--c-primary-on);
        border: 2px solid var(--c-primary-rim);
    }

    .u-btn-primary:hover { background: var(--c-primary-hover); }

    .u-btn-ghost {
        @apply border-2 border-line-strong bg-surface text-ink hover:border-action hover:text-action;
    }

    .u-btn-danger {
        background: var(--c-danger-soft);
        color: var(--c-danger);
        border: 2px solid transparent;
    }

    .u-btn-danger:hover { border-color: var(--c-danger); }

    .u-btn-sm { @apply min-h-[2.75rem] px-4 py-2 text-sm; }

    .u-field {
        @apply w-full min-h-[3rem] rounded-xl border-2 border-line-strong bg-surface px-4 py-3
               text-base text-ink transition-[border-color,box-shadow] duration-200
               focus:border-action focus:outline-none focus:ring-4 focus:ring-action/25;
    }

    .u-field::placeholder { color: var(--c-muted); opacity: 1; }

    /* ---- Jadval ----------------------------------------------------------- */
    .u-table { @apply w-full text-left text-sm; }

    .u-table thead th {
        @apply whitespace-nowrap px-4 py-3 text-left text-xs font-semibold text-muted;
    }

    .u-table tbody td {
        @apply border-t border-line px-4 py-4 align-middle text-ink;
    }

    .u-table tbody tr { @apply transition-colors duration-150; }
    .u-table tbody tr:hover td { @apply bg-surface-2/60; }

    /* Jadval o'z o'ramining kengligini o'lchaydi, ekranning emas — @media
       emas, @container. Ikki sabab bor:
         1. qurilmalar jadvali kengi 1920px ekranda ham tor ustunda turadi;
         2. foydalanuvchi matnni 140% ga kattalashtirsa jadval kengayadi,
            ekran esa o'zgarmaydi — chegara rem da bo'lgani uchun @container
            buni o'zi hisobga oladi, @media hech qachon hisobga olmaydi.
       Kartaga aylanganda har bir katak o'z sarlavhasini data-label dan chop
       etadi va gorizontal scroll umuman qolmaydi. */
    .u-table-wrap {
        container-type: inline-size;
        @apply overflow-x-auto;
    }

    @container (max-width: 40rem) {
        .u-table-cards { min-width: 0; }

        .u-table-cards thead { @apply sr-only; }

        .u-table-cards tbody tr {
            @apply mb-2.5 block rounded-xl border-2 border-line bg-surface p-1.5 last:mb-0;
        }

        .u-table-cards tbody td {
            @apply flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1
                   border-0 px-3 py-2 text-right;
        }

        .u-table-cards tbody td::before {
            content: attr(data-label);
            @apply shrink-0 text-left text-xs font-semibold text-muted;
        }

        /* Amallar ustuni real ma'lumotda ko'pincha bo'sh — busiz telefonda
           ostida hech narsasi yo'q "Amallar" yorlig'i qolib ketadi. */
        .u-table-cards tbody td:empty { @apply hidden; }

        .u-table-cards tbody tr:hover td { @apply bg-transparent; }
    }

    /* ---- Holat belgilari --------------------------------------------------
       Har biri rang + ikonka + so'z. Rang ko'rmaydigan foydalanuvchi uchun
       ikonka, ikonkani anglamagan uchun so'z qoladi. */
    /* whitespace-nowrap ataylab yo'q: 320px ekranda matn 140% ga
       kattalashtirilganda "To'lov kutilmoqda" bir qatorga sig'maydi va
       nowrap bo'lsa butun sahifani yonga surib yuboradi. */
    .u-pill-ok, .u-pill-warn, .u-pill-off, .u-pill-neutral {
        @apply inline-flex max-w-full items-center gap-1.5 rounded-full
               px-3 py-1.5 text-xs font-semibold;
    }

    .u-pill-ok { background: var(--c-action-soft); color: var(--c-action); }
    .u-pill-warn { background: var(--c-warn-soft); color: var(--c-warn); }
    .u-pill-off { background: var(--c-danger-soft); color: var(--c-danger); }
    .u-pill-neutral { @apply bg-surface-2 text-muted; }

    /* ---- Navigatsiya ------------------------------------------------------
       shrink-0 + whitespace-nowrap: yorliq hech qachon o'z tabletkasi ichida
       ikkiga bo'linmaydi. Ularsiz 140% matnda qator siqilib, "Bosh sahifa" va
       "Tezlikni tekshirish" ikki qatorga tushardi va tabletkalar bo'yi har xil
       bo'lib qolardi. Joy yetmasa navigatsiya qatorlarga bo'linadi — bu
       tabletkalar orasida sodir bo'ladi, ichida emas. */
    .u-nav-link {
        @apply relative flex min-h-[3rem] shrink-0 items-center gap-2.5 whitespace-nowrap
               rounded-full px-4 py-2.5
               text-sm font-semibold text-muted no-underline
               transition-colors duration-200 hover:bg-surface-2 hover:text-ink;
    }

    .u-nav-link[aria-current='page'] {
        background: var(--c-action-soft);
        color: var(--c-action);
    }

    /* ---- IMZO: kun o'lchagichi -------------------------------------------
       Abonentning yagona savoli — "hisobim yetadimi?". Foiz chizig'i bunga
       javob bermaydi, sanaladigan kunlar beradi. Yo'lak ikkala mavzuda ham
       qorong'i: logotipdagi munosabat aynan shu — qorong'ida yashil. */
    .u-meter {
        @apply flex items-end gap-[2px] rounded-xl px-3 py-3;
        background: var(--c-meter-bg);
    }

    .u-tick {
        @apply min-w-[3px] flex-1 rounded-[2px];
        height: 1.25rem;
        background: var(--c-meter-idle);
    }

    .u-tick[data-t='past'] { background: var(--c-lime); }
    .u-tick[data-t='today'] { background: #ffffff; height: 1.75rem; }
    /* Yechim kuni: rangi ham, bo'yi ham boshqacha — faqat rang bilan
       farqlanmasin. */
    .u-tick[data-t='charge'] { background: #f0c24a; height: 2.25rem; }

    /* ---- Logotip ----------------------------------------------------------
       Logotip fayllari (public/img/logo-*.png) qorong'i yer uchun chizilgan:
       "SOLA" yozuvi va qushning tanasi OQ. Yorug' mavzuda ular oddiygina
       yo'qoladi. Shuning uchun logotip o'z yerini o'zi bilan olib yuradi —
       kun o'lchagichi bilan bir xil qora yuza. Qorong'i mavzuda bu yuza fon
       bilan deyarli qo'shilib ketadi, ya'ni bitta qoida ikkala mavzuda ham
       to'g'ri ishlaydi va mavzuga qarab shart yozish kerak emas.

       Mijoz qorong'i yozuvli (yorug' yer uchun) variantni bersa, yorug'
       mavzuda bu yuzani olib tashlash mumkin. */
    .u-logo {
        @apply inline-flex shrink-0 items-center rounded-xl px-3 py-2 no-underline;
        background: var(--c-meter-bg);
    }

    /* ---- Ko'rinish paneli ------------------------------------------------- */
    .u-choice {
        @apply flex min-h-[3rem] cursor-pointer items-center justify-center gap-2 rounded-xl
               border-2 border-line px-3 py-2 text-sm font-semibold text-ink
               transition-colors duration-200 hover:bg-surface-2;
    }

    .u-choice input { @apply sr-only; }

    /* Ikki shakl: radio bilan (mavzu, matn hajmi) va aria-pressed bilan
       (til) — tanlangan ko'rinish ikkalasida ham bir xil. */
    .u-choice:has(input:checked),
    .u-choice[aria-pressed='true'] {
        border-color: var(--c-action);
        background: var(--c-action-soft);
        color: var(--c-action);
    }

    .u-choice:has(input:focus-visible) {
        outline: 3px solid var(--c-action);
        outline-offset: 3px;
    }
}

@layer utilities {
    @keyframes rise {
        from { opacity: 0; transform: translateY(.6rem); }
        to { opacity: 1; transform: none; }
    }

    .u-rise {
        animation: rise .45s var(--ease-out-soft) both;
        animation-delay: calc(var(--i, 0) * 55ms);
    }

    @keyframes draw {
        from { stroke-dashoffset: var(--arc-len); }
        to { stroke-dashoffset: 0; }
    }

    .u-draw {
        stroke-dasharray: var(--arc-len) 360;
        animation: draw 1s var(--ease-out-soft) .15s both;
    }
}

/* ---------------------------------------------------------------------------
   Tepa qator va matn hajmi
   ---------------------------------------------------------------------------
   Media so'rovlaridagi rem har doim brauzerning boshlang'ich 16px iga
   bog'lanadi, kontent esa ildiz font-size i bilan o'sadi — 145% da 23.2px.
   Ya'ni "2xl" chegarasi 1536px da ochiladi, ammo o'sha kenglikda qator
   allaqachon 1.45 barobar kattalashgan bo'ladi. Qator max-w-[1240px] bilan
   cheklangani uchun kengroq ekran ham yordam bermaydi: faktlarga ~341px joy
   qoladi, kerak esa ~470-530px.

   Shuning uchun 131%/145% da faktlar qisqartirilmaydi ham, yashirilmaydi ham
   ("Shartnoma D…" qirqilgan raqam — foydasiz, shartnoma raqami esa desktopda
   boshqa joyda ko'rsatilmaydi), balki to'liq kenglikda o'z qatoriga
   tushiriladi. Logotip va boshqaruvlar birinchi qatorda qoladi.
   Qoidalar qatlamsiz: ular Tailwind ning lg:flex-nowrap va ml-5
   utilitalaridan ustun turishi kerak. */
:root[data-text] .u-id-row {
    flex-wrap: wrap;
}

:root[data-text] .u-id-facts {
    order: 1;
    flex-basis: 100%;
    margin-left: 0;
}

@media (prefers-reduced-motion: reduce) {
    *, *::before, *::after {
        animation-duration: .001ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: .001ms !important;
    }
}

/* Abonentlar to'lov tarixini chop etadi — bu yagona chop etiladigan ekran. */
@media print {
    .u-no-print { display: none !important; }

    body { background: #fff; color: #000; }

    .u-card {
        border: 1px solid #999;
        box-shadow: none;
        break-inside: avoid;
    }

    .u-table-cards thead { position: static !important; width: auto !important; height: auto !important; clip: auto !important; }
    .u-table-cards tbody tr { display: table-row; border: 0; }
    .u-table-cards tbody td { display: table-cell; text-align: left; }
    .u-table-cards tbody td::before { content: none; }
}
`;

document.currentScript.insertAdjacentHTML(
    'afterend',
    '<style type="text/tailwindcss">' + TOKENS + '</style>'
);
