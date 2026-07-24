<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Tenancy\Models\Tenant;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateTenantMarketingRequest;
use App\Services\Marketing\MarketingCache;
use App\Services\Media\ImageUploader;
use App\Services\Platform\PlatformAuditor;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Tenant Management — the 5-state lifecycle system of record (docs/MODULES.md
 * §6, ARCHITECTURE.md §3, BR-205/BR-206). List/detail remain frontend-first
 * mocks; marketing opt-in persists to real Tenant rows when present.
 */
class TenantController extends Controller
{
    /**
     * @var list<array{name:string, slug:string, owner:string, email:string, plan:string, status:string, employees:int, signup:string, last_active:string}>
     */
    private function tenants(): array
    {
        return [
            ['name' => 'شركة الأفق للتقنية', 'slug' => 'ofoq-tech', 'owner' => 'سارة المنصوري', 'email' => 'sara@ofoq.tech', 'plan' => 'Growth', 'status' => 'pending_approval', 'employees' => 24, 'signup' => '2026-07-19', 'last_active' => 'منذ ساعتين'],
            ['name' => 'مؤسسة نماء', 'slug' => 'namaa', 'owner' => 'خالد العتيبي', 'email' => 'khaled@namaa.co', 'plan' => 'Startup', 'status' => 'pending_approval', 'employees' => 6, 'signup' => '2026-07-19', 'last_active' => 'منذ 5 ساعات'],
            ['name' => 'مجموعة رواد', 'slug' => 'ruwad', 'owner' => 'ليلى الحربي', 'email' => 'laila@ruwad.sa', 'plan' => 'Enterprise', 'status' => 'pending_approval', 'employees' => 140, 'signup' => '2026-07-18', 'last_active' => 'منذ يوم'],
            ['name' => 'حلول بيان', 'slug' => 'bayan', 'owner' => 'عمر الشمري', 'email' => 'omar@bayan.io', 'plan' => 'Growth', 'status' => 'pending_verification', 'employees' => 0, 'signup' => '2026-07-20', 'last_active' => '—'],
            ['name' => 'شركة الابتكار', 'slug' => 'ibtikar', 'owner' => 'نورة القحطاني', 'email' => 'noura@ibtikar.sa', 'plan' => 'Growth', 'status' => 'active', 'employees' => 58, 'signup' => '2026-05-02', 'last_active' => 'منذ 10 دقائق'],
            ['name' => 'مؤسسة التميّز', 'slug' => 'tamayoz', 'owner' => 'فهد الدوسري', 'email' => 'fahad@tamayoz.co', 'plan' => 'Startup', 'status' => 'active', 'employees' => 12, 'signup' => '2026-04-15', 'last_active' => 'منذ ساعة'],
            ['name' => 'مجموعة الريادة', 'slug' => 'riyada', 'owner' => 'هند السالم', 'email' => 'hind@riyada.sa', 'plan' => 'Enterprise', 'status' => 'active', 'employees' => 210, 'signup' => '2026-01-20', 'last_active' => 'منذ 3 ساعات'],
            ['name' => 'متجر السلام', 'slug' => 'salam-store', 'owner' => 'ماجد العنزي', 'email' => 'majed@salam.store', 'plan' => 'Startup', 'status' => 'suspended', 'employees' => 9, 'signup' => '2026-03-11', 'last_active' => 'منذ 4 أيام'],
            ['name' => 'شركة المدى', 'slug' => 'almada', 'owner' => 'ريم الغامدي', 'email' => 'reem@almada.co', 'plan' => 'Growth', 'status' => 'suspended', 'employees' => 33, 'signup' => '2026-02-08', 'last_active' => 'منذ أسبوع'],
            ['name' => 'حلول أفنان', 'slug' => 'afnan', 'owner' => 'بدر المطيري', 'email' => 'bader@afnan.io', 'plan' => 'Startup', 'status' => 'cancelled', 'employees' => 0, 'signup' => '2025-12-01', 'last_active' => 'منذ 45 يومًا'],
        ];
    }

