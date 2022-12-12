<?php

use Illuminate\Support\Facades\Route;

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


Route::get('/admin', [\App\Http\Controllers\Admin::class, 'index']);
Route::get('/admin/conversation/{conversation}', [\App\Http\Controllers\Admin::class, 'byConversationID']);
Route::get('/admin/user/{user}', [\App\Http\Controllers\Admin::class, 'byUserID']);
Route::get('/admin/{user}/{conversation}', [\App\Http\Controllers\Admin::class, 'byUserIDAndConversationID']);
