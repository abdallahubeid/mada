<?php

use App\Http\Controllers\Admin\AccountSecurityController as AdminAccountSecurityController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\AiFeatureController as AdminAiFeatureController;
use App\Http\Controllers\Admin\AuditLogController as AdminAuditLogController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\FaqController as AdminFaqController;
use App\Http\Controllers\Admin\FeatureController as AdminFeatureController;
use App\Http\Controllers\Admin\MessageController as AdminMessageController;
use App\Http\Controllers\Admin\ModuleController as AdminModuleController;
use App\Http\Controllers\Admin\NotificationController as AdminNotificationController;
use App\Http\Controllers\Admin\OfferingController as AdminOfferingController;
use App\Http\Controllers\Admin\PlanController as AdminPlanController;
use App\Http\Controllers\Admin\ProblemController as AdminProblemController;
use App\Http\Controllers\Admin\ProfileController as AdminProfileController;
use App\Http\Controllers\Admin\SettingController as AdminSettingController;
use App\Http\Controllers\Admin\SolutionController as AdminSolutionController;
use App\Http\Controllers\Admin\TenantController as AdminTenantController;
use App\Http\Controllers\Admin\TestimonialController as AdminTestimonialController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\TwoFactorChallengeController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\DashboardSetupController;
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
use App\Livewire\Dashboard\Overview;
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

Route::post('/newsletter', NewsletterController::class)
    ->middleware('throttle:10,1')
    ->name('marketing.newsletter.store');

Route::middleware('guest')->group(function () {
    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store']);

    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:5,1');
});

Route::middleware('auth')->group(function () {
    Route::get('/verify-email', [VerifyEmailController::class, 'notice'])->name('verification.notice');

    Route::get('/verify-email/{id}/{hash}', [VerifyEmailController::class, 'verify'])
        ->middleware('signed')
        ->name('verification.verify');

    Route::post('/verify-email/resend', [VerifyEmailController::class, 'resend'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    Route::get('/dashboard/setup', DashboardSetupController::class)
        ->middleware('verified')
        ->name('dashboard.setup');
});

// Operational tenant app — gated behind a verified user on an active tenant
// (docs/ARCHITECTURE.md §1.3, BR-203).
Route::middleware(['auth', 'verified', 'tenant.active'])->prefix('app')->group(function () {
    Route::get('/dashboard', Overview::class)->name('dashboard');
});






// Super Admin / Platform Console (docs/MODULES.md §6). Frontend-first preview
// with mock data — the auth/2FA gate (ADR-14) and cross-tenant authorization
// middleware are added with the backend phase.
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', AdminDashboardController::class)->name('dashboard');
    Route::get('/tenants', [AdminTenantController::class, 'index'])->name('tenants');
    Route::get('/tenants/{tenant}', [AdminTenantController::class, 'show'])->name('tenants.show');
    Route::put('/tenants/{tenant}/marketing', [AdminTenantController::class, 'updateMarketing'])->name('tenants.marketing');

    Route::get('/plans', [AdminPlanController::class, 'index'])->name('plans');
    Route::post('/plans', [AdminPlanController::class, 'store'])->name('plans.store');
    Route::put('/plans/{plan}', [AdminPlanController::class, 'update'])->name('plans.update');
    Route::delete('/plans/{plan}', [AdminPlanController::class, 'destroy'])->name('plans.destroy');

    Route::resource('faqs', AdminFaqController::class)->except(['show']);
    Route::resource('problems', AdminProblemController::class)->except(['show']);
    Route::resource('solutions', AdminSolutionController::class)->except(['show']);
    Route::resource('offerings', AdminOfferingController::class)->except(['show']);
    Route::resource('modules', AdminModuleController::class)->except(['show']);
    Route::resource('ai-features', AdminAiFeatureController::class)->except(['show']);
    Route::resource('features', AdminFeatureController::class)->except(['show']);
    Route::resource('testimonials', AdminTestimonialController::class)->except(['show']);

    Route::get('/landing/settings', [AdminSettingController::class, 'edit'])->name('landing.settings.edit');
    Route::put('/landing/settings', [AdminSettingController::class, 'update'])->name('landing.settings.update');

    Route::get('/notifications', AdminNotificationController::class)->name('notifications');
    Route::get('/messages', [AdminMessageController::class, 'index'])->name('messages');
    Route::get('/audit-log', AdminAuditLogController::class)->name('audit-log');
    Route::get('/account/security', AdminAccountSecurityController::class)->name('account.security');
    Route::get('/admins', [AdminUserController::class, 'index'])->name('admins');
    Route::get('/profile', [AdminProfileController::class, 'index'])->name('profile');
});

// Standalone Two-Factor Challenge (docs/MODULES.md §6, ADR-14) — rendered
// outside the console shell. Frontend-first preview; the real gate is wired in
// the backend phase.
Route::get('/two-factor-challenge', TwoFactorChallengeController::class)->name('two-factor.challenge');

Route::post('/logout', LogoutController::class)
    ->middleware('auth')
    ->name('logout');
