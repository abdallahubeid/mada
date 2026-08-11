<?php

use App\Domain\Tenancy\Enums\EvaluationPeriodType;
use App\Domain\Tenancy\Enums\EvaluationStatus;
use App\Domain\Tenancy\Models\Department;
use App\Domain\Tenancy\Models\Employee;
use App\Domain\Tenancy\Models\EmployeeEvaluation;
use App\Domain\Tenancy\Models\OrgSetting;
use App\Domain\Tenancy\TenantPermissionCatalog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('owner can view hierarchical evaluations dashboard with period filters', function () {
    $user = actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);

    $department = Department::factory()->create([
        'tenant_id' => $user->tenant_id,
        'name' => 'المبيعات',
    ]);

    $employee = Employee::factory()->create([
        'tenant_id' => $user->tenant_id,
        'department_id' => $department->id,
        'first_name' => 'ليان',
        'last_name' => 'الخالد',
    ]);

    $department->update(['department_head_id' => $employee->id]);

    $this->get(route('hr.evaluations.index', [
        'period_type' => 'quarterly',
        'period_key' => '2026-Q3',
    ]))
        ->assertOk()
        ->assertSee('تقييمات الأداء', false)
        ->assertSee('إعتماد التقييم', false)
        ->assertSee('المبيعات', false)
        ->assertSee('ليان الخالد', false)
        ->assertSee('رئيس القسم', false);
});

test('line manager only sees direct reports and can submit ratings', function () {
    $owner = actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);

    $managerUser = User::factory()->create([
        'tenant_id' => $owner->tenant_id,
        'is_active' => true,
    ]);
    $managerUser->assignRole(TenantPermissionCatalog::ROLE_EMPLOYEE);

    $manager = Employee::factory()->create([
        'tenant_id' => $owner->tenant_id,
        'user_id' => $managerUser->id,
        'first_name' => 'مدير',
        'last_name' => 'مباشر',
    ]);

    $report = Employee::factory()->create([
        'tenant_id' => $owner->tenant_id,
        'manager_id' => $manager->id,
        'first_name' => 'مرؤوس',
        'last_name' => 'أول',
    ]);

    $other = Employee::factory()->create([
        'tenant_id' => $owner->tenant_id,
        'first_name' => 'خارج',
        'last_name' => 'النطاق',
    ]);

    $this->actingAs($managerUser)
        ->get(route('hr.evaluations.index', [
            'period_type' => 'monthly',
            'period_key' => '2026-M08',
        ]))
        ->assertOk()
        ->assertSee('مرؤوس أول', false)
        ->assertDontSee('خارج النطاق', false)
        ->assertSee('عرض المدير المباشر', false);

    $this->actingAs($managerUser)
        ->post(route('hr.evaluations.upsert'), [
            'period_type' => 'monthly',
            'period_key' => '2026-M08',
            'intent' => 'submit',
            'rows' => [
                $report->id => [
                    'employee_id' => $report->id,
                    'rating' => 4.5,
                    'notes' => 'أداء ممتاز',
                ],
                $other->id => [
                    'employee_id' => $other->id,
                    'rating' => 1,
                    'notes' => 'should be ignored',
                ],
            ],
        ])
        ->assertRedirect(route('hr.evaluations.index', [
            'period_type' => 'monthly',
            'period_key' => '2026-M08',
        ]));

    $evaluation = EmployeeEvaluation::query()
        ->where('employee_id', $report->id)
        ->where('period_key', '2026-M08')
        ->first();

    expect($evaluation)->not->toBeNull()
        ->and((float) $evaluation->rating)->toBe(4.5)
        ->and($evaluation->status)->toBe(EvaluationStatus::Submitted)
        ->and($evaluation->evaluator_id)->toBe($manager->id)
        ->and(EmployeeEvaluation::query()->where('employee_id', $other->id)->exists())->toBeFalse();
});

test('owner can approve and lock evaluations for a period', function () {
    $user = actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);

    $employee = Employee::factory()->create([
        'tenant_id' => $user->tenant_id,
    ]);

    $evaluation = EmployeeEvaluation::factory()->create([
        'tenant_id' => $user->tenant_id,
        'employee_id' => $employee->id,
        'period_type' => EvaluationPeriodType::Quarterly,
        'period_key' => '2026-Q1',
        'rating' => 3.5,
        'status' => EvaluationStatus::Submitted,
    ]);

    $this->post(route('hr.evaluations.approve'), [
        'period_type' => 'quarterly',
        'period_key' => '2026-Q1',
    ])->assertRedirect(route('hr.evaluations.index', [
        'period_type' => 'quarterly',
        'period_key' => '2026-Q1',
    ]));

    expect($evaluation->fresh()->status)->toBe(EvaluationStatus::Approved);

    $this->post(route('hr.evaluations.upsert'), [
        'period_type' => 'quarterly',
        'period_key' => '2026-Q1',
        'intent' => 'submit',
        'rows' => [
            $employee->id => [
                'employee_id' => $employee->id,
                'rating' => 1,
                'notes' => 'blocked',
            ],
        ],
    ])->assertRedirect();

    expect((float) $evaluation->fresh()->rating)->toBe(3.5);
});

test('company settings store default evaluation periodicity', function () {
    actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);

    $this->put(route('settings.company.update'), [
        'currency' => 'SAR',
        'timezone' => 'Asia/Riyadh',
        'evaluation_periodicity' => 'annually',
        'working_days' => [0, 1, 2, 3, 4],
        'holidays' => [],
    ])->assertRedirect(route('settings.company'));

    $settings = OrgSetting::query()->first();

    expect($settings?->evaluation_periodicity)->toBe(EvaluationPeriodType::Annually);

    $this->get(route('hr.evaluations.index'))
        ->assertOk()
        ->assertSee('سنوي', false);
});

test('employee without reports cannot access evaluations hub', function () {
    actingAsTenantUser(TenantPermissionCatalog::ROLE_EMPLOYEE, ['status' => 'active']);

    $this->get(route('hr.evaluations.index'))->assertForbidden();
});
