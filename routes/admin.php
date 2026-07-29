<?php

use App\Http\Controllers\Admin\AccountSecurityController as AdminAccountSecurityController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\AiFeatureController as AdminAiFeatureController;
use App\Http\Controllers\Admin\AuditLogController as AdminAuditLogController;
use App\Http\Controllers\Admin\ChromeController as AdminChromeController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\FaqController as AdminFaqController;
use App\Http\Controllers\Admin\FeatureController as AdminFeatureController;
use App\Http\Controllers\Admin\MessageController as AdminMessageController;
use App\Http\Controllers\Admin\ModuleController as AdminModuleController;
use App\Http\Controllers\Admin\NewsletterCampaignController as AdminNewsletterCampaignController;
use App\Http\Controllers\Admin\NewsletterController as AdminNewsletterController;
use App\Http\Controllers\Admin\NotificationController as AdminNotificationController;
use App\Http\Controllers\Admin\OfferingController as AdminOfferingController;
use App\Http\Controllers\Admin\PlanController as AdminPlanController;
use App\Http\Controllers\Admin\ProblemController as AdminProblemController;
use App\Http\Controllers\Admin\ProfileController as AdminProfileController;
use App\Http\Controllers\Admin\RoleController as AdminRoleController;
use App\Http\Controllers\Admin\SearchController as AdminSearchController;
use App\Http\Controllers\Admin\SettingController as AdminSettingController;
use App\Http\Controllers\Admin\SolutionController as AdminSolutionController;
use App\Http\Controllers\Admin\TenantController as AdminTenantController;
use App\Http\Controllers\Admin\TestimonialController as AdminTestimonialController;
use App\Http\Controllers\Admin\TrashController as AdminTrashController;
use Illuminate\Support\Facades\Route;

/**
 * Super Admin / Platform Console (docs/MODULES.md §6, BR-807).
 *
 * Loaded from bootstrap/app.php with middleware [web, auth, platform.operator],
 * URI prefix `admin`, and route name prefix `admin.`.
 */
Route::get('/dashboard', AdminDashboardController::class)
    ->middleware('permission:dashboard.view')
    ->name('dashboard');

Route::get('/tenants', [AdminTenantController::class, 'index'])
    ->middleware('permission:tenants.view_any')
    ->name('tenants');
Route::get('/tenants/{tenant}', [AdminTenantController::class, 'show'])
    ->middleware('permission:tenants.view')
    ->name('tenants.show');
Route::put('/tenants/{tenant}/marketing', [AdminTenantController::class, 'updateMarketing'])
    ->middleware('permission:tenants.update')
    ->name('tenants.marketing');

Route::get('/plans', [AdminPlanController::class, 'index'])
    ->middleware('permission:plans.view_any')
    ->name('plans');
Route::post('/plans', [AdminPlanController::class, 'store'])
    ->middleware('permission:plans.create')
    ->name('plans.store');
Route::put('/plans/{plan}', [AdminPlanController::class, 'update'])
    ->middleware('permission:plans.update')
    ->name('plans.update');
Route::delete('/plans/{plan}', [AdminPlanController::class, 'destroy'])
    ->middleware('permission:plans.delete')
    ->name('plans.destroy');

Route::middleware('permission:faqs.view_any')->group(function () {
    Route::get('faqs', [AdminFaqController::class, 'index'])->name('faqs.index');
    Route::get('faqs/create', [AdminFaqController::class, 'create'])->middleware('permission:faqs.create')->name('faqs.create');
    Route::post('faqs', [AdminFaqController::class, 'store'])->middleware('permission:faqs.create')->name('faqs.store');
    Route::get('faqs/{faq}/edit', [AdminFaqController::class, 'edit'])->middleware('permission:faqs.update')->name('faqs.edit');
    Route::put('faqs/{faq}', [AdminFaqController::class, 'update'])->middleware('permission:faqs.update')->name('faqs.update');
    Route::patch('faqs/{faq}', [AdminFaqController::class, 'update'])->middleware('permission:faqs.update');
    Route::delete('faqs/{faq}', [AdminFaqController::class, 'destroy'])->middleware('permission:faqs.delete')->name('faqs.destroy');
});

