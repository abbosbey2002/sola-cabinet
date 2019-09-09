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

Route::group(['prefix' => '/', 'middleware' => 'login', 'namespace' => 'Site'], function () {
    Route::get('/', 'Cabinet\Controller@index')->name('cabinet');

    Route::group(['prefix' => '/tariffs', 'namespace' => 'Tariffs'], function () {
        Route::get('/', 'Controller@index')->name('tariffs');
        Route::get('/connect/{id}', 'Controller@connect')->name('tariff.connect');
    });

    Route::group(['prefix' => 'auth', 'namespace' => 'Auth'], function () {
        Route::get('/logout', 'Controller@logout')->name('logout');
    });

    Route::group(['prefix' => 'traffic', 'namespace' => 'Traffic'], function () {
        Route::get('/detail', 'Controller@index')->name('traffic');
    });
});


Route::group(['prefix' => 'auth', 'namespace' => 'Site\Auth', 'middleware' => 'checkCookie'], function () {
   Route::match(['get', 'post'], '/login', 'Controller@login')->name('login');
   Route::match(['get', 'post'], '/verify', 'Controller@verify')->name('verify');
});