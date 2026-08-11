<?php

namespace App\Providers;

use App\Domain\Finance\Models\Expense;
use App\Domain\Finance\Models\OffboardingSettlement;
use App\Domain\Finance\Models\PayrollRun;
use App\Domain\Finance\Models\Payslip;
use App\Domain\Finance\Models\PayslipLineItem;
use App\Domain\Finance\Observers\ExpenseObserver;
use App\Domain\Finance\Observers\OffboardingSettlementObserver;
use App\Domain\Finance\Observers\PayrollRunObserver;
use App\Domain\Finance\Observers\PayslipLineItemObserver;
use App\Domain\Finance\Observers\PayslipObserver;
use App\Domain\Tenancy\ApprovableCatalog;
use App\Domain\Tenancy\Models\Employee;
use App\Domain\Tenancy\TenantContext;
use App\Listeners\Tenancy\NotifyHrOfOperationalEvents;
use App\Listeners\Tenancy\NotifyOwnersOfWaveEvents;
use App\Listeners\Tenancy\NotifyStaffOfHrEvents;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Bound as a singleton so the resolved tenant persists for the
        // lifetime of a single request. See docs/ARCHITECTURE.md §1.2.
        $this->app->singleton(TenantContext::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        /*
         * Morph-map aliases for polymorphic approval subjects (BR-902).
         *
         * Non-enforcing on purpose: Relation::enforceMorphMap() throws on write
         * for any unmapped model, and notifications.notifiable_type still stores
         * FQCNs. Promoting to enforcement is a separate, gated step (ADR-08).
         */
        Relation::morphMap(ApprovableCatalog::morphMap());

        $this->localizeFrameworkNotifications();

        /*
         * Financial immutability (BR-610, NFR-11). Registered here rather than
         * relying on route/policy checks so the guard covers every write path
         * that goes through a model instance.
         */
        PayrollRun::observe(PayrollRunObserver::class);
        Payslip::observe(PayslipObserver::class);
        PayslipLineItem::observe(PayslipLineItemObserver::class);
        Expense::observe(ExpenseObserver::class);
        OffboardingSettlement::observe(OffboardingSettlementObserver::class);

        /*
         * Standard Spatie pattern: Tenant Owner + platform Super Admin bypass
         * every ability via Gate::before. Return null for everyone else so
         * Spatie permission middleware / policies continue normally.
         *
         * Do NOT override User::hasPermissionTo()/checkPermissionTo() — that
         * interferes with Spatie's Gate registration and can 403 tenant routes.
         */
        Gate::before(function ($user, string $ability): ?bool {
            if (! $user instanceof User) {
                return null;
            }

            if ($user->isPlatformSuperAdmin() || $user->isOwner()) {
                return true;
            }

            return null;
        });

        Gate::define('hr.evaluations.access', function (User $user): bool {
            $employeeId = Employee::query()
                ->where('user_id', $user->id)
                ->value('id');

            $hasDirectReports = $employeeId !== null
                && Employee::query()->where('manager_id', $employeeId)->exists();

            return $hasDirectReports
                || $user->can('hr.evaluations.view_any')
                || $user->can('hr.evaluations.manage')
                || $user->can('hr.evaluations.approve');
        });

        Gate::define('hr.tasks.access', function (User $user): bool {
            $employeeId = Employee::query()
                ->where('user_id', $user->id)
                ->value('id');

            $hasDirectReports = $employeeId !== null
                && Employee::query()->where('manager_id', $employeeId)->exists();

            return $hasDirectReports || $user->can('hr.tasks.manage');
        });

        Event::subscribe(NotifyOwnersOfWaveEvents::class);
        Event::subscribe(NotifyHrOfOperationalEvents::class);
        Event::subscribe(NotifyStaffOfHrEvents::class);

        View::composer('*', function ($view): void {
            $settings = Schema::hasTable('settings')
                ? Setting::query()->pluck('value', 'key')->toArray()
                : [];

            $view->with('settings', $settings);
        });
    }

    /**
     * Arabic copy for the two notifications the framework ships itself.
     *
     * ─────────────────────────────────────────────────────────────────────
     * Every other message in this application is written in Arabic in its own
     * Mailable. These two are not ours — `ResetPassword` and `VerifyEmail`
     * come from the framework and build their own MailMessage in English.
     *
     * They then render inside the branded shell, which is pinned `dir="rtl"`
     * because all our copy is Arabic. The result was English sentences
     * right-aligned under an Arabic header and above an Arabic footer, on the
     * two emails that sit at the very start of the customer journey: the
     * verification link every signup receives, and password recovery.
     *
     * `toMailUsing` is the framework's own extension point, so no view is
     * published and no framework file is patched — an upgrade cannot silently
     * revert this the way an edited vendor view would.
     *
     * The URL construction below mirrors the framework's exactly: the reset
     * route name and payload, and the signed temporary URL for verification.
     * ─────────────────────────────────────────────────────────────────────
     */
    private function localizeFrameworkNotifications(): void
    {
        ResetPassword::toMailUsing(function (object $notifiable, string $token): MailMessage {
            $expiry = config('auth.passwords.'.config('auth.defaults.passwords').'.expire', 60);

            return (new MailMessage)
                ->subject('إعادة تعيين كلمة المرور — '.config('app.name'))
                ->greeting('مرحباً '.($notifiable->name ?? '').'،')
                ->line('وصلنا طلب لإعادة تعيين كلمة مرور حسابك. اضغط الزر أدناه لاختيار كلمة مرور جديدة.')
                ->action('إعادة تعيين كلمة المرور', url(route('password.reset', [
                    'token' => $token,
                    'email' => $notifiable->getEmailForPasswordReset(),
                ], false)))
                ->line('تنتهي صلاحية هذا الرابط خلال '.$expiry.' دقيقة.')
                ->line('إن لم تطلب إعادة التعيين فلا حاجة لأي إجراء، ولم يطرأ أي تغيير على حسابك.')
                ->salutation('مع التقدير، فريق '.config('app.name'));
        });

        VerifyEmail::toMailUsing(function (object $notifiable, string $url): MailMessage {
            return (new MailMessage)
                ->subject('تأكيد بريدك الإلكتروني — '.config('app.name'))
                ->greeting('مرحباً '.($notifiable->name ?? '').'،')
                ->line('يرجى تأكيد بريدك الإلكتروني لاستكمال تفعيل حساب مؤسستك.')
                ->action('تأكيد البريد الإلكتروني', $url)
                ->line('بعد التأكيد ينتقل طلب مؤسستك إلى مرحلة المراجعة، ويصلك إشعار فور اعتماده.')
                ->line('إن لم تنشئ هذا الحساب فلا حاجة لأي إجراء.')
                ->salutation('مع التقدير، فريق '.config('app.name'));
        });
    }
}
