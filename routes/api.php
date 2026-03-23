<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ApiUserController;
use App\Http\Controllers\Api\CouponController;
use App\Http\Controllers\SubscriptionController;

Route::apiResource('user', ApiUserController::class)->middleware('auth:sanctum');

// Coupon validation (public, no auth required for onboarding)
Route::post('coupons/validate', [CouponController::class, 'validate'])->name('api.coupons.validate');
Route::post('coupons/exit-intent', [CouponController::class, 'exitIntent'])->name('api.coupons.exit-intent');

// Stripe checkout webhook (test/sandbox)
Route::post('stripe/webhook', [SubscriptionController::class, 'webhook'])->name('api.stripe.webhook');
