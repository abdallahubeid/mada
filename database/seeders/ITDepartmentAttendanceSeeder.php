<?php

namespace Database\Seeders;

use App\Domain\Finance\Exceptions\WorkLedgerException;
use App\Domain\Tenancy\Actions\SeedDefaultTenantRoles;
use App\Domain\Tenancy\Enums\AttendanceStatus;
use App\Domain\Tenancy\Enums\ContractStatus;
use App\Domain\Tenancy\Enums\ContractType;
use App\Domain\Tenancy\Enums\EmployeeStatus;
use App\Domain\Tenancy\Enums\PayBasis;
use App\Domain\Tenancy\Models\Attendance;
use App\Domain\Tenancy\Models\Department;
use App\Domain\Tenancy\Models\Employee;
use App\Domain\Tenancy\Models\EmployeeContract;
use App\Domain\Tenancy\Models\OfficialHoliday;
use App\Domain\Tenancy\Models\OrgSetting;
use App\Domain\Tenancy\Models\Tenant;
use App\Domain\Tenancy\Models\WorkCalendar;
use App\Domain\Tenancy\TenantContext;
use App\Domain\Tenancy\TenantPermissionCatalog;
use App\Models\User;
use App\Services\Finance\WorkLedgerReconciler;
use BackedEnum;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;

/**
 * Seeds a complete, payroll-ready IT department for one tenant.
 *
 * Produces: an "IT" department, 10 employees with linked login accounts and
 * priced active contracts, 100% attendance for every working day of the target
 * month, and a reconciled Work Ledger — so a payroll run can be created for
 * that month immediately, with no absence deductions.
 *
 * Fully idempotent: every write is a firstOrCreate / updateOrCreate keyed on a
 * deterministic value, so running it repeatedly converges on the same state
 * rather than duplicating rows.
 *
 * Usage:
 *     php artisan db:seed --class=ITDepartmentAttendanceSeeder
 */
class ITDepartmentAttendanceSeeder extends Seeder
{
    /**
     * Target month as YYYY-MM.
     *
     * Override before calling run(), e.g. from tinker:
     *     $s = new ITDepartmentAttendanceSeeder; $s->period = '2026-09'; $s->run();
     */
    public string $period = '2026-08';

    /**
     * The tenant this seeder writes to — Ubeid.
     */
    public int $tenantId = 5;

    /**
     * Safety assertion: the tenant at {@see $tenantId} must carry this slug.
     *
     * Tenant ids are not portable between environments, and seeding an
     * unrelated customer's tenant with ten fake employees is not something you
     * discover quickly. Set to null to skip the check.
     */
    public ?string $expectedSlug = 'ubeid';

    private const DEFAULT_PASSWORD = 'password';

    private const CHECK_IN = '08:30';

    private const CHECK_OUT = '17:30';

    /**
     * The IT roster. `salary` is in MAJOR units (SAR) for readability here and
     * is converted to minor units on write — the system stores every monetary
     * value as an integer count of halalas (ADR-20).
     *
     * @var list<array{first: string, last: string, title: string, salary: int, lead: bool}>
     */
    private const ROSTER = [
        ['first' => 'ياسر', 'last' => 'العتيبي', 'title' => 'IT Manager', 'salary' => 28000, 'lead' => true],
        ['first' => 'محمد', 'last' => 'الشمري', 'title' => 'Backend Developer', 'salary' => 20000, 'lead' => false],
        ['first' => 'عبدالله', 'last' => 'القحطاني', 'title' => 'Backend Developer', 'salary' => 18500, 'lead' => false],
        ['first' => 'سارة', 'last' => 'الدوسري', 'title' => 'Frontend Developer', 'salary' => 19000, 'lead' => false],
        ['first' => 'نورة', 'last' => 'الغامدي', 'title' => 'Frontend Developer', 'salary' => 17500, 'lead' => false],
        ['first' => 'فهد', 'last' => 'الحربي', 'title' => 'DevOps Engineer', 'salary' => 23000, 'lead' => false],
        ['first' => 'ريم', 'last' => 'الزهراني', 'title' => 'UI/UX Designer', 'salary' => 16500, 'lead' => false],
        ['first' => 'خالد', 'last' => 'المطيري', 'title' => 'QA Engineer', 'salary' => 15000, 'lead' => false],
        ['first' => 'أحمد', 'last' => 'السبيعي', 'title' => 'Systems Administrator', 'salary' => 17000, 'lead' => false],
        ['first' => 'لمى', 'last' => 'البقمي', 'title' => 'IT Support Specialist', 'salary' => 12500, 'lead' => false],
    ];

