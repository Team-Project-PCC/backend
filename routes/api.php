<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\{
    RegisterController,
    LoginController,
    EmailVerificationController,
    LogoutController,
    AccountController
};
use App\Http\Controllers\Event\{
    EventController,
    PromotionController
};
use App\Http\Controllers\Promotion\{
    PromotionTypeController,
    PromotionRuleController
};
use App\Http\Controllers\Order\TicketController;

// Authentication Routes
Route::post('/register', [RegisterController::class, 'register']);
Route::post('/login', [LoginController::class, 'store'])->name('login');
Route::post('/logout', [LogoutController::class, 'store'])->name('logout');
Route::post('/user', [LoginController::class, 'show'])->name('user');

Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])->name('verification.verify');
Route::post('/email/resend', [EmailVerificationController::class, 'resend'])->name('verification.resend');
Route::get('/email', fn () => response()->json(['message' => 'Email verification successful']));

Route::middleware(['auth:sanctum', 'verified'])->get('/user', fn (Request $request) => $request->user());

// Profile Management
Route::post('/update_profile', [AccountController::class, 'update_profile']);

// Event Routes
Route::apiResource('events', EventController::class)->middleware('role:admin')->except(['index', 'show']);
Route::get('events', [EventController::class, 'index']);
Route::get('events/{id}', [EventController::class, 'show']);

// Promotion Routes
Route::apiResource('promo', PromotionController::class)->middleware('role:admin')->except(['index', 'show']);
Route::get('promo', [PromotionController::class, 'index']);
Route::get('promo/{id}', [PromotionController::class, 'show']);

// Promotion Type Routes
Route::apiResource('promotion-types', PromotionTypeController::class)->middleware('role:admin')->except(['store']);

// Promotion Rules Routes
Route::apiResource('promo/rules', PromotionRuleController::class)->middleware('role:admin')->except(['index', 'show']);
