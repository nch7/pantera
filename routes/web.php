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

Route::get('/', function () {
    return view('welcome');
});

Auth::routes();

Route::post('/message', 'DashboardController@newMessage');
Route::get('/message', 'DashboardController@getMessage');

Route::get('/dashboard', 'DashboardController@index')->name('dashboard');

Route::get('/home', 'HomeController@index');

Route::get('/test', function() {
    preg_match('/looking for (.+)/i', "looking for giorgi gedenidze", $match);
    dd($match);
});