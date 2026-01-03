<?php

use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\KasPaymentController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Google OAuth Routes
Route::get('/auth/google', [GoogleController::class, 'redirect'])->name('auth.google');
Route::get('/auth/google/callback', [GoogleController::class, 'callback'])->name('auth.google.callback');

// Kas Payment Routes
Route::middleware(['auth'])->group(function () {
    Route::get('/kas/pay/{kasPayment}', [KasPaymentController::class, 'pay'])->name('kas.pay');
    Route::get('/kas/finish', [KasPaymentController::class, 'finish'])->name('kas.finish');
    Route::get('/kas/unfinish', [KasPaymentController::class, 'unfinish'])->name('kas.unfinish');
    Route::get('/kas/error', [KasPaymentController::class, 'error'])->name('kas.error');
});

// Midtrans Callback (no auth)
Route::post('/kas/callback', [KasPaymentController::class, 'callback'])->name('kas.callback');
