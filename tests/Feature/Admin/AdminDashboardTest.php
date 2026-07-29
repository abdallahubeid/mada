<?php

use App\Domain\Tenancy\Models\Tenant;
use App\Models\Plan;
use App\Models\PlatformAuditLog;
use App\Models\User;
use App\Services\Admin\AdminDashboard;
use Database\Seeders\FaqSeeder;
use Database\Seeders\PlanSeeder;
use Database\Seeders\TestimonialSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

beforeEach(function () {
    actingAsPlatformOperator();
});

beforeEach(function () {
    $this->seed([
        PlanSeeder::class,
        FaqSeeder::class,
        TestimonialSeeder::class,
    ]);

    AdminDashboard::flush();
});

test('admin dashboard renders live tenant metrics from the database', function () {
    Tenant::factory()->active()->create(['name' => 'شركة نشطة', 'plan' => 'growth']);
    Tenant::factory()->pendingApproval()->create(['name' => 'شركة بانتظار', 'plan' => 'startup']);
    Tenant::factory()->suspended()->create(['name' => 'شركة موقوفة', 'plan' => 'startup']);

    $pending = Tenant::factory()->pendingApproval()->create(['name' => 'مؤسسة الانتظار', 'plan' => 'growth']);
    User::factory()->create([
        'tenant_id' => $pending->id,
        'name' => 'خالد العتيبي',
        'email' => 'khaled@wait.test',
    ]);

    $this->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('إجمالي المستأجرين', false)
        ->assertSee('شركة بانتظار', false)
        ->assertSee('مؤسسة الانتظار', false)
        ->assertSee('خالد العتيبي', false)
        ->assertSee('توزيع الخطط', false)
        ->assertSee('أحدث التسجيلات', false)
        ->assertSee('حالة النظام', false)
        ->assertSee('الأساسية', false)
        ->assertSee('النمو', false);
});

test('admin dashboard respects range query and caches aggregate metrics', function () {
    Tenant::factory()->active()->count(2)->create(['plan' => 'startup']);

    Cache::flush();

    $this->get(route('admin.dashboard', ['range' => '7d']))
        ->assertOk()
        ->assertSee('المستأجرون النشطون', false);

    expect(Cache::has('admin.dashboard.metrics.7d'))->toBeTrue()
        ->and(Cache::has('admin.dashboard.distribution'))->toBeTrue();

    $payload = app(AdminDashboard::class)->build('7d');

    expect($payload['range'])->toBe('7d')
        ->and((int) str_replace(',', '', $payload['metrics']['active']['value']))->toBe(2)
        ->and($payload['distribution'])->toBeArray()
        ->and($payload['planBreakdown'])->not->toBeEmpty()
        ->and($payload['systemStatus'])->not->toBeEmpty();
});

test('admin dashboard estimated mrr sums active tenant plan prices', function () {
    $growth = Plan::query()->where('slug', 'growth')->firstOrFail();

    Tenant::factory()->active()->count(2)->create(['plan' => 'growth']);
    Tenant::factory()->suspended()->create(['plan' => 'growth']);

    AdminDashboard::flush();

    $payload = app(AdminDashboard::class)->build('30d');
    $expected = (float) $growth->price_monthly * 2;

    expect($payload['metrics']['mrr']['value'])->toContain((string) (int) $expected);
});

test('admin dashboard activity prefers platform audit logs when present', function () {
    PlatformAuditLog::query()->create([
        'user_id' => null,
        'action' => 'plan.updated',
        'subject_type' => Plan::class,
        'subject_id' => Plan::query()->first()->id,
        'meta' => null,
        'ip_address' => '127.0.0.1',
    ]);

    AdminDashboard::flush();

    $this->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('حدّث خطة', false);
});