    public function index(Request $request): View
    {
        $tenants = $this->tenants();

        $counts = [
            'all' => count($tenants),
            'pending_verification' => count(array_filter($tenants, fn ($t): bool => $t['status'] === 'pending_verification')),
            'pending_approval' => count(array_filter($tenants, fn ($t): bool => $t['status'] === 'pending_approval')),
            'active' => count(array_filter($tenants, fn ($t): bool => $t['status'] === 'active')),
            'suspended' => count(array_filter($tenants, fn ($t): bool => $t['status'] === 'suspended')),
            'cancelled' => count(array_filter($tenants, fn ($t): bool => $t['status'] === 'cancelled')),
        ];

        $tabs = [
            'all' => 'الكل',
            'pending_verification' => 'بانتظار التحقق',
            'pending_approval' => 'بانتظار الموافقة',
            'active' => 'نشط',
            'suspended' => 'موقوف',
            'cancelled' => 'ملغى',
        ];

        $activeTab = $request->query('status', 'pending_approval');

        if (! array_key_exists($activeTab, $tabs)) {
            $activeTab = 'pending_approval';
        }

        $filtered = $activeTab === 'all'
            ? $tenants
            : array_values(array_filter($tenants, fn ($t): bool => $t['status'] === $activeTab));

        return view('admin.tenants.index', [
            'tenants' => $filtered,
            'tabs' => $tabs,
            'counts' => $counts,
            'activeTab' => $activeTab,
        ]);
    }

    public function show(string $tenant): View
    {
        $base = collect($this->tenants())->firstWhere('slug', $tenant)
            ?? collect($this->tenants())->firstWhere('status', 'active');

        $record = Tenant::query()->where('slug', $base['slug'])->first();

        $detail = [
            ...$base,
            'projects' => 24,
            'member_since' => '2 مايو 2026',
            'owner' => [
                'name' => $base['owner'],
                'email' => $base['email'],
                'verified' => $base['status'] !== 'pending_verification',
                'last_login' => $base['status'] === 'active' ? 'منذ 10 دقائق' : 'منذ 3 أيام',
            ],
            'usage' => [
                ['label' => 'الموظفون', 'used' => $base['employees'], 'limit' => 100],
                ['label' => 'المشاريع النشطة', 'used' => 24, 'limit' => 50],
                ['label' => 'المساحة التخزينية (GB)', 'used' => 41, 'limit' => 50],
                ['label' => 'مقاعد المستخدمين', 'used' => 62, 'limit' => 100],
            ],
            'audit' => [
                ['action' => 'سجّل الدخول', 'actor' => $base['owner'], 'time' => 'منذ 10 دقائق'],
                ['action' => 'أضاف موظفًا جديدًا', 'actor' => 'مدير الموارد البشرية', 'time' => 'منذ ساعتين'],
                ['action' => 'أنشأ مشروعًا', 'actor' => $base['owner'], 'time' => 'أمس'],
                ['action' => 'حدّث إعدادات الشركة', 'actor' => $base['owner'], 'time' => 'قبل 3 أيام'],
                ['action' => 'فُعّل الحساب بواسطة مشرف المنصّة', 'actor' => 'مشرف المنصّة', 'time' => $base['signup']],
            ],
            'marketing' => [
                'persistable' => $record !== null,
                'show_on_marketing' => $record?->show_on_marketing ?? false,
                'logo_url' => $record?->image('logo')->first()?->url()
                    ?? $record?->image('marketing_logo')->first()?->url(),
            ],
        ];

        return view('admin.tenants.show', [
            'tenant' => $detail,
            'tenantRecord' => $record,
        ]);
    }

    public function updateMarketing(
        UpdateTenantMarketingRequest $request,
        string $tenant,
        ImageUploader $uploader,
        PlatformAuditor $auditor,
    ): RedirectResponse {
        $record = Tenant::query()->where('slug', $tenant)->firstOrFail();

        $record->update([
            'show_on_marketing' => $request->boolean('show_on_marketing'),
        ]);

        if ($request->boolean('remove_logo')) {
            $uploader->deleteCollection($record, 'marketing_logo');
            $uploader->deleteCollection($record, 'logo');
        }

        if ($request->hasFile('marketing_logo')) {
            $uploader->deleteCollection($record, 'marketing_logo');
            $uploader->store(
                $record,
                $request->file('marketing_logo'),
                'logo',
                $request->input('alt_text'),
            );
        }

        MarketingCache::flush();
        $auditor->log('tenant.marketing.updated', $record, [
            'show_on_marketing' => $record->show_on_marketing,
        ]);

        return redirect()
            ->route('admin.tenants.show', $record->slug)
            ->with('status', 'تم حفظ إعدادات التسويق للمستأجر.');
    }
}
