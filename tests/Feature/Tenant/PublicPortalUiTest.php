<?php

use App\Domain\Tenancy\Models\JobPosting;
use App\Domain\Tenancy\Models\Tenant;
use App\Domain\Tenancy\Models\TenantPortalSetting;
use App\Domain\Tenancy\TenantContext;
use App\Domain\Tenancy\TenantPermissionCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('owner can view the portal settings ui', function () {
    actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, [
        'status' => 'active',
        'slug' => 'acme-robotics',
        'name' => 'Acme Robotics',
    ]);

    $this->get(route('settings.portal'))
        ->assertOk()
        ->assertSee('الموقع العام')
        ->assertSee('قسم البطل')
        ->assertSee('معاينة الموقع');
});

test('public portal pages render without authentication', function () {
    $tenant = Tenant::factory()->active()->create([
        'slug' => 'acme-robotics',
        'name' => 'Acme Robotics',
    ]);

    app(TenantContext::class)->setTenant($tenant);
    TenantPortalSetting::query()->create(TenantPortalSetting::defaultAttributes($tenant));
    JobPosting::factory()->published()->create([
        'tenant_id' => $tenant->id,
        'title' => 'أخصائي موارد بشرية أول',
        'slug' => 'senior-hr-specialist',
        'description' => 'قيادة عمليات التوظيف وتطوير السياسات.',
    ]);
    app(TenantContext::class)->setTenant(null);

    $this->get(route('portal.index', 'acme-robotics'))
        ->assertOk()
        ->assertSee('نحن نوظف الآن')
        ->assertSee('من نحن')
        ->assertSee('خدماتنا')
        ->assertSee('بيئة العمل')
        ->assertSee('هل أنت جاهز للانضمام لقصة نجاحنا؟');

    $this->get(route('portal.careers', 'acme-robotics'))
        ->assertOk()
        ->assertSee('الشواغر المتاحة')
        ->assertSee('أخصائي موارد بشرية أول');

    $this->get(route('portal.jobs.show', ['acme-robotics', 'senior-hr-specialist']))
        ->assertOk()
        ->assertSee('قدّم على هذه الوظيفة')
        ->assertSee('إرسال الطلب');

    $this->get(route('portal.contact', 'acme-robotics'))
        ->assertOk()
        ->assertSee('نحن هنا للإجابة على جميع استفساراتك')
        ->assertSee('أرسل رسالة');
});
