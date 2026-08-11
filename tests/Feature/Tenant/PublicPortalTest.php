<?php

use App\Domain\Tenancy\Models\Department;
use App\Domain\Tenancy\Models\JobPosting;
use App\Domain\Tenancy\Models\Tenant;
use App\Domain\Tenancy\Models\TenantPortalSetting;
use App\Domain\Tenancy\TenantContext;
use App\Domain\Tenancy\TenantPermissionCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * @param  array<string, mixed>  $overrides
 */
function seedPublicPortalTenant(array $overrides = [], array $settingOverrides = []): Tenant
{
    $tenant = Tenant::factory()->active()->create(array_merge([
        'slug' => 'acme-robotics',
        'name' => 'Acme Robotics',
    ], $overrides));

    app(TenantContext::class)->setTenant($tenant);

    TenantPortalSetting::query()->create(array_merge(
        TenantPortalSetting::defaultAttributes($tenant),
        $settingOverrides,
    ));

    app(TenantContext::class)->setTenant(null);

    return $tenant;
}

/**
 * @return array<string, mixed>
 */
function portalSettingsPayload(array $overrides = []): array
{
    return array_merge([
        'is_portal_enabled' => '1',
        'is_hero_active' => '1',
        'hero_badge_text' => 'نحن نوظف الآن',
        'hero_title' => 'عنوان بطل مخصص',
        'hero_subtitle' => 'وصف البطل المخصص',
        'hero_primary_cta_text' => 'استكشف الشواغر',
        'hero_primary_cta_url' => 'careers',
        'hero_secondary_cta_text' => 'تواصل معنا',
        'hero_secondary_cta_url' => 'contact',
        'is_about_active' => '1',
        'about_title' => 'عنوان من نحن',
        'about_subtitle' => 'من نحن',
        'vision_text' => 'رؤية مخصصة',
        'mission_text' => 'رسالة مخصصة',
        'values_json' => [
            ['title' => 'قيمة 1', 'desc' => 'وصف قيمة'],
        ],
        'is_services_active' => '1',
        'services_title' => 'خدمات مخصصة',
        'services_subtitle' => 'خدماتنا',
        'services_json' => [
            ['title' => 'خدمة 1', 'description' => 'وصف خدمة', 'icon' => 'tech'],
        ],
        'is_culture_active' => '1',
        'culture_title' => 'ثقافة مخصصة',
        'culture_subtitle' => 'بيئة العمل',
        'culture_perks_json' => [
            ['title' => 'ميزة 1', 'description' => 'وصف ميزة'],
        ],
        'is_stats_active' => '1',
        'stats_title' => 'إحصائيات',
        'stats_json' => [
            ['label' => 'الموظفون', 'value' => '50', 'suffix' => '+'],
        ],
        'is_careers_active' => '1',
        'careers_badge_text' => 'الوظائف',
        'careers_title' => 'فرص مميزة الآن',
        'careers_subtitle' => 'وصف الوظائف',
        'is_faq_active' => '1',
        'faq_title' => 'أسئلة مخصصة',
        'faq_subtitle' => 'الأسئلة الشائعة',
        'faqs_json' => [
            ['question' => 'سؤال مخصص؟', 'answer' => 'جواب مخصص'],
        ],
        'is_cta_active' => '1',
        'cta_title' => 'هل أنت جاهز للانضمام لقصة نجاحنا؟',
        'cta_subtitle' => 'وصف CTA',
        'cta_button_text' => 'تصفّح الشواغر',
        'is_contact_active' => '1',
        'contact_email' => 'hr@acme.test',
        'contact_phone' => '+966 11 111 1111',
        'contact_address' => 'الرياض',
        'office_hours' => 'الأحد — الخميس',
        'map_embed_url' => 'https://maps.google.com/maps?q=Riyadh&output=embed',
    ], $overrides);
}

