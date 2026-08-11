<?php

use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Auth\TwoFactorChallengeController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\Marketing\AboutController;
use App\Http\Controllers\Marketing\ContactController;
use App\Http\Controllers\Marketing\FaqController;
use App\Http\Controllers\Marketing\FeaturesController;
use App\Http\Controllers\Marketing\HomeController;
use App\Http\Controllers\Marketing\NewsletterController;
use App\Http\Controllers\Marketing\PricingController;
use App\Http\Controllers\Marketing\PrivacyController;
use App\Http\Controllers\Marketing\SecurityController;
use App\Http\Controllers\Marketing\SolutionsController;
use App\Http\Controllers\Marketing\TermsController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('landing');

// Public marketing site (docs/MARKETING.md §2 / §5.2).
Route::get('/features', FeaturesController::class)->name('marketing.features');
Route::get('/solutions', SolutionsController::class)->name('marketing.solutions');
Route::get('/pricing', PricingController::class)->name('marketing.pricing');
Route::get('/security', SecurityController::class)->name('marketing.security');
Route::get('/about', AboutController::class)->name('marketing.about');
Route::get('/faq', FaqController::class)->name('marketing.faq');
Route::get('/privacy', PrivacyController::class)->name('marketing.privacy');
Route::get('/terms', TermsController::class)->name('marketing.terms');

Route::get('/contact', [ContactController::class, 'create'])->name('marketing.contact');
Route::post('/contact', [ContactController::class, 'store'])
    ->middleware('throttle:5,1')
    ->name('marketing.contact.store');

Route::post('/newsletter/subscribe', [NewsletterController::class, 'subscribe'])
    ->middleware('throttle:10,1')
    ->name('marketing.newsletter.subscribe');
Route::post('/newsletter', [NewsletterController::class, 'subscribe'])
    ->middleware('throttle:10,1')
    ->name('marketing.newsletter.store');
Route::get('/newsletter/unsubscribe/{email}', [NewsletterController::class, 'unsubscribe'])
    ->where('email', '[^/]+')
    ->name('marketing.newsletter.unsubscribe');

Route::middleware('guest')->group(function () {
    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store']);

    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:5,1');

    Route::get('/forgot-password', [ForgotPasswordController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [ForgotPasswordController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('password.email');

    Route::get('/reset-password/{token}', [ResetPasswordController::class, 'create'])->name('password.reset');
    Route::post('/reset-password', [ResetPasswordController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('password.update');
});

Route::middleware('auth')->group(function () {
    Route::get('/verify-email', [VerifyEmailController::class, 'notice'])->name('verification.notice');

    Route::get('/verify-email/{id}/{hash}', [VerifyEmailController::class, 'verify'])
        ->middleware('signed')
        ->name('verification.verify');

    Route::post('/verify-email/resend', [VerifyEmailController::class, 'resend'])
        ->middleware('throttle:6,1')
        ->name('verification.send');
});

// Standalone Two-Factor Challenge (docs/MODULES.md §6, ADR-14) — rendered
// outside the console shell. Frontend-first preview; the real gate is wired in
// the backend phase.
Route::get('/two-factor-challenge', TwoFactorChallengeController::class)->name('two-factor.challenge');

Route::post('/logout', LogoutController::class)
    ->middleware('auth')
    ->name('logout');
