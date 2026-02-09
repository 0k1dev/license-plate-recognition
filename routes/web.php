<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Fix lỗi "Route [login] not defined"
Route::name('login')->get('login', function () {
    return redirect()->route('filament.admin.auth.login');
});

// Filament/Livewire tự động xử lý tất cả auth routes
// Không cần thêm route login thủ công

// Filament/Livewire tự động xử lý tất cả auth routes
// Không cần thêm route login thủ công