    public function run(): void
    {
        $tenant = $this->resolveTenant();

        if ($tenant === null) {
            $this->command?->error('No active tenant found. Seed a tenant first (e.g. DemoTenantSeeder).');

            return;
        }

        $period = $this->period !== '' ? $this->period : now()->format('Y-m');

        if (preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $period) !== 1) {
            $this->command?->error("Invalid period '{$period}'. Expected YYYY-MM.");

            return;
        }

        $registrar = app(PermissionRegistrar::class);
        $previousTeamId = $registrar->getPermissionsTeamId();
        $context = app(TenantContext::class);
        $previousTenant = $context->getTenant();

        // Binds both the tenant global scope and Spatie's permission team id,
        // so every model below is stamped and every role lookup is scoped.
        $context->setTenant($tenant);

        try {
            $this->ensureRolesExist($tenant);
            $calendar = $this->ensureWorkCalendar($tenant);
            $currency = OrgSetting::query()->value('currency') ?? 'SAR';

            [$department, $employees] = DB::transaction(function () use ($tenant, $currency) {
                $department = $this->ensureDepartment();
                $employees = $this->ensureEmployees($tenant, $department, $currency);

                return [$department, $employees];
            });

            $start = Carbon::createFromFormat('Y-m-d', $period.'-01')->startOfDay();
            $end = $start->copy()->endOfMonth()->startOfDay();

            $recorded = DB::transaction(
                fn (): int => $this->recordFullAttendance($employees, $calendar, $start, $end)
            );

            $this->report($department, $employees, $period, $recorded);
            $this->reconcile($start, $end, $period);
        } finally {
            $context->setTenant($previousTenant);
            $registrar->setPermissionsTeamId($previousTeamId);
        }
    }

    private function resolveTenant(): ?Tenant
    {
        $tenant = Tenant::query()->find($this->tenantId);

        if ($tenant === null) {
            $this->command?->error("Tenant #{$this->tenantId} does not exist.");

            return null;
        }

        if ($this->expectedSlug !== null && $tenant->slug !== $this->expectedSlug) {
            $this->command?->error(
                "Tenant #{$this->tenantId} is '{$tenant->slug}', expected '{$this->expectedSlug}'. "
                .'Refusing to seed — set $expectedSlug to null to override.'
            );

            return null;
        }

        // status is a cast enum, not a string — interpolating it directly
        // throws "could not be converted to string".
        $status = $tenant->status instanceof BackedEnum ? $tenant->status->value : (string) $tenant->status;

        $this->command?->info("Target tenant: #{$tenant->id} {$tenant->name} ({$tenant->slug}) — {$status}");

        return $tenant;
    }

    /**
     * The Employee role must exist before accounts can be assigned to it.
     */
    private function ensureRolesExist(Tenant $tenant): void
    {
        app(SeedDefaultTenantRoles::class)->handle($tenant);
    }

    /**
     * Resolve the calendar attendance must be generated against.
     *
     * Deliberately reuses the tenant's EXISTING calendar and never edits it —
     * the working week is real customer configuration, not seed data. Tenant 5
     * runs a Friday-only weekend (`working_days` includes Saturday), and
     * imposing a Fri/Sat default here would mark every Saturday absent for ten
     * employees who were, per this tenant's own calendar, at work.
     *
     * The lookup mirrors WorkLedgerReconciler's `WorkCalendar::query()->first()`
     * exactly, so the seeder and the reconciler can never disagree about which
     * calendar is in force.
     */
    private function ensureWorkCalendar(Tenant $tenant): WorkCalendar
    {
        $calendar = WorkCalendar::query()->first();

        if ($calendar !== null) {
            $weekend = implode(', ', $calendar->resolvedWeekendDays());
            $this->command?->info("Using existing work calendar '{$calendar->name}' — weekend day(s): [{$weekend}]");

            return $calendar;
        }

        $this->command?->info('No work calendar found — creating a default Sun–Thu week.');

        return WorkCalendar::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Default',
            'working_days' => [0, 1, 2, 3, 4],
            'weekend_days' => [5, 6],
            'work_start_time' => self::CHECK_IN,
            'work_end_time' => self::CHECK_OUT,
            'grace_period_minutes' => 15,
        ]);
    }

    private function ensureDepartment(): Department
    {
        return Department::query()->firstOrCreate(
            ['name' => 'IT'],
            ['code' => 'IT', 'description' => 'قسم تقنية المعلومات'],
        );
    }

    /**
     * @return Collection<int, Employee>
     */
    private function ensureEmployees(Tenant $tenant, Department $department, string $currency)
    {
        $employees = collect();
        $lead = null;

        foreach (self::ROSTER as $index => $member) {
            $email = $this->emailFor($member, $index, $tenant);

            $user = User::query()->firstOrCreate(
                ['email' => $email],
                [
                    'tenant_id' => $tenant->id,
                    'department_id' => $department->id,
                    'name' => $member['first'].' '.$member['last'],
                    'job_title' => $member['title'],
                    'password' => Hash::make(self::DEFAULT_PASSWORD),
                    'is_active' => true,
                ],
            );

            // Team id is already bound above, so this resolves the tenant's own
            // Employee role. Drop the cached relation afterwards so any later
            // team-scoped check on this instance re-queries (see User::isTenantOwner).
            if (! $user->hasRole(TenantPermissionCatalog::ROLE_EMPLOYEE)) {
                $user->assignRole(TenantPermissionCatalog::ROLE_EMPLOYEE);
            }
            $user->unsetRelation('roles');

            $employee = Employee::query()->firstOrCreate(
                ['user_id' => $user->id],
                [
                    'department_id' => $department->id,
                    'manager_id' => $lead?->id,
                    'first_name' => $member['first'],
                    'last_name' => $member['last'],
                    'job_title' => $member['title'],
                    'national_id' => '1'.str_pad((string) (700000000 + $index), 9, '0', STR_PAD_LEFT),
                    'phone' => '+9665'.str_pad((string) (10000000 + $index), 8, '0', STR_PAD_LEFT),
                    'joining_date' => now()->subYears(3)->subMonths($index)->toDateString(),
                    'status' => EmployeeStatus::Active,
                ],
            );

            EmployeeContract::query()->firstOrCreate(
                ['employee_id' => $employee->id, 'status' => ContractStatus::Active->value],
                [
                    'contract_type' => ContractType::FullTime,
                    'pay_basis' => PayBasis::Salaried,
                    // Major -> minor units. Every monetary value in the system
                    // is an integer count of halalas.
                    'base_rate' => $member['salary'] * 100,
                    'pay_currency' => $currency,
                    'start_date' => $employee->joining_date?->toDateString() ?? now()->toDateString(),
                    'end_date' => null,
                ],
            );

            if ($member['lead']) {
                $lead = $employee;

                // Only claim the head seat if it is vacant. The IT department
                // may already exist in this tenant with a real head assigned,
                // and a seeder has no business overwriting that.
                if ($department->department_head_id === null) {
                    $department->update(['department_head_id' => $employee->id]);
                }
            }

            $employees->push($employee);
        }

        /*
         * Back-fill the reporting line, scoped to the employees THIS seeder
         * created — never "everyone in the IT department". The department may
         * already contain real employees, and a department-wide update would
         * silently reassign their line manager.
         */
        if ($lead !== null) {
            Employee::query()
                ->whereIn('id', $employees->pluck('id'))
                ->whereNull('manager_id')
                ->whereKeyNot($lead->id)
                ->update(['manager_id' => $lead->id]);
        }

        return $employees;
    }

    /**
     * Deterministic so re-running the seeder matches the same accounts.
     *
     * The roster index is part of the address on purpose: job titles REPEAT
     * (two Backend Developers, two Frontend Developers), so a title-only handle
     * collides and firstOrCreate silently reuses one account for both people —
     * leaving 8 employees where 10 were asked for. The index keeps every
     * address unique while staying stable across runs.
     */
    private function emailFor(array $member, int $index, Tenant $tenant): string
    {
        $handle = Str::slug(Str::ascii($member['title']), '-');

        // Tenant slug in the address keeps the ten accounts identifiable as
        // seed data, and scoped to the tenant they belong to.
        return sprintf('it.%02d.%s@%s.test', $index + 1, $handle, $tenant->slug ?: 'mada');
    }

    /**
     * Every account address this seeder owns — the exact set a revert removes.
     *
     * @return list<string>
     */
    public function seededEmails(Tenant $tenant): array
    {
        return array_values(array_map(
            fn (array $member, int $index): string => $this->emailFor($member, $index, $tenant),
            self::ROSTER,
            array_keys(self::ROSTER),
        ));
    }

    /**
     * Marks every working day of the period Present for every IT employee.
     *
     * Weekends come from the same calendar the reconciler reads, and official
     * holidays are skipped, so the resulting ledger contains no absent days.
     *
     * @param  Collection<int, Employee>  $employees
     * @return int rows written
     */
    private function recordFullAttendance($employees, WorkCalendar $calendar, Carbon $start, Carbon $end): int
    {
        $weekendDays = $calendar->resolvedWeekendDays();
        $holidays = OfficialHoliday::query()->get();
        $written = 0;

        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            if (in_array($date->dayOfWeek, $weekendDays, true)) {
                continue;
            }

            if (OfficialHoliday::isHoliday($date, $holidays)) {
                continue;
            }

            foreach ($employees as $employee) {
                Attendance::query()->updateOrCreate(
                    ['employee_id' => $employee->id, 'date' => $date->toDateString()],
                    [
                        'check_in' => $date->copy()->setTimeFromTimeString(self::CHECK_IN),
                        'check_out' => $date->copy()->setTimeFromTimeString(self::CHECK_OUT),
                        'status' => AttendanceStatus::Present,
                        'notes' => null,
                    ],
                );

                $written++;
            }
        }

        return $written;
    }

    /**
     * Rebuild the Work Ledger for the whole tenant, not just IT.
     *
     * Payroll's guard checks the entire period, and an employee outside IT with
     * no ledger rows at all would be priced against zero scheduled days. A
     * tenant-wide rebuild is the only state a payroll run can honestly consume.
     */
    private function reconcile(Carbon $start, Carbon $end, string $period): void
    {
        try {
            $rows = app(WorkLedgerReconciler::class)->reconcilePeriod($start, $end);

            $this->command?->info("  Work Ledger reconciled for {$period}: {$rows} day(s) across all active employees.");
            $this->command?->info("  A payroll run for {$period} can now be created.");
        } catch (WorkLedgerException $exception) {
            // A period already covered by an approved/paid run is frozen by
            // design (BR-407) — surface it rather than failing the seed.
            $this->command?->warn('  Work Ledger not rebuilt: '.$exception->getMessage());
        }
    }

    /**
     * @param  Collection<int, Employee>  $employees
     */
    private function report(Department $department, $employees, string $period, int $attendanceRows): void
    {
        $expected = count(self::ROSTER);
        $actual = $employees->unique('id')->count();

        $this->command?->info('IT department seeded:');
        $this->command?->info("  Department #{$department->id} — {$actual} employees");
        $this->command?->info("  Attendance: {$attendanceRows} row(s) for {$period}, all Present");
        $this->command?->info('  Login password for every seeded account: '.self::DEFAULT_PASSWORD);

        /*
         * Fail loudly on under-creation rather than reporting success over a
         * short roster. This caught a real bug: the account handle was derived
         * from the job title, and repeated titles collided on firstOrCreate,
         * quietly producing 8 employees instead of 10.
         */
        if ($actual !== $expected) {
            $this->command?->error(
                "  Expected {$expected} distinct employees but produced {$actual}. "
                .'Check that every roster entry resolves to a unique account handle.'
            );
        }
    }
}
