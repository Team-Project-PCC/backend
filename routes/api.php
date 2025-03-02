<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Event\EventController;
use App\Http\Controllers\Auth\AccountController;
use App\Http\Controllers\Event\PromotionController;

Route::post('/register', [RegisterController::class, 'register']);

Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
    // ->middleware(['auth', 'signed'])
    ->name('verification.verify');

Route::post('/email/resend', [EmailVerificationController::class, 'resend'])
    ->name('verification.resend');

Route::post('/login', [LoginController::class, 'store'])->name('login');
Route::post('/user', [LoginController::class, 'show'])->name('user');

Route::get('/email', function () {
    return response()->json(['message' => 'Email verification successful']);
});

Route::middleware(['auth:sanctum', 'verified'])->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/logout', [LogoutController::class, 'store'])->name('logout');

Route::put('update_profile', [AccountController::class, 'update_profile'])->middleware('auth:sanctum');

Route::apiResource('events', EventController::class)->middleware('role:admin')->except('index', 'show');

Route::apiResource('promo', PromotionController::class)->middleware('role:admin')->except('index', 'show');