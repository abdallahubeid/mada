<?php

namespace App\Domain\Tenancy;

use App\Domain\Finance\Models\Expense;
use App\Domain\Finance\Models\ExpenseCategory;
use App\Domain\Finance\Models\OffboardingSettlement;
use App\Domain\Finance\Models\PayrollRun;
use App\Domain\Finance\Models\PayslipLineItemType;
use App\Domain\Tenancy\Models\Announcement;
use App\Domain\Tenancy\Models\Asset;
use App\Domain\Tenancy\Models\Department;
use App\Domain\Tenancy\Models\Employee;
use App\Domain\Tenancy\Models\EmployeeContract;
use App\Domain\Tenancy\Models\EmployeeEvaluation;
use App\Domain\Tenancy\Models\JobApplication;
use App\Domain\Tenancy\Models\JobPosting;
use App\Domain\Tenancy\Models\LeaveRequest;
use App\Domain\Tenancy\Models\OfficialHoliday;
use App\Domain\Tenancy\Models\TenantContactThread;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use InvalidArgumentException;

/**
 * Registry of soft-deletable tenant resources shown in سلة المحذوفات (/app/trash).
 *
 * @phpstan-type TrashResourceConfig array{
 *     label: string,
 *     model: class-string<Model>,
 *     title: callable(Model): string,
 *     subtitle?: callable(Model): ?string,
 *     query?: callable(Builder): Builder
 * }
 */
