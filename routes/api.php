<?php

use App\Http\Controllers\Auth\ForgotPasswordOtpController;
use App\Controllers\HealthController;
use App\Controllers\SiteController;
use App\Controllers\UsersController;
use Illuminate\Support\Facades\Route;

require_once base_path('src/bootstrap.php');

Route::get('/health', fn () => (new HealthController())->show());
Route::get('/content', fn () => \App\Response::json((new SiteController())->contentApi()));
Route::get('/users', fn () => (new UsersController())->index());
Route::post('/users', fn () => (new UsersController())->store());
Route::get('/users/{id}', fn (int $id) => (new UsersController())->show($id));

Route::prefix('forgot-password')->group(function (): void {
    Route::post('/request-otp', [ForgotPasswordOtpController::class, 'requestOtp']);
    Route::post('/verify-otp', [ForgotPasswordOtpController::class, 'verifyOtp']);
    Route::post('/reset', [ForgotPasswordOtpController::class, 'reset']);
});
