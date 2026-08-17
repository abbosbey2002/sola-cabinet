<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\CabinetController;
use App\Http\Controllers\DeviceController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\TariffController;
use App\Http\Controllers\TrafficController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Cabinet
|--------------------------------------------------------------------------
|
| Everything behind the SMS check. There is no Laravel auth guard: the
| "verify" cookie set after a successful SMS confirmation is the session.
|
*/

Route::middleware('abonent.verified')->group(function (): void {
    // One page per section of the top navigation. The POST beside each one
    // re-renders just that page's result block, so the period controls update
    // without a full reload.
    Route::get('/', [CabinetController::class, 'index'])->name('cabinet');
    Route::get('/services', [CabinetController::class, 'services'])->name('services');

    Route::get('/statistics', [TrafficController::class, 'index'])->name('traffic');
    Route::post('/statistics', [TrafficController::class, 'filter'])->name('traffic.filter');

    Route::get('/finance', [PaymentController::class, 'index'])->name('payment');
    Route::post('/finance', [PaymentController::class, 'filter'])->name('payment.filter');

    /*
     * The three write actions below were GET. A GET request carries no CSRF
     * token, and all three change state — two of them spend the subscriber's
     * money. A third-party page opened in the same browser only had to redirect
     * to /devices/add for a permit to be billed. They are POST now, and the
     * tariff's id and timing moved from route segments into a validated body
     * (TariffConnectRequest).
     */
    Route::prefix('tariffs')->group(function (): void {
        Route::get('/', [TariffController::class, 'index'])->name('tariff');
        Route::post('/connect', [TariffController::class, 'connect'])->name('tariff.connect');
    });

    Route::prefix('devices')->group(function (): void {
        Route::get('/', [DeviceController::class, 'index'])->name('devices');
        Route::post('/add', [DeviceController::class, 'store'])->name('devices.add');
        Route::post('/delete/{permitId}', [DeviceController::class, 'destroy'])->name('devices.delete');
    });

    // The old URLs are in bookmarks and in the links billing texts out.
    Route::redirect('/traffic/detail', '/statistics');
    Route::redirect('/payment/history', '/finance');

    Route::get('/select/account/{accountId}', [AuthController::class, 'switchAccount'])->name('set.account');
    Route::get('/auth/logout', [AuthController::class, 'logout'])->name('logout');
});

/*
|--------------------------------------------------------------------------
| Login
|--------------------------------------------------------------------------
*/

Route::prefix('auth')->middleware('abonent.guest')->group(function (): void {
    // Unmetered, these two endpoints let anyone bomb a subscriber with SMS or
    // brute-force a short confirmation code. The GET side is the plain form,
    // so only the submissions are throttled.
    Route::get('/login', [AuthController::class, 'login'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:5,1');

    Route::get('/verify', [AuthController::class, 'verify'])->name('verify');
    Route::post('/verify', [AuthController::class, 'verify'])
        ->middleware('throttle:10,1');

    Route::get('/select/account', [AuthController::class, 'accountChoice'])->name('select.account');
});

/*
|--------------------------------------------------------------------------
| Locale
|--------------------------------------------------------------------------
*/

Route::get('/change/lang/{locale}', [LocaleController::class, 'update'])->name('change.lang');