Route::middleware('permission:cms.view_any')->group(function () {
    Route::get('problems', [AdminProblemController::class, 'index'])->name('problems.index');
    Route::get('problems/create', [AdminProblemController::class, 'create'])->middleware('permission:cms.create')->name('problems.create');
    Route::post('problems', [AdminProblemController::class, 'store'])->middleware('permission:cms.create')->name('problems.store');
    Route::get('problems/{problem}/edit', [AdminProblemController::class, 'edit'])->middleware('permission:cms.update')->name('problems.edit');
    Route::match(['put', 'patch'], 'problems/{problem}', [AdminProblemController::class, 'update'])->middleware('permission:cms.update')->name('problems.update');
    Route::delete('problems/{problem}', [AdminProblemController::class, 'destroy'])->middleware('permission:cms.delete')->name('problems.destroy');

    Route::get('solutions', [AdminSolutionController::class, 'index'])->name('solutions.index');
    Route::get('solutions/create', [AdminSolutionController::class, 'create'])->middleware('permission:cms.create')->name('solutions.create');
    Route::post('solutions', [AdminSolutionController::class, 'store'])->middleware('permission:cms.create')->name('solutions.store');
    Route::get('solutions/{solution}/edit', [AdminSolutionController::class, 'edit'])->middleware('permission:cms.update')->name('solutions.edit');
    Route::match(['put', 'patch'], 'solutions/{solution}', [AdminSolutionController::class, 'update'])->middleware('permission:cms.update')->name('solutions.update');
    Route::delete('solutions/{solution}', [AdminSolutionController::class, 'destroy'])->middleware('permission:cms.delete')->name('solutions.destroy');

    Route::get('offerings', [AdminOfferingController::class, 'index'])->name('offerings.index');
    Route::get('offerings/create', [AdminOfferingController::class, 'create'])->middleware('permission:cms.create')->name('offerings.create');
    Route::post('offerings', [AdminOfferingController::class, 'store'])->middleware('permission:cms.create')->name('offerings.store');
    Route::get('offerings/{offering}/edit', [AdminOfferingController::class, 'edit'])->middleware('permission:cms.update')->name('offerings.edit');
    Route::match(['put', 'patch'], 'offerings/{offering}', [AdminOfferingController::class, 'update'])->middleware('permission:cms.update')->name('offerings.update');
    Route::delete('offerings/{offering}', [AdminOfferingController::class, 'destroy'])->middleware('permission:cms.delete')->name('offerings.destroy');

    Route::get('modules', [AdminModuleController::class, 'index'])->name('modules.index');
    Route::get('modules/create', [AdminModuleController::class, 'create'])->middleware('permission:cms.create')->name('modules.create');
    Route::post('modules', [AdminModuleController::class, 'store'])->middleware('permission:cms.create')->name('modules.store');
    Route::get('modules/{module}/edit', [AdminModuleController::class, 'edit'])->middleware('permission:cms.update')->name('modules.edit');
    Route::match(['put', 'patch'], 'modules/{module}', [AdminModuleController::class, 'update'])->middleware('permission:cms.update')->name('modules.update');
    Route::delete('modules/{module}', [AdminModuleController::class, 'destroy'])->middleware('permission:cms.delete')->name('modules.destroy');

    Route::get('ai-features', [AdminAiFeatureController::class, 'index'])->name('ai-features.index');
    Route::get('ai-features/create', [AdminAiFeatureController::class, 'create'])->middleware('permission:cms.create')->name('ai-features.create');
    Route::post('ai-features', [AdminAiFeatureController::class, 'store'])->middleware('permission:cms.create')->name('ai-features.store');
    Route::get('ai-features/{ai_feature}/edit', [AdminAiFeatureController::class, 'edit'])->middleware('permission:cms.update')->name('ai-features.edit');
    Route::match(['put', 'patch'], 'ai-features/{ai_feature}', [AdminAiFeatureController::class, 'update'])->middleware('permission:cms.update')->name('ai-features.update');
    Route::delete('ai-features/{ai_feature}', [AdminAiFeatureController::class, 'destroy'])->middleware('permission:cms.delete')->name('ai-features.destroy');

    Route::get('features', [AdminFeatureController::class, 'index'])->name('features.index');
    Route::get('features/create', [AdminFeatureController::class, 'create'])->middleware('permission:cms.create')->name('features.create');
    Route::post('features', [AdminFeatureController::class, 'store'])->middleware('permission:cms.create')->name('features.store');
    Route::get('features/{feature}/edit', [AdminFeatureController::class, 'edit'])->middleware('permission:cms.update')->name('features.edit');
    Route::match(['put', 'patch'], 'features/{feature}', [AdminFeatureController::class, 'update'])->middleware('permission:cms.update')->name('features.update');
    Route::delete('features/{feature}', [AdminFeatureController::class, 'destroy'])->middleware('permission:cms.delete')->name('features.destroy');

    Route::get('testimonials', [AdminTestimonialController::class, 'index'])->name('testimonials.index');
    Route::get('testimonials/create', [AdminTestimonialController::class, 'create'])->middleware('permission:cms.create')->name('testimonials.create');
    Route::post('testimonials', [AdminTestimonialController::class, 'store'])->middleware('permission:cms.create')->name('testimonials.store');
    Route::get('testimonials/{testimonial}/edit', [AdminTestimonialController::class, 'edit'])->middleware('permission:cms.update')->name('testimonials.edit');
    Route::match(['put', 'patch'], 'testimonials/{testimonial}', [AdminTestimonialController::class, 'update'])->middleware('permission:cms.update')->name('testimonials.update');
    Route::delete('testimonials/{testimonial}', [AdminTestimonialController::class, 'destroy'])->middleware('permission:cms.delete')->name('testimonials.destroy');
});

