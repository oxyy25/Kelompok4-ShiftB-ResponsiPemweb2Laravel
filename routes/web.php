<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'home')->name('home');
Route::view('login', 'auth.login')->name('login');
Route::view('register', 'auth.register')->name('register');

Route::view('labs', 'labs.index')->name('labs.index');
Route::view('labs/{id}', 'labs.show')->name('labs.show')->whereNumber('id');

Route::view('alats', 'alats.index')->name('alats.index');

Route::view('peminjaman', 'peminjaman.index')->name('peminjaman.index');
Route::view('peminjaman/buat', 'peminjaman.create')->name('peminjaman.create');
Route::view('peminjaman/{id}', 'peminjaman.show')->name('peminjaman.show')->whereNumber('id');

Route::view('admin', 'admin.index')->name('admin.index');
Route::view('admin/labs', 'admin.labs')->name('admin.labs');
Route::view('admin/peminjaman', 'admin.peminjaman')->name('admin.peminjaman');
Route::view('admin/pengguna', 'admin.users')->name('admin.users');
