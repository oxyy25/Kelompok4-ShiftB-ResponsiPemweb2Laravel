<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'home')->name('home');
Route::view('login', 'auth.login')->name('login');
Route::view('register', 'auth.register')->name('register');
