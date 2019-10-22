<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::group(['prefix' => '/', 'middleware' => ['login', 'setLang'], 'namespace' => 'Site'], function () {
    Route::get('/', 'Cabinet\Controller@index')->name('cabinet');

    Route::group(['prefix' => '/tariffs', 'namespace' => 'Tariffs'], function () {
        Route::get('/', 'Controller@index')->name('tariffs');
        Route::get('/connect/{id}/{type}', 'Controller@connect')->name('tariff.connect');
    });

    Route::group(['prefix' => '/services', 'namespace' => 'Services'], function () {
        Route::get('/', 'Controller@index')->name('services');
        Route::get('/device/add', 'Controller@newDevice')->name('services.device.new');
    });

    Route::group(['prefix' => 'auth', 'namespace' => 'Auth'], function () {
        Route::get('/logout', 'Controller@logout')->name('logout');
    });

    Route::group(['prefix' => 'traffic', 'namespace' => 'Traffic'], function () {
        Route::get('/detail', 'Controller@index')->name('traffic');
        Route::match(['get', 'post'], '/detail/month/', 'Controller@other_month')->name('traffic.month');
    });

    Route::group(['prefix' => 'payment', 'namespace' => 'Payment'], function () {
        Route::get('/history', 'Controller@index')->name('payment');
        Route::match(['get', 'post'], '/history/month', 'Controller@other_month')->name('payment.month');
    });

    Route::get('/select/account/{acc_id}/{type}', 'Auth\Controller@selectAccountSet')->name('set.account');
});

Route::get('/change/lang/{lang}', 'Controller@changeLang')->name('change.lang');

Route::group(['prefix' => 'auth', 'namespace' => 'Site\Auth', 'middleware' => ['checkCookie', 'setLang'] ], function () {
   Route::match(['get', 'post'], '/login', 'Controller@login')->name('login');
   Route::match(['get', 'post'], '/verify', 'Controller@verify')->name('verify');
   Route::match(['get'], '/select/account', 'Controller@selectAccount')->name('select.account');
});


