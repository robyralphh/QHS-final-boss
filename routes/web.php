<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EmailVerificationController;

// Email Verification Web Route
Route::get('/verify-email', [EmailVerificationController::class, 'verifyEmail'])->name('verify.email');

// Catch-all route for React SPA - must be last
Route::get('/{any?}', function () {
    return view('app');
})->where('any', '.*');