final class TrashableResourceCatalog
{
    /**
     * @return array<string, TrashResourceConfig>
     */
    public static function all(): array
    {
        return [
            'employees' => [
                'label' => 'الموظفين',
                'model' => Employee::class,
                'title' => fn (Model $m): string => (string) ($m->getAttribute('full_name') ?: trim(($m->getAttribute('first_name') ?? '').' '.($m->getAttribute('last_name') ?? ''))),
                'subtitle' => fn (Model $m): ?string => $m->getAttribute('job_title') ?: $m->getAttribute('national_id'),
            ],
            'assets' => [
                'label' => 'العُهد',
                'model' => Asset::class,
                'title' => fn (Model $m): string => (string) $m->getAttribute('name'),
                'subtitle' => fn (Model $m): ?string => $m->getAttribute('asset_code'),
            ],
            'leave-requests' => [
                'label' => 'طلبات الإجازات',
                'model' => LeaveRequest::class,
                'title' => function (Model $m): string {
                    /** @var LeaveRequest $m */
                    $employee = $m->relationLoaded('employee') ? $m->employee : $m->employee()->withTrashed()->first();

                    return $employee?->full_name ?? 'طلب إجازة #'.$m->getKey();
                },
                'subtitle' => function (Model $m): ?string {
                    $start = $m->getAttribute('start_date');
                    $end = $m->getAttribute('end_date');

                    if ($start === null || $end === null) {
                        return null;
                    }

                    return $start->format('Y-m-d').' → '.$end->format('Y-m-d');
                },
            ],
            'evaluations' => [
                'label' => 'تقييمات الأداء',
                'model' => EmployeeEvaluation::class,
                'title' => function (Model $m): string {
                    /** @var EmployeeEvaluation $m */
                    $employee = $m->relationLoaded('employee') ? $m->employee : $m->employee()->withTrashed()->first();

                    return ($employee?->full_name ?? 'تقييم').' · '.$m->getAttribute('period_key');
                },
                'subtitle' => fn (Model $m): ?string => $m->getAttribute('period_type')?->label(),
            ],
            'contracts' => [
                'label' => 'العقود',
                'model' => EmployeeContract::class,
                'title' => function (Model $m): string {
                    /** @var EmployeeContract $m */
                    $employee = $m->relationLoaded('employee') ? $m->employee : $m->employee()->withTrashed()->first();

                    return $employee?->full_name ?? 'عقد #'.$m->getKey();
                },
                'subtitle' => fn (Model $m): ?string => (string) ($m->getAttribute('contract_type')?->value ?? $m->getAttribute('contract_type') ?? ''),
            ],
            'contact-messages' => [
                'label' => 'رسائل التواصل',
                'model' => TenantContactThread::class,
                'title' => fn (Model $m): string => (string) $m->getAttribute('subject'),
                'subtitle' => fn (Model $m): ?string => $m->getAttribute('sender_email') ?: $m->getAttribute('sender_name'),
            ],
            'departments' => [
                'label' => 'الأقسام',
                'model' => Department::class,
                'title' => fn (Model $m): string => (string) $m->getAttribute('name'),
            ],
            /*
             * Only DRAFT and CANCELLED runs can ever appear here: the
             * PayrollRunObserver refuses to delete an approved or paid run at
             * all (BR-617 as amended), so a locked financial record never
             * reaches the trash and can never be purged from it.
             */
            'payroll-runs' => [
                'label' => 'مسيرات الرواتب',
                'model' => PayrollRun::class,
                'title' => fn (Model $m): string => 'مسيرة رواتب '.$m->getAttribute('period'),
                'subtitle' => fn (Model $m): ?string => $m->getAttribute('status')?->label(),
            ],
            'expenses' => [
                'label' => 'المصروفات',
                'model' => Expense::class,
                'title' => fn (Model $m): string => (string) $m->getAttribute('title'),
                'subtitle' => fn (Model $m): ?string => $m->getAttribute('status')?->label(),
            ],
            'expense-categories' => [
                'label' => 'تصنيفات المصروفات',
                'model' => ExpenseCategory::class,
                'title' => fn (Model $m): string => (string) $m->getAttribute('name'),
            ],
            /*
             * Only DRAFT settlements reach here: the observer refuses to delete
             * an approved or paid one, exactly like payroll runs (BR-617).
             */
            'offboarding-settlements' => [
                'label' => 'تسويات نهاية الخدمة',
                'model' => OffboardingSettlement::class,
                'title' => fn (Model $m): string => 'تسوية '.$m->getAttribute('employee_name'),
                'subtitle' => fn (Model $m): ?string => $m->getAttribute('status')?->label(),
            ],
            'line-item-types' => [
                'label' => 'بنود البدلات والاستقطاعات',
                'model' => PayslipLineItemType::class,
                'title' => fn (Model $m): string => (string) $m->getAttribute('name'),
                'subtitle' => fn (Model $m): ?string => $m->getAttribute('kind')?->label(),
            ],
            'job-postings' => [
                'label' => 'الوظائف',
                'model' => JobPosting::class,
                'title' => fn (Model $m): string => (string) $m->getAttribute('title'),
            ],
            'job-applications' => [
                'label' => 'طلبات التقديم',
                'model' => JobApplication::class,
                'title' => fn (Model $m): string => (string) $m->getAttribute('applicant_name'),
                'subtitle' => fn (Model $m): ?string => $m->getAttribute('email'),
            ],
            'announcements' => [
                'label' => 'التعميمات',
                'model' => Announcement::class,
                'title' => fn (Model $m): string => (string) $m->getAttribute('title'),
            ],
            'holidays' => [
                'label' => 'العطلات الرسمية',
                'model' => OfficialHoliday::class,
                'title' => fn (Model $m): string => (string) $m->getAttribute('name'),
                'subtitle' => function (Model $m): ?string {
                    $start = $m->getAttribute('start_date');
                    $end = $m->getAttribute('end_date');

                    if ($start === null) {
                        return null;
                    }

                    $startStr = $start->format('Y-m-d');
                    $endStr = $end?->format('Y-m-d');

                    return $endStr && $endStr !== $startStr ? "{$startStr} → {$endStr}" : $startStr;
                },
            ],
            'team-users' => [
                'label' => 'أعضاء الفريق',
                'model' => User::class,
                'title' => fn (Model $m): string => (string) $m->getAttribute('name'),
                'subtitle' => fn (Model $m): ?string => $m->getAttribute('email'),
                'query' => function (Builder $q): Builder {
                    $tenantId = app(TenantContext::class)->getTenantId();

                    return $q->where('tenant_id', $tenantId);
                },
            ],
        ];
    }

    /**
     * @return list<string>
     */
    public static function keys(): array
    {
        return array_keys(self::all());
    }

    /**
     * @return TrashResourceConfig
     */
    public static function get(string $type): array
    {
        $all = self::all();

        if (! isset($all[$type])) {
            throw new InvalidArgumentException("Unknown trashable type [{$type}].");
        }

        return $all[$type];
    }

    public static function assertSoftDeletable(string $type): void
    {
        $modelClass = self::get($type)['model'];

        if (! in_array(SoftDeletes::class, class_uses_recursive($modelClass), true)) {
            throw new InvalidArgumentException("Model [{$modelClass}] does not use SoftDeletes.");
        }
    }
}
