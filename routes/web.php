<?php

declare(strict_types=1);

use App\Http\Controllers\ActivityBeaconController;
use App\Http\Controllers\Admin\AccountController;
use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\AdminTariffController;
use App\Http\Controllers\Admin\StatsController;
use App\Http\Controllers\Admin\TariffChangeController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\CabinetController;
use App\Http\Controllers\DeviceController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\TariffController;
use App\Http\Controllers\TopUpController;
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

    // iWon card top-up — unsigned browser redirect, no server callback.
    Route::prefix('topup')->group(function (): void {
        Route::get('/', [TopUpController::class, 'index'])->name('topup');
        Route::post('/', [TopUpController::class, 'store'])->name('topup.store');
    });

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

    // Browser-side events for the activity journal (print, search, a dialog
    // closed unconfirmed). Throttled: a page sends a handful, never sixty.
    Route::post('/activity', [ActivityBeaconController::class, 'store'])
        ->middleware('throttle:60,1')
        ->name('activity.beacon');

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
        ->middleware('throttle:5,1')
        ->name('login.submit');

    Route::get('/verify', [AuthController::class, 'verify'])->name('verify');
    Route::post('/verify', [AuthController::class, 'verify'])
        ->middleware('throttle:10,1')
        ->name('verify.submit');

    Route::get('/select/account', [AuthController::class, 'accountChoice'])->name('select.account');
});

/*
|--------------------------------------------------------------------------
| Admin
|--------------------------------------------------------------------------
|
| A separate world from the cabinet above: its own cookie (AdminSession), its
| own guard aliases, no SolaClient session or subscriber state involved.
| Screens: which tariffs /tariffs may show, and the activity journal's
| statistics, tariff-change history and per-account history.
|
*/

Route::prefix('admin')->group(function (): void {
    Route::middleware('admin.guest')->group(function (): void {
        Route::get('/login', [AdminAuthController::class, 'login'])->name('admin.login');
        Route::post('/login', [AdminAuthController::class, 'login'])
            ->middleware('throttle:5,1');
    });

    // Every request by a signed-in admin is audited (admin_audit), and each
    // screen is gated by what the admin's role may do (AdminAbility).
    Route::middleware(['admin.auth', 'admin.audit'])->group(function (): void {
        Route::middleware('admin.can:manage-tariffs')->group(function (): void {
            Route::get('/tariffs', [AdminTariffController::class, 'index'])->name('admin.tariffs');
            Route::post('/tariffs/{tariffId}/toggle', [AdminTariffController::class, 'toggle'])
                ->whereNumber('tariffId')
                ->name('admin.tariffs.toggle');
            Route::post('/tariffs/bulk-toggle', [AdminTariffController::class, 'bulkToggle'])
                ->name('admin.tariffs.bulk-toggle');
        });

        Route::middleware('admin.can:view-stats')->prefix('stats')->group(function (): void {
            Route::get('/', [StatsController::class, 'index'])->name('admin.stats');
            Route::get('/funnel', [StatsController::class, 'funnel'])->name('admin.stats.funnel');
            Route::get('/segments', [StatsController::class, 'segments'])->name('admin.stats.segments');
            Route::get('/errors', [StatsController::class, 'errors'])->name('admin.stats.errors');
        });

        Route::middleware('admin.can:view-tariff-changes')->prefix('tariff-changes')->group(function (): void {
            Route::get('/', [TariffChangeController::class, 'index'])->name('admin.tariff-changes');
            Route::get('/export', [TariffChangeController::class, 'export'])
                ->middleware('admin.can:export')
                ->name('admin.tariff-changes.export');
            Route::get('/{changeId}', [TariffChangeController::class, 'show'])
                ->whereNumber('changeId')
                ->name('admin.tariff-changes.show');
        });

        Route::middleware('admin.can:view-account-history')->prefix('accounts')->group(function (): void {
            Route::get('/', [AccountController::class, 'index'])->name('admin.accounts');
            Route::get('/{accountId}', [AccountController::class, 'show'])
                ->where('accountId', '[A-Za-z0-9_-]{1,32}')
                ->name('admin.accounts.show');
        });

        Route::post('/logout', [AdminAuthController::class, 'logout'])->name('admin.logout');
    });
});

/*
|--------------------------------------------------------------------------
| Locale
|--------------------------------------------------------------------------
*/

Route::get('/change/lang/{locale}', [LocaleController::class, 'update'])->name('change.lang');