Route::get('/landing/settings', [AdminSettingController::class, 'edit'])
    ->middleware('permission:settings.view')
    ->name('landing.settings.edit');
Route::put('/landing/settings', [AdminSettingController::class, 'update'])
    ->middleware('permission:settings.update')
    ->name('landing.settings.update');
Route::delete('/landing/settings/image/{key}', [AdminSettingController::class, 'destroyImage'])
    ->middleware('permission:settings.update')
    ->name('landing.settings.image.destroy');

Route::get('/chrome/poll', [AdminChromeController::class, 'poll'])
    ->middleware('permission:dashboard.view')
    ->name('chrome.poll');
Route::get('/search', [AdminSearchController::class, 'index'])
    ->middleware('permission:dashboard.view')
    ->name('search');
Route::get('/search/suggest', [AdminSearchController::class, 'suggest'])
    ->middleware('permission:dashboard.view')
    ->name('search.suggest');

Route::get('/notifications', [AdminNotificationController::class, 'index'])
    ->middleware('permission:notifications.view_any')
    ->name('notifications');
Route::post('/notifications/read-all', [AdminNotificationController::class, 'markAllRead'])
    ->middleware('permission:notifications.update')
    ->name('notifications.read-all');
Route::delete('/notifications', [AdminNotificationController::class, 'destroyAll'])
    ->middleware('permission:notifications.delete')
    ->name('notifications.destroy-all');
Route::put('/notifications/{notification}/read', [AdminNotificationController::class, 'toggleRead'])
    ->middleware('permission:notifications.update')
    ->name('notifications.toggle-read');
Route::delete('/notifications/{notification}', [AdminNotificationController::class, 'destroy'])
    ->middleware('permission:notifications.delete')
    ->name('notifications.destroy');

Route::get('/messages', [AdminMessageController::class, 'index'])
    ->middleware('permission:support.view_any')
    ->name('messages');
Route::get('/messages/poll', [AdminMessageController::class, 'poll'])
    ->middleware('permission:support.view_any')
    ->name('messages.poll');
Route::post('/messages/{thread}/reply', [AdminMessageController::class, 'reply'])
    ->middleware('permission:support.reply')
    ->name('messages.reply');
Route::put('/messages/{thread}/status', [AdminMessageController::class, 'updateStatus'])
    ->middleware('permission:support.update')
    ->name('messages.status');
Route::post('/messages/{thread}/archive', [AdminMessageController::class, 'archive'])
    ->middleware('permission:support.update')
    ->name('messages.archive');
Route::delete('/messages/{thread}', [AdminMessageController::class, 'destroy'])
    ->middleware('permission:support.delete')
    ->name('messages.destroy');

Route::get('/newsletter', [AdminNewsletterController::class, 'index'])
    ->middleware('permission:newsletters.view_any')
    ->name('newsletter.index');
Route::get('/newsletter/poll', [AdminNewsletterController::class, 'poll'])
    ->middleware('permission:newsletters.view_any')
    ->name('newsletter.poll');
