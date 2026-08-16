<?php

use App\Http\Controllers\Tenant\AnnouncementController;
use App\Http\Controllers\Tenant\AssetController;
use App\Http\Controllers\Tenant\AuditLogController;
use App\Http\Controllers\Tenant\CompanySettingController;
use App\Http\Controllers\Tenant\ContactMessageController;
use App\Http\Controllers\Tenant\ConversationController;
use App\Http\Controllers\Tenant\DashboardController;
use App\Http\Controllers\Tenant\DepartmentController;
use App\Http\Controllers\Tenant\EmployeeController;
use App\Http\Controllers\Tenant\Finance\ExpenseCategoryController;
use App\Http\Controllers\Tenant\Finance\ExpenseController;
use App\Http\Controllers\Tenant\Finance\FinanceDashboardController;
use App\Http\Controllers\Tenant\Finance\FinanceSettingController;
use App\Http\Controllers\Tenant\Finance\MyPayslipController;
use App\Http\Controllers\Tenant\Finance\OffboardingSettlementController;
use App\Http\Controllers\Tenant\Finance\PayrollRunController;
use App\Http\Controllers\Tenant\Finance\PayslipController;
use App\Http\Controllers\Tenant\Finance\PayslipLineItemTypeController;
use App\Http\Controllers\Tenant\HR\AttendanceController;
use App\Http\Controllers\Tenant\HR\ContractController;
use App\Http\Controllers\Tenant\HR\EmployeeEvaluationController;
use App\Http\Controllers\Tenant\HR\HrDashboardController;
use App\Http\Controllers\Tenant\HR\InterviewController;
use App\Http\Controllers\Tenant\HR\JobApplicationController;
use App\Http\Controllers\Tenant\HR\JobPostingController;
use App\Http\Controllers\Tenant\HR\LeaveController;
use App\Http\Controllers\Tenant\HR\MyAttendanceController;
use App\Http\Controllers\Tenant\HR\MyLeaveController;
use App\Http\Controllers\Tenant\HR\TaskController;
use App\Http\Controllers\Tenant\NotificationController;
use App\Http\Controllers\Tenant\OfficialHolidayController;
use App\Http\Controllers\Tenant\ProfileController;
use App\Http\Controllers\Tenant\PublicPortalController;
use App\Http\Controllers\Tenant\ReportController;
use App\Http\Controllers\Tenant\RoleController;
use App\Http\Controllers\Tenant\SetupWizardController;
use App\Http\Controllers\Tenant\SubscriptionController;
use App\Http\Controllers\Tenant\TeamController;
use App\Http\Controllers\Tenant\TrashController;
use App\Http\Controllers\Tenant\WorkScheduleController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Tenant application + public company portal
|--------------------------------------------------------------------------
|
| Authenticated tenant routes require auth/verified. Public careers portal
| lives at /companies/{slug}/* (docs/ARCHITECTURE.md §1.3, MODULES BR-302).
|
*/

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::middleware('tenant.context')->group(function (): void {
        Route::get('/dashboard/setup', [SetupWizardController::class, 'show'])
            ->name('dashboard.setup');
        Route::put('/dashboard/setup', [SetupWizardController::class, 'update'])
            ->name('dashboard.setup.update');
    });

    /*
     * `presence.touch` sits on the whole tenant app, not on the messenger.
     *
     * "متصل الآن" has to mean "using Mada", not "has the chat tab open" — a
     * colleague deep in payroll is reachable and should not read as offline.
     * The middleware is cache-throttled to one write per 55 seconds, so the
     * cost of it being here rather than on four messenger routes is a cache
     * lookup per request.
     */
    Route::middleware(['tenant.active', 'presence.touch'])->prefix('app')->group(function (): void {
        Route::get('/dashboard', [DashboardController::class, 'index'])
            ->middleware('permission:tenant.dashboard.view')
            ->name('dashboard');

        Route::get('/audit-logs', [AuditLogController::class, 'index'])
            ->middleware('permission:tenant.audit_logs.view')
            ->name('tenant.audit-logs.index');

        /*
         * Internal messenger.
         *
         * Deliberately gated on NOTHING beyond `tenant.active` and an
         * authenticated session. Every other module here carries a
         * `permission:` middleware, so the absence is worth stating: messaging
         * a colleague is not an administrative capability, and a permission
         * would imply someone can be granted access to OTHER people's threads.
         * Access is decided per conversation by participant membership, inside
         * the controller and the channel authorizer.
         */
        Route::prefix('messenger')->name('tenant.messenger.')->group(function (): void {
            Route::get('/', [ConversationController::class, 'index'])->name('index');
            Route::post('/', [ConversationController::class, 'store'])->name('store');
            Route::get('/{conversation}', [ConversationController::class, 'show'])
                ->whereNumber('conversation')
                ->name('show');
            Route::post('/{conversation}/messages', [ConversationController::class, 'send'])
                ->whereNumber('conversation')
                ->name('send');

            /*
             * Presence + read state for the open thread. Polled, because it
             * has to keep working with Reverb down — see the controller.
             */
            Route::get('/{conversation}/pulse', [ConversationController::class, 'pulse'])
                ->whereNumber('conversation')
                ->name('pulse');

            /*
             * Group creation is the ONE messaging capability behind a
             * permission. Everything else here is gated by participant
             * membership inside the controller — see the note above.
             */
            Route::post('/groups', [ConversationController::class, 'storeGroup'])
                ->middleware('permission:messaging.groups.create')
                ->name('groups.store');

            Route::post('/messages/{message}/react', [ConversationController::class, 'react'])
                ->whereNumber('message')
                ->name('react');

            Route::post('/messages/{message}/pin', [ConversationController::class, 'pin'])
                ->whereNumber('message')
                ->name('pin');

            /*
             * Attachment serving. These two routes are the ONLY way the bytes
             * on the `chat` disk can be read — that disk registers no route of
             * its own and exposes no URL. Both re-check conversation
             * membership per request; see findAttachmentFor().
             */
            Route::get('/attachments/{attachment}', [ConversationController::class, 'previewAttachment'])
                ->whereNumber('attachment')
                ->name('attachments.preview');

            Route::get('/attachments/{attachment}/download', [ConversationController::class, 'downloadAttachment'])
                ->whereNumber('attachment')
                ->name('attachments.download');

            // Author-only, enforced in the action — no role overrides it.
            Route::delete('/messages/{message}', [ConversationController::class, 'destroyMessage'])
                ->whereNumber('message')
                ->name('messages.destroy');

            // Membership is verified on BOTH the source and the destination.
            Route::post('/messages/{message}/forward', [ConversationController::class, 'forward'])
                ->whereNumber('message')
                ->name('forward');

            Route::put('/privacy', [ConversationController::class, 'updatePrivacy'])
                ->name('privacy.update');

            /*
             * Both act on the CALLER's participant row only — neither deletes
             * the conversation or any message, because the thread is shared
             * and the other party never agreed to lose their copy.
             */
            Route::post('/{conversation}/archive', [ConversationController::class, 'archive'])
                ->whereNumber('conversation')
                ->name('archive');

            Route::post('/{conversation}/hide', [ConversationController::class, 'hide'])
                ->whereNumber('conversation')
                ->name('hide');
        });

        Route::get('/trash', [TrashController::class, 'index'])
            ->middleware('can:tenant.trash.view_any')
            ->name('tenant.trash.index');
        Route::post('/trash/restore-selected', [TrashController::class, 'restoreSelected'])
            ->middleware('can:tenant.trash.restore')
            ->name('tenant.trash.restore-selected');
        Route::delete('/trash/force-selected', [TrashController::class, 'forceSelected'])
            ->middleware('can:tenant.trash.force_delete')
            ->name('tenant.trash.force-selected');
        Route::delete('/trash/empty', [TrashController::class, 'empty'])
            ->middleware('can:tenant.trash.force_delete')
            ->name('tenant.trash.empty');
        Route::post('/trash/{type}/{id}/restore', [TrashController::class, 'restore'])
            ->middleware('can:tenant.trash.restore')
            ->name('tenant.trash.restore');
        Route::delete('/trash/{type}/{id}/force-delete', [TrashController::class, 'forceDelete'])
            ->middleware('can:tenant.trash.force_delete')
            ->name('tenant.trash.force-delete');

        Route::get('/reports', [ReportController::class, 'index'])
            ->middleware('permission:tenant.reports.view')
            ->name('tenant.reports.index');

        Route::get('/reports/attendance', [ReportController::class, 'exportAttendance'])
            ->middleware('permission:tenant.reports.view')
            ->name('tenant.reports.attendance');

        Route::get('/reports/leaves', [ReportController::class, 'exportLeaves'])
            ->middleware('permission:tenant.reports.view')
            ->name('tenant.reports.leaves');

        Route::get('/reports/employees', [ReportController::class, 'exportEmployees'])
            ->middleware('permission:tenant.reports.view')
            ->name('tenant.reports.employees');

        Route::get('/reports/audit-logs', [ReportController::class, 'exportAuditLogs'])
            ->middleware('permission:tenant.audit_logs.view')
            ->name('tenant.reports.audit-logs');

        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');

        Route::get('/notifications', [NotificationController::class, 'index'])
            ->name('tenant.notifications.index');
        Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])
            ->name('tenant.notifications.read-all');
        Route::post('/notifications/{notification}/read', [NotificationController::class, 'markAsRead'])
            ->name('tenant.notifications.read');

        Route::get('/contact-messages', [ContactMessageController::class, 'index'])
            ->middleware('can:tenant.contact_messages.view_any')
            ->name('tenant.contact-messages.index');

        Route::get('/contact-messages/threads', [ContactMessageController::class, 'threads'])
            ->middleware('can:tenant.contact_messages.view_any')
            ->name('tenant.contact-messages.threads');

        Route::get('/contact-messages/{thread}', [ContactMessageController::class, 'show'])
            ->middleware('can:tenant.contact_messages.view_any')
            ->name('tenant.contact-messages.show');

        Route::post('/contact-messages/{thread}/reply', [ContactMessageController::class, 'reply'])
            ->middleware('can:tenant.contact_messages.manage')
            ->name('tenant.contact-messages.reply');

        Route::post('/contact-messages/{thread}/archive', [ContactMessageController::class, 'archive'])
            ->middleware('can:tenant.contact_messages.manage')
            ->name('tenant.contact-messages.archive');

        Route::post('/contact-messages/{thread}/unarchive', [ContactMessageController::class, 'unarchive'])
            ->middleware('can:tenant.contact_messages.manage')
            ->name('tenant.contact-messages.unarchive');

        Route::delete('/contact-messages/{thread}', [ContactMessageController::class, 'destroy'])
            ->middleware('can:tenant.contact_messages.manage')
            ->name('tenant.contact-messages.destroy');

        Route::get('/subscription', [SubscriptionController::class, 'index'])
            ->middleware('permission:tenant.subscription.view')
            ->name('tenant.subscription.index');

        Route::get('/subscription/invoices/{invoice}/download', [SubscriptionController::class, 'downloadInvoice'])
            ->middleware('permission:tenant.subscription.view')
            ->name('tenant.subscription.invoices.download');

        /*
         * Employee self-service. Each feature is a first-class route; the old
         * tabbed /my-space hub (MySpaceController) it replaced has been retired.
         */
        Route::get('/hr/my-attendance', [MyAttendanceController::class, 'index'])
            ->middleware('permission:hr.attendance.check_in_out')
            ->name('tenant.hr.my-attendance');

        Route::post('/hr/my-attendance/check-in', [MyAttendanceController::class, 'checkIn'])
            ->middleware('permission:hr.attendance.check_in_out')
            ->name('tenant.hr.my-attendance.check-in');

        Route::post('/hr/my-attendance/check-out', [MyAttendanceController::class, 'checkOut'])
            ->middleware('permission:hr.attendance.check_in_out')
            ->name('tenant.hr.my-attendance.check-out');

        Route::get('/hr/my-leaves', [MyLeaveController::class, 'index'])
            ->middleware('permission:hr.my_leaves.view')
            ->name('tenant.hr.my-leaves');

        Route::post('/hr/my-leaves', [MyLeaveController::class, 'store'])
            ->middleware('permission:hr.my_leaves.view')
            ->name('tenant.hr.my-leaves.store');

        Route::get('/hr/my-evaluations', [EmployeeEvaluationController::class, 'myEvaluations'])
            ->middleware('permission:hr.my_evaluations.view')
            ->name('tenant.hr.my-evaluations');

        Route::post('/hr/attendance/checkout', [AttendanceController::class, 'checkOutSelf'])
            ->middleware('permission:hr.attendance.check_in_out')
            ->name('tenant.hr.attendance.checkout');

        Route::get('/hr/dashboard', [HrDashboardController::class, 'index'])
            ->middleware('permission:hr.dashboard.view')
            ->name('tenant.hr.dashboard');

        Route::get('/hr/my-dashboard', [HrDashboardController::class, 'employee'])
            ->middleware('permission:hr.my_dashboard.view')
            ->name('tenant.hr.employee.dashboard');

        Route::get('/hr/my-tasks', [TaskController::class, 'myTasks'])
            ->middleware('permission:hr.my_tasks.view')
            ->name('tenant.hr.my-tasks');

        Route::post('/hr/my-tasks/{task}/status', [TaskController::class, 'updateStatus'])
            ->middleware('permission:hr.my_tasks.view')
            ->name('tenant.hr.my-tasks.status');

        Route::get('/settings/company', [CompanySettingController::class, 'edit'])
            ->middleware('permission:tenant.settings.view')
            ->name('settings.company');

        Route::put('/settings/company', [CompanySettingController::class, 'update'])
            ->middleware('permission:tenant.settings.update')
            ->name('settings.company.update');

        Route::get('/settings/work-schedule', [WorkScheduleController::class, 'edit'])
            ->middleware('permission:tenant.settings.view')
            ->name('settings.work-schedule');

        Route::put('/settings/work-schedule', [WorkScheduleController::class, 'update'])
            ->middleware('permission:tenant.settings.update')
            ->name('settings.work-schedule.update');

        Route::get('/announcements', [AnnouncementController::class, 'index'])
            ->middleware('permission:tenant.announcements.view_any')
            ->name('tenant.announcements.index');

        Route::post('/announcements', [AnnouncementController::class, 'store'])
            ->middleware('permission:tenant.announcements.manage')
            ->name('tenant.announcements.store');

        Route::put('/announcements/{announcement}', [AnnouncementController::class, 'update'])
            ->middleware('permission:tenant.announcements.manage')
            ->name('tenant.announcements.update');

        Route::delete('/announcements/{announcement}', [AnnouncementController::class, 'destroy'])
            ->middleware('permission:tenant.announcements.manage')
            ->name('tenant.announcements.destroy');

        Route::get('/holidays', [OfficialHolidayController::class, 'index'])
            ->middleware('permission:tenant.holidays.view_any')
            ->name('tenant.holidays.index');

        Route::post('/holidays', [OfficialHolidayController::class, 'store'])
            ->middleware('permission:tenant.holidays.manage')
            ->name('tenant.holidays.store');

        Route::put('/holidays/{officialHoliday}', [OfficialHolidayController::class, 'update'])
            ->middleware('permission:tenant.holidays.manage')
            ->name('tenant.holidays.update');

        Route::delete('/holidays/{officialHoliday}', [OfficialHolidayController::class, 'destroy'])
            ->middleware('permission:tenant.holidays.manage')
            ->name('tenant.holidays.destroy');

        Route::get('/assets', [AssetController::class, 'index'])
            ->middleware('permission:hr.assets.view_any')
            ->name('tenant.assets.index');

        Route::post('/assets', [AssetController::class, 'store'])
            ->middleware('permission:hr.assets.manage')
            ->name('tenant.assets.store');

        Route::put('/assets/{asset}', [AssetController::class, 'update'])
            ->middleware('permission:hr.assets.manage')
            ->name('tenant.assets.update');

        Route::post('/assets/{asset}/assign', [AssetController::class, 'assign'])
            ->middleware('permission:hr.assets.manage')
            ->name('tenant.assets.assign');

        Route::post('/assets/{asset}/return', [AssetController::class, 'returnAsset'])
            ->middleware('permission:hr.assets.manage')
            ->name('tenant.assets.return');

        Route::get('/assets/employee/{employee}', [AssetController::class, 'employeeCustody'])
            ->middleware('permission:hr.assets.view_any')
            ->name('tenant.assets.employee');

        Route::get('/settings/portal', [PublicPortalController::class, 'settings'])
            ->middleware('permission:tenant.settings.view')
            ->name('settings.portal');

        Route::put('/settings/portal', [PublicPortalController::class, 'updateSettings'])
            ->middleware('permission:tenant.settings.update')
            ->name('settings.portal.update');

        Route::middleware('permission:tenant.roles.manage')->group(function (): void {
            Route::get('/settings/roles', [RoleController::class, 'index'])->name('roles.index');
            Route::get('/settings/roles/create', [RoleController::class, 'create'])->name('roles.create');
            Route::post('/settings/roles', [RoleController::class, 'store'])->name('roles.store');
            Route::get('/settings/roles/{role}/edit', [RoleController::class, 'edit'])->name('roles.edit');
            Route::put('/settings/roles/{role}', [RoleController::class, 'update'])->name('roles.update');
            Route::delete('/settings/roles/{role}', [RoleController::class, 'destroy'])->name('roles.destroy');
        });

        Route::middleware('permission:tenant.users.manage')->group(function (): void {
            Route::get('/settings/team', [TeamController::class, 'index'])->name('team.index');
            Route::get('/settings/team/create', [TeamController::class, 'create'])->name('team.create');
            Route::post('/settings/team', [TeamController::class, 'store'])->name('team.store');
            Route::get('/settings/team/{user}/edit', [TeamController::class, 'edit'])->name('team.edit');
            Route::put('/settings/team/{user}', [TeamController::class, 'update'])->name('team.update');
            Route::delete('/settings/team/{user}', [TeamController::class, 'destroy'])->name('team.destroy');
            Route::patch('/settings/team/{user}/toggle-status', [TeamController::class, 'toggleStatus'])
                ->name('team.toggle-status');
        });
        Route::prefix('hr')->name('hr.')->group(function (): void {
            Route::get('/departments', [DepartmentController::class, 'index'])
                ->middleware('permission:hr.departments.view_any')
                ->name('departments.index');

            Route::get('/departments/create', [DepartmentController::class, 'create'])
                ->middleware('permission:hr.departments.create')
                ->name('departments.create');

            Route::post('/departments', [DepartmentController::class, 'store'])
                ->middleware('permission:hr.departments.create')
                ->name('departments.store');

            Route::get('/departments/{department}/edit', [DepartmentController::class, 'edit'])
                ->middleware('permission:hr.departments.update')
                ->name('departments.edit');

            Route::put('/departments/{department}', [DepartmentController::class, 'update'])
                ->middleware('permission:hr.departments.update')
                ->name('departments.update');

            Route::delete('/departments/{department}', [DepartmentController::class, 'destroy'])
                ->middleware('permission:hr.departments.delete')
                ->name('departments.destroy');

            Route::get('/employees', [EmployeeController::class, 'index'])
                ->middleware('permission:hr.employees.view_any')
                ->name('employees.index');

            Route::get('/employees/create', [EmployeeController::class, 'create'])
                ->middleware('permission:hr.employees.create')
                ->name('employees.create');

            Route::post('/employees', [EmployeeController::class, 'store'])
                ->middleware('permission:hr.employees.create')
                ->name('employees.store');

            Route::get('/employees/{employee}', [EmployeeController::class, 'show'])
                ->middleware('permission:hr.employees.view')
                ->name('employees.show');

            Route::get('/employees/{employee}/edit', [EmployeeController::class, 'edit'])
                ->middleware('permission:hr.employees.update')
                ->name('employees.edit');

            Route::put('/employees/{employee}', [EmployeeController::class, 'update'])
                ->middleware('permission:hr.employees.update')
                ->name('employees.update');

            Route::delete('/employees/{employee}', [EmployeeController::class, 'destroy'])
                ->middleware('permission:hr.employees.delete')
                ->name('employees.destroy');

            Route::get('/attendance', [AttendanceController::class, 'index'])
                ->middleware('permission:hr.attendance.view_any')
                ->name('attendance.index');

            Route::post('/attendance', [AttendanceController::class, 'store'])
                ->middleware('permission:hr.attendance.create')
                ->name('attendance.store');

            Route::put('/attendance/{attendance}', [AttendanceController::class, 'update'])
                ->middleware('permission:hr.attendance.update')
                ->name('attendance.update');

            Route::post('/attendance/check-in', [AttendanceController::class, 'checkIn'])
                ->middleware('permission:hr.attendance.create')
                ->name('attendance.check-in');

            Route::post('/attendance/check-out', [AttendanceController::class, 'checkOut'])
                ->middleware('permission:hr.attendance.update')
                ->name('attendance.check-out');

            Route::get('/leaves', [LeaveController::class, 'index'])
                ->middleware('permission:hr.leaves.view_any')
                ->name('leaves.index');

            Route::post('/leaves/types', [LeaveController::class, 'storeType'])
                ->middleware('permission:hr.leaves.manage_types')
                ->name('leaves.types.store');

            Route::post('/leaves/requests', [LeaveController::class, 'storeRequest'])
                ->middleware('permission:hr.leaves.create')
                ->name('leaves.requests.store');

            Route::post('/leaves/{leaveRequest}/approve', [LeaveController::class, 'approve'])
                ->middleware('permission:hr.leaves.approve')
                ->name('leaves.approve');

            Route::post('/leaves/{leaveRequest}/reject', [LeaveController::class, 'reject'])
                ->middleware('permission:hr.leaves.approve')
                ->name('leaves.reject');

            Route::get('/evaluations', [EmployeeEvaluationController::class, 'index'])
                ->middleware('can:hr.evaluations.access')
                ->name('evaluations.index');

            Route::post('/evaluations', [EmployeeEvaluationController::class, 'upsert'])
                ->middleware('can:hr.evaluations.access')
                ->name('evaluations.upsert');

            Route::post('/evaluations/approve', [EmployeeEvaluationController::class, 'approve'])
                ->middleware('can:hr.evaluations.access')
                ->name('evaluations.approve');

            Route::get('/tasks', [TaskController::class, 'index'])
                ->middleware('can:hr.tasks.access')
                ->name('tasks.index');

            Route::post('/tasks', [TaskController::class, 'store'])
                ->middleware('can:hr.tasks.access')
                ->name('tasks.store');

            Route::get('/contracts', [ContractController::class, 'index'])
                ->middleware('permission:hr.contracts.view_any')
                ->name('contracts.index');

            Route::get('/contracts/create', [ContractController::class, 'create'])
                ->middleware('permission:hr.contracts.create')
                ->name('contracts.create');

            Route::post('/contracts', [ContractController::class, 'store'])
                ->middleware('permission:hr.contracts.create')
                ->name('contracts.store');

            Route::get('/contracts/{contract}/edit', [ContractController::class, 'edit'])
                ->middleware('permission:hr.contracts.update')
                ->name('contracts.edit');

            Route::put('/contracts/{contract}', [ContractController::class, 'update'])
                ->middleware('permission:hr.contracts.update')
                ->name('contracts.update');

            Route::delete('/contracts/{contract}', [ContractController::class, 'destroy'])
                ->middleware('permission:hr.contracts.delete')
                ->name('contracts.destroy');

            Route::get('/jobs', [JobPostingController::class, 'index'])
                ->middleware('permission:hr.jobs.view_any')
                ->name('jobs.index');

            Route::get('/jobs/create', [JobPostingController::class, 'create'])
                ->middleware('permission:hr.jobs.create')
                ->name('jobs.create');

            Route::post('/jobs', [JobPostingController::class, 'store'])
                ->middleware('permission:hr.jobs.create')
                ->name('jobs.store');

            Route::get('/jobs/{job}/edit', [JobPostingController::class, 'edit'])
                ->middleware('permission:hr.jobs.update')
                ->name('jobs.edit');

            Route::put('/jobs/{job}', [JobPostingController::class, 'update'])
                ->middleware('permission:hr.jobs.update')
                ->name('jobs.update');

            Route::patch('/jobs/{job}/status', [JobPostingController::class, 'updateStatus'])
                ->middleware('permission:hr.jobs.update')
                ->name('jobs.status');

            Route::delete('/jobs/{job}', [JobPostingController::class, 'destroy'])
                ->middleware('permission:hr.jobs.delete')
                ->name('jobs.destroy');

            Route::get('/applications', [JobApplicationController::class, 'index'])
                ->middleware('permission:hr.applications.view_any')
                ->name('applications.index');

            Route::get('/applications/{application}', [JobApplicationController::class, 'show'])
                ->middleware('permission:hr.applications.view')
                ->name('applications.show');

            Route::put('/applications/{application}', [JobApplicationController::class, 'update'])
                ->middleware('permission:hr.applications.update')
                ->name('applications.update');

            Route::delete('/applications/{application}', [JobApplicationController::class, 'destroy'])
                ->middleware('permission:hr.applications.delete')
                ->name('applications.destroy');

            Route::post('/applications/{application}/convert', [JobApplicationController::class, 'convertToEmployee'])
                ->middleware('permission:hr.applications.convert')
                ->name('applications.convert');

            /*
             * Interview scheduling (Phase 3 ATS extension).
             *
             * Nested under the application because an interview has no meaning
             * without one. Both routes carry `hr.recruitment.manage`; the Owner
             * reaches them through the Gate::before bypass. The preview endpoint
             * is gated identically to the send — it echoes the candidate's
             * address and the composed message, so it is not public surface.
             */
            Route::post('/applications/{application}/interviews', [InterviewController::class, 'store'])
                ->middleware('permission:hr.recruitment.manage')
                ->name('applications.interviews.store');

            Route::post('/applications/{application}/interviews/preview', [InterviewController::class, 'preview'])
                ->middleware('permission:hr.recruitment.manage')
                ->name('applications.interviews.preview');
        });

        /*
         * Finance & Payroll (Phase 2A).
         *
         * Note the permission split: `prepare` covers create/edit/delete of a
         * DRAFT, while `approve` is deliberately held by a different role.
         * Granting both to one role would collapse maker-checker (ADR-09), and
         * the model layer asserts approver != maker regardless (BR-615).
         */
        /*
         * Dashboard sits outside the `finance.` name group so it matches the
         * `tenant.hr.dashboard` convention — dashboards are named `tenant.*`,
         * module CRUD is named after the module.
         */
        Route::get('/finance/dashboard', [FinanceDashboardController::class, 'index'])
            ->middleware('permission:finance.dashboard.view')
            ->name('tenant.finance.dashboard');

        Route::get('/finance/my-payslips', [MyPayslipController::class, 'index'])
            ->middleware('permission:hr.my_payslips.view')
            ->name('tenant.finance.my-payslips');

        Route::prefix('finance')->name('finance.')->group(function (): void {
            Route::get('/payroll-runs', [PayrollRunController::class, 'index'])
                ->middleware('permission:finance.payroll.view_any')
                ->name('payroll-runs.index');

            Route::get('/payroll-runs/create', [PayrollRunController::class, 'create'])
                ->middleware('permission:finance.payroll.prepare')
                ->name('payroll-runs.create');

            Route::post('/payroll-runs', [PayrollRunController::class, 'store'])
                ->middleware('permission:finance.payroll.prepare')
                ->name('payroll-runs.store');

            Route::get('/payroll-runs/{payrollRun}', [PayrollRunController::class, 'show'])
                ->middleware('permission:finance.payroll.view_any')
                ->name('payroll-runs.show');

            Route::get('/payroll-runs/{payrollRun}/edit', [PayrollRunController::class, 'edit'])
                ->middleware('permission:finance.payroll.prepare')
                ->name('payroll-runs.edit');

            Route::put('/payroll-runs/{payrollRun}', [PayrollRunController::class, 'update'])
                ->middleware('permission:finance.payroll.prepare')
                ->name('payroll-runs.update');

            Route::delete('/payroll-runs/{payrollRun}', [PayrollRunController::class, 'destroy'])
                ->middleware('permission:finance.payroll.delete')
                ->name('payroll-runs.destroy');

            Route::post('/payroll-runs/{payrollRun}/recalculate', [PayrollRunController::class, 'recalculate'])
                ->middleware('permission:finance.payroll.prepare')
                ->name('payroll-runs.recalculate');

            // BR-603 correction path: this DRAFT carries an adjustment for an
            // earlier locked run. The locked run is never touched.
            Route::post('/payroll-runs/{payrollRun}/adjustments', [PayrollRunController::class, 'adjust'])
                ->middleware('permission:finance.payroll.prepare')
                ->name('payroll-runs.adjustments.store');

            Route::post('/payroll-runs/{payrollRun}/submit', [PayrollRunController::class, 'submit'])
                ->middleware('permission:finance.payroll.prepare')
                ->name('payroll-runs.submit');

            Route::post('/payroll-runs/{payrollRun}/approve', [PayrollRunController::class, 'approve'])
                ->middleware('permission:finance.payroll.approve')
                ->name('payroll-runs.approve');

            Route::post('/payroll-runs/{payrollRun}/reject', [PayrollRunController::class, 'reject'])
                ->middleware('permission:finance.payroll.approve')
                ->name('payroll-runs.reject');

            Route::post('/payroll-runs/{payrollRun}/disburse', [PayrollRunController::class, 'disburse'])
                ->middleware('permission:finance.payroll.pay')
                ->name('payroll-runs.disburse');

            /*
             * Payslip routes carry NO permission middleware: they serve two
             * audiences at one URL — finance staff viewing anyone's payslip and
             * an employee viewing their own. PayslipController::authorizeView()
             * resolves which, per BR-614.
             */
            Route::get('/payslips/{payslip}', [PayslipController::class, 'show'])
                ->name('payslips.show');

            Route::get('/payslips/{payslip}/print', [PayslipController::class, 'print'])
                ->name('payslips.print');

            /*
             * Expenses (BR-613). `manage` covers create/edit/delete of a draft;
             * `approve` is a separate ability, and the actions additionally
             * assert submitter != decider — an employee approving their own
             * reimbursement is the primary abuse this workflow prevents.
             */
            Route::get('/expenses', [ExpenseController::class, 'index'])
                ->middleware('permission:finance.expenses.view_any')
                ->name('expenses.index');

            Route::get('/expenses/create', [ExpenseController::class, 'create'])
                ->middleware('permission:finance.expenses.manage')
                ->name('expenses.create');

            Route::post('/expenses', [ExpenseController::class, 'store'])
                ->middleware('permission:finance.expenses.manage')
                ->name('expenses.store');

            Route::get('/expenses/{expense}', [ExpenseController::class, 'show'])
                ->middleware('permission:finance.expenses.view_any')
                ->name('expenses.show');

            Route::get('/expenses/{expense}/edit', [ExpenseController::class, 'edit'])
                ->middleware('permission:finance.expenses.manage')
                ->name('expenses.edit');

            Route::put('/expenses/{expense}', [ExpenseController::class, 'update'])
                ->middleware('permission:finance.expenses.manage')
                ->name('expenses.update');

            Route::delete('/expenses/{expense}', [ExpenseController::class, 'destroy'])
                ->middleware('permission:finance.expenses.manage')
                ->name('expenses.destroy');

            Route::post('/expenses/{expense}/submit', [ExpenseController::class, 'submit'])
                ->middleware('permission:finance.expenses.manage')
                ->name('expenses.submit');

            Route::post('/expenses/{expense}/approve', [ExpenseController::class, 'approve'])
                ->middleware('permission:finance.expenses.approve')
                ->name('expenses.approve');

            Route::post('/expenses/{expense}/reject', [ExpenseController::class, 'reject'])
                ->middleware('permission:finance.expenses.approve')
                ->name('expenses.reject');

            Route::post('/expenses/{expense}/disburse', [ExpenseController::class, 'disburse'])
                ->middleware('permission:finance.expenses.pay')
                ->name('expenses.disburse');

            Route::middleware('permission:finance.expense_categories.manage')->group(function (): void {
                Route::get('/expense-categories', [ExpenseCategoryController::class, 'index'])
                    ->name('expense-categories.index');
                Route::get('/expense-categories/create', [ExpenseCategoryController::class, 'create'])
                    ->name('expense-categories.create');
                Route::post('/expense-categories', [ExpenseCategoryController::class, 'store'])
                    ->name('expense-categories.store');
                Route::get('/expense-categories/{expenseCategory}/edit', [ExpenseCategoryController::class, 'edit'])
                    ->name('expense-categories.edit');
                Route::put('/expense-categories/{expenseCategory}', [ExpenseCategoryController::class, 'update'])
                    ->name('expense-categories.update');
                Route::delete('/expense-categories/{expenseCategory}', [ExpenseCategoryController::class, 'destroy'])
                    ->name('expense-categories.destroy');
            });

            /*
             * Offboarding settlements (BR-606). `manage` prepares, `approve`
             * both approves and disburses — disbursement is what terminates the
             * contract and revokes access, so it sits with the checker.
             */
            Route::get('/offboarding', [OffboardingSettlementController::class, 'index'])
                ->middleware('permission:finance.offboarding.view_any')
                ->name('offboarding.index');

            Route::get('/offboarding/create', [OffboardingSettlementController::class, 'create'])
                ->middleware('permission:finance.offboarding.manage')
                ->name('offboarding.create');

            Route::post('/offboarding', [OffboardingSettlementController::class, 'store'])
                ->middleware('permission:finance.offboarding.manage')
                ->name('offboarding.store');

            Route::get('/offboarding/{offboarding}', [OffboardingSettlementController::class, 'show'])
                ->middleware('permission:finance.offboarding.view_any')
                ->name('offboarding.show');

            Route::get('/offboarding/{offboarding}/print', [OffboardingSettlementController::class, 'print'])
                ->middleware('permission:finance.offboarding.view_any')
                ->name('offboarding.print');

            Route::post('/offboarding/{offboarding}/submit', [OffboardingSettlementController::class, 'submit'])
                ->middleware('permission:finance.offboarding.manage')
                ->name('offboarding.submit');

            Route::delete('/offboarding/{offboarding}', [OffboardingSettlementController::class, 'destroy'])
                ->middleware('permission:finance.offboarding.manage')
                ->name('offboarding.destroy');

            Route::post('/offboarding/{offboarding}/approve', [OffboardingSettlementController::class, 'approve'])
                ->middleware('permission:finance.offboarding.approve')
                ->name('offboarding.approve');

            Route::post('/offboarding/{offboarding}/disburse', [OffboardingSettlementController::class, 'disburse'])
                ->middleware('permission:finance.offboarding.approve')
                ->name('offboarding.disburse');

            /*
             * Finance settings — the tenant's EOSB rules. Held by the Owner and
             * the Finance Manager only; no maker-checker split, because this
             * screen sets rules rather than authorizing a payment, and every
             * settlement snapshots the rules it was computed under.
             */
            Route::middleware('permission:finance.settings.manage')->group(function (): void {
                Route::get('/settings', [FinanceSettingController::class, 'edit'])
                    ->name('settings.edit');

                Route::put('/settings', [FinanceSettingController::class, 'update'])
                    ->name('settings.update');

                Route::post('/settings/reset', [FinanceSettingController::class, 'reset'])
                    ->name('settings.reset');
            });

            Route::middleware('permission:finance.line_item_types.manage')->group(function (): void {
                Route::get('/line-item-types', [PayslipLineItemTypeController::class, 'index'])
                    ->name('line-item-types.index');

                Route::get('/line-item-types/create', [PayslipLineItemTypeController::class, 'create'])
                    ->name('line-item-types.create');

                Route::post('/line-item-types', [PayslipLineItemTypeController::class, 'store'])
                    ->name('line-item-types.store');

                Route::get('/line-item-types/{lineItemType}/edit', [PayslipLineItemTypeController::class, 'edit'])
                    ->name('line-item-types.edit');

                Route::put('/line-item-types/{lineItemType}', [PayslipLineItemTypeController::class, 'update'])
                    ->name('line-item-types.update');

                Route::delete('/line-item-types/{lineItemType}', [PayslipLineItemTypeController::class, 'destroy'])
                    ->name('line-item-types.destroy');
            });
        });
    });
});

Route::prefix('companies/{slug}')
    ->where(['slug' => '[a-z0-9]+(?:-[a-z0-9]+)*'])
    ->name('portal.')
    ->group(function (): void {
        Route::get('/', [PublicPortalController::class, 'index'])->name('index');
        Route::get('/careers', [PublicPortalController::class, 'careers'])->name('careers');
        Route::get('/careers/{job}', [PublicPortalController::class, 'jobDetail'])->name('jobs.show');
        Route::post('/careers/{job}/apply', [PublicPortalController::class, 'applyForJob'])->name('jobs.apply');
        Route::get('/contact', [PublicPortalController::class, 'contact'])->name('contact');
        Route::post('/contact', [PublicPortalController::class, 'storeContact'])
            ->middleware('throttle:10,1')
            ->name('contact.store');
    });
