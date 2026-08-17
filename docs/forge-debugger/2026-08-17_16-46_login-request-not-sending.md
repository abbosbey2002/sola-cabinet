# Login formasi so'rov jo'natolmayapti

- **Symptom:** Foydalanuvchi login formasini yuborganda so'rov "ketmayapti" — aslida 3 soniya kutgandan keyin 503 Service Unavailable qaytadi.
- **Root cause:** `AuthController::login()` -> `SolaClient::identify()` SOLA billing API'ga (`172.19.1.101:808` / `172.19.1.201:808`, `/identify`) so'rov yuboradi. Bu dev-mashina SOLA'ning ichki tarmog'iga/VPN'iga ulanmagan ("No route to host" — `172.19.1.0/24` uchun marshrut yo'q). `connect_timeout=3s` tugagach `SolaUnavailableException` otiladi, `bootstrap/app.php` uni 503 sahifasiga aylantiradi. Bu kod xatosi emas, muhit (network) muammosi.
- **Evidence chain:**
  1. `storage/logs/laravel.log`: doimiy `SOLA API unreachable ... cURL error 28: Connection timed out ... /identify` yozuvlari, oxirgisi so'rov paytida bevosita hosil bo'ldi.
  2. `bash -c 'cat < /dev/null > /dev/tcp/172.19.1.101/808'` -> `No route to host` (host tarmoqda yo'q, filtrlanmagan — mutlaqo yetib bo'lmaydi).
  3. To'g'ridan-to'g'ri `curl -X POST /auth/login` bilan reproduce qilindi: `login` maydoni bilan 200 dan oldin 3.026s kutish va 503 javob — timeout bilan mos.
  4. `config/sola.php` izohi aynan shu holatni tasvirlaydi: "Billing sits on SOLA's internal network, so off the VPN every call times out... every page becomes a 503."
- **Ruled out:** Forma/CSRF/route xatosi emas (birinchi urinishda `phone` maydon nomi bilan xato qilgan edim — validatsiya `login` maydonini kutadi, bu alohida narsa, haqiqiy bug emas).
- **Fix:** `.env` da `SOLA_FAKE=false` -> `true` (loyihada aynan shu holat uchun tayyorlangan `App\Services\Sola\FakeSolaServer` mexanizmi bor, faqat `local` environmentda ishlaydi). `php artisan config:clear` (container: `cabinet_php`) bilan config-cache tozalandi.
- **Numbers:** POST /auth/login: 3.026s + 503 -> 0.028s + 200 (SMS-kod ekraniga o'tdi).
- **Regression test:** Yo'q — bu kod bug emas, mahalliy muhit sozlamasi, `.env` git'ga kirmaydi.
- **Same-pattern risks:** Agar boshqa dasturchilarda ham xuddi shu 503 chiqsa, sabab bir xil bo'ladi — ular ham VPN/SOLA tarmog'ida emas. Ularga ham `.env`da `SOLA_FAKE=true` qo'yishni tavsiya qilish kerak (README/onboarding hujjatida eslatilganmi tekshirish kerak).