Route::get('/newsletter/export', [AdminNewsletterController::class, 'export'])
    ->middleware('permission:newsletters.export')
    ->name('newsletter.export');
Route::get('/newsletter/campaigns', [AdminNewsletterCampaignController::class, 'index'])
    ->middleware('permission:newsletters.view_any')
    ->name('newsletter.campaigns.index');
Route::get('/newsletter/campaigns/{campaign}', [AdminNewsletterCampaignController::class, 'show'])
    ->middleware('permission:newsletters.view_any')
    ->name('newsletter.campaigns.show');
Route::post('/newsletter/campaign', [AdminNewsletterController::class, 'sendCampaign'])
    ->middleware('permission:newsletters.campaigns.send')
    ->name('newsletter.campaign');
Route::put('/newsletter/{subscriber}/toggle', [AdminNewsletterController::class, 'toggle'])
    ->middleware('permission:newsletters.update')
    ->name('newsletter.toggle');
Route::delete('/newsletter/{subscriber}', [AdminNewsletterController::class, 'destroy'])
    ->middleware('permission:newsletters.delete')
    ->name('newsletter.destroy');

Route::get('/audit-log', AdminAuditLogController::class)
    ->middleware('permission:audit_logs.view_any')
    ->name('audit-log');

Route::get('/trash', [AdminTrashController::class, 'index'])
    ->middleware('permission:trash.view_any')
    ->name('trash.index');
Route::post('/trash/restore-selected', [AdminTrashController::class, 'restoreSelected'])
    ->middleware('permission:trash.restore')
    ->name('trash.restore-selected');
Route::delete('/trash/force-selected', [AdminTrashController::class, 'forceSelected'])
    ->middleware('permission:trash.force_delete')
    ->name('trash.force-selected');
Route::delete('/trash/empty', [AdminTrashController::class, 'empty'])
    ->middleware('permission:trash.force_delete')
    ->name('trash.empty');
Route::post('/trash/{type}/{id}/restore', [AdminTrashController::class, 'restore'])
    ->middleware('permission:trash.restore')
    ->name('trash.restore');
Route::delete('/trash/{type}/{id}', [AdminTrashController::class, 'forceDestroy'])
    ->middleware('permission:trash.force_delete')
    ->name('trash.force-destroy');

Route::get('/account/security', AdminAccountSecurityController::class)
    ->middleware('permission:account.security.view')
    ->name('account.security');

Route::get('/admins', [AdminUserController::class, 'index'])
    ->middleware('permission:admins.view_any')
    ->name('admins');
Route::get('/admins/create', [AdminUserController::class, 'create'])
    ->middleware('permission:admins.create')
    ->name('admins.create');
Route::post('/admins', [AdminUserController::class, 'store'])
    ->middleware('permission:admins.create')
    ->name('admins.store');
Route::get('/admins/{admin}/edit', [AdminUserController::class, 'edit'])
    ->middleware('permission:admins.update')
    ->name('admins.edit');
Route::put('/admins/{admin}', [AdminUserController::class, 'update'])
    ->middleware('permission:admins.update')
    ->name('admins.update');
Route::delete('/admins/{admin}', [AdminUserController::class, 'destroy'])
    ->middleware('permission:admins.delete')
    ->name('admins.destroy');

Route::get('/roles', [AdminRoleController::class, 'index'])
    ->middleware('permission:roles.view_any')
    ->name('roles.index');
Route::get('/roles/create', [AdminRoleController::class, 'create'])
    ->middleware('permission:roles.create')
    ->name('roles.create');
Route::post('/roles', [AdminRoleController::class, 'store'])
    ->middleware('permission:roles.create')
    ->name('roles.store');
Route::get('/roles/{role}/edit', [AdminRoleController::class, 'edit'])
    ->middleware('permission:roles.update')
    ->name('roles.edit');
Route::put('/roles/{role}', [AdminRoleController::class, 'update'])
    ->middleware('permission:roles.update')
    ->name('roles.update');
Route::delete('/roles/{role}', [AdminRoleController::class, 'destroy'])
    ->middleware('permission:roles.delete')
    ->name('roles.destroy');

Route::get('/profile', [AdminProfileController::class, 'index'])
    ->middleware('permission:account.profile.update')
    ->name('profile');
Route::put('/profile', [AdminProfileController::class, 'update'])
    ->middleware('permission:account.profile.update')
    ->name('profile.update');