test('owner can view and update portal settings', function () {
    $user = actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, [
        'status' => 'active',
        'slug' => 'acme-robotics',
        'name' => 'Acme Robotics',
    ]);

    $this->get(route('settings.portal'))
        ->assertOk()
        ->assertSee('الموقع العام')
        ->assertSee('قسم البطل')
        ->assertSee('معاينة الموقع');

    $this->put(route('settings.portal.update'), portalSettingsPayload([
        'hero_title' => 'انضم لفريق أكمي',
        'faq_title' => 'أسئلة أكمي',
    ]))
        ->assertRedirect(route('settings.portal'))
        ->assertSessionHas('flasher');

    app(TenantContext::class)->setTenant($user->tenant);

    $settings = TenantPortalSetting::query()->first();
    expect($settings)->not->toBeNull()
        ->and($settings->hero_title)->toBe('انضم لفريق أكمي')
        ->and($settings->faq_title)->toBe('أسئلة أكمي')
        ->and($settings->contact_email)->toBe('hr@acme.test')
        ->and($settings->faqs_json[0]['question'])->toBe('سؤال مخصص؟')
        ->and($settings->services_json[0]['icon'])->toBe('tech')
        ->and($settings->is_portal_enabled)->toBeTrue();
});

test('hr manager can view portal settings but cannot update them', function () {
    actingAsTenantUser(TenantPermissionCatalog::ROLE_HR_MANAGER, [
        'status' => 'active',
        'slug' => 'acme-robotics',
    ]);

    $this->get(route('settings.portal'))
        ->assertOk()
        ->assertSee('عرض فقط');

    $this->put(route('settings.portal.update'), portalSettingsPayload())
        ->assertForbidden();

    expect(TenantPortalSetting::query()->count())->toBe(0);
});

test('public portal renders dynamic content from settings defaults', function () {
    $tenant = seedPublicPortalTenant();

    app(TenantContext::class)->setTenant($tenant);
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
        ->assertSee('هل أنت جاهز للانضمام لقصة نجاحنا؟')
        ->assertSee('Acme Robotics');

    $this->get(route('portal.careers', 'acme-robotics'))
        ->assertOk()
        ->assertSee('أخصائي موارد بشرية أول');

    $this->get(route('portal.jobs.show', ['acme-robotics', 'senior-hr-specialist']))
        ->assertOk()
        ->assertSee('قدّم على هذه الوظيفة');

    $this->get(route('portal.contact', 'acme-robotics'))
        ->assertOk()
        ->assertSee('نحن هنا للإجابة على جميع استفساراتك');
});

test('disabling a homepage section hides it from the public portal', function () {
    seedPublicPortalTenant([], [
        'is_faq_active' => false,
        'faq_title' => 'قسم الأسئلة المخفي',
        'hero_title' => 'بطل ظاهر',
    ]);

    $this->get(route('portal.index', 'acme-robotics'))
        ->assertOk()
        ->assertSee('بطل ظاهر')
        ->assertDontSee('قسم الأسئلة المخفي');
});

test('global portal kill-switch blocks public access', function () {
    seedPublicPortalTenant([], [
        'is_portal_enabled' => false,
    ]);

    $this->get(route('portal.index', 'acme-robotics'))
        ->assertNotFound()
        ->assertSee('الموقع العام غير متاح حالياً');

    $this->get(route('portal.careers', 'acme-robotics'))
        ->assertNotFound();

    $this->get(route('portal.contact', 'acme-robotics'))
        ->assertNotFound();
});

test('careers page filters jobs by department query', function () {
    $tenant = seedPublicPortalTenant();

    app(TenantContext::class)->setTenant($tenant);

    $tech = Department::factory()->create([
        'tenant_id' => $tenant->id,
        'name' => 'التقنية',
        'code' => 'TECH',
    ]);
    $finance = Department::factory()->create([
        'tenant_id' => $tenant->id,
        'name' => 'المالية',
        'code' => 'FIN',
    ]);

    JobPosting::factory()->published()->create([
        'tenant_id' => $tenant->id,
        'department_id' => $tech->id,
        'title' => 'مهندس برمجيات Full-Stack',
        'slug' => 'fullstack-engineer',
    ]);
    JobPosting::factory()->published()->create([
        'tenant_id' => $tenant->id,
        'department_id' => $finance->id,
        'title' => 'محلل مالي',
        'slug' => 'finance-analyst',
    ]);

    app(TenantContext::class)->setTenant(null);

    $this->get(route('portal.careers', [
        'slug' => 'acme-robotics',
        'department' => 'التقنية',
    ]))
        ->assertOk()
        ->assertSee('مهندس برمجيات Full-Stack')
        ->assertDontSee('محلل مالي');
});
