<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Route::statamic('example', 'example-view', [
//    'title' => 'Example'
// ]);


Route::statamic('career/{subpage}', '', [
    'redirect' => 'career/',
]);
Route::statamic('search', 'search');
Route::statamic('users', 'user.index');
Route::statamic('users/{username}', 'user.profile');
Route::statamic('account', 'user.account');
Route::statamic('login', 'auth.login');
Route::statamic('register', 'auth.register');
Route::statamic('forgot-password', 'auth.password-forgot');
Route::statamic('reset-password', 'auth.password-reset');