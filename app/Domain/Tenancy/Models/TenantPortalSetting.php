<?php

namespace App\Domain\Tenancy\Models;

use App\Domain\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Per-tenant public careers portal content (landing sections, contact, toggles).
 *
 * One row per tenant (unique tenant_id). Defaults fill missing customization
 * so the public site renders before the owner saves settings.
 *
 * @property int $id
 * @property int $tenant_id
 * @property bool $is_portal_enabled
 * @property string|null $hero_badge_text
 * @property string|null $hero_title
 * @property string|null $hero_subtitle
 * @property string|null $hero_primary_cta_text
 * @property string|null $hero_primary_cta_url
 * @property string|null $hero_secondary_cta_text
 * @property string|null $hero_secondary_cta_url
 * @property bool $is_hero_active
 * @property string|null $about_title
 * @property string|null $about_subtitle
 * @property string|null $vision_text
 * @property string|null $mission_text
 * @property list<array{title: string, desc: string}>|null $values_json
 * @property bool $is_about_active
 * @property string|null $services_title
 * @property string|null $services_subtitle
 * @property list<array{title: string, description: string, icon: string}>|null $services_json
 * @property bool $is_services_active
 * @property string|null $culture_title
 * @property string|null $culture_subtitle
 * @property list<array{title: string, description: string}>|null $culture_perks_json
 * @property bool $is_culture_active
 * @property string|null $stats_title
 * @property list<array{label: string, value: int|string, suffix?: string}>|null $stats_json
 * @property bool $is_stats_active
 * @property string|null $careers_badge_text
 * @property string|null $careers_title
 * @property string|null $careers_subtitle
 * @property bool $is_careers_active
 * @property string|null $faq_title
 * @property string|null $faq_subtitle
 * @property list<array{question: string, answer: string}>|null $faqs_json
 * @property bool $is_faq_active
 * @property string|null $cta_title
 * @property string|null $cta_subtitle
 * @property string|null $cta_button_text
 * @property bool $is_cta_active
 * @property string|null $contact_email
 * @property string|null $contact_phone
 * @property string|null $contact_address
 * @property string|null $office_hours
 * @property string|null $map_embed_url
 * @property bool $is_contact_active
 * @property int|null $created_by
 * @property int|null $updated_by
 */
class TenantPortalSetting extends Model
{
    use BelongsToTenant, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'tenant_id',
        'is_portal_enabled',
        'hero_badge_text',
        'hero_title',
        'hero_subtitle',
        'hero_primary_cta_text',
        'hero_primary_cta_url',
        'hero_secondary_cta_text',
        'hero_secondary_cta_url',
        'is_hero_active',
        'about_title',
        'about_subtitle',
        'vision_text',
        'mission_text',
        'values_json',
        'is_about_active',
        'services_title',
        'services_subtitle',
        'services_json',
        'is_services_active',
        'culture_title',
        'culture_subtitle',
        'culture_perks_json',
        'is_culture_active',
        'stats_title',
        'stats_json',
        'is_stats_active',
        'careers_badge_text',
        'careers_title',
        'careers_subtitle',
        'is_careers_active',
        'faq_title',
        'faq_subtitle',
        'faqs_json',
        'is_faq_active',
        'cta_title',
        'cta_subtitle',
        'cta_button_text',
        'is_cta_active',
        'contact_email',
        'contact_phone',
        'contact_address',
        'office_hours',
        'map_embed_url',
        'is_contact_active',
        'created_by',
        'updated_by',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_portal_enabled' => true,
        'is_hero_active' => true,
        'is_about_active' => true,
        'is_services_active' => true,
        'is_culture_active' => true,
        'is_stats_active' => true,
        'is_careers_active' => true,
        'is_faq_active' => true,
        'is_cta_active' => true,
        'is_contact_active' => true,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_portal_enabled' => 'boolean',
            'is_hero_active' => 'boolean',
            'is_about_active' => 'boolean',
            'is_services_active' => 'boolean',
            'is_culture_active' => 'boolean',
            'is_stats_active' => 'boolean',
            'is_careers_active' => 'boolean',
            'is_faq_active' => 'boolean',
            'is_cta_active' => 'boolean',
            'is_contact_active' => 'boolean',
            'values_json' => 'array',
            'services_json' => 'array',
            'culture_perks_json' => 'array',
            'stats_json' => 'array',
            'faqs_json' => 'array',
        ];
    }

    /**
     * Resolve the tenant's portal settings, falling back to in-memory defaults.
     */
    public static function resolveForTenant(Tenant $tenant): self
    {
        $existing = static::query()->first();

        if ($existing !== null) {
            return $existing;
        }

        return (new static)->forceFill(static::defaultAttributes($tenant));
    }

    /**
     * @return array<string, mixed>
     */
    public static function defaultAttributes(Tenant $tenant): array
    {
        $slug = $tenant->slug;

        return [
            'tenant_id' => $tenant->id,
            'is_portal_enabled' => true,
            'hero_badge_text' => 'نحن نوظف الآن',
            'hero_title' => 'انضم إلى فريق يصنع الأثر',
            'hero_subtitle' => 'نبني فرقاً استثنائية ونمنح المواهب مساحة للنمو.',
            'hero_primary_cta_text' => 'استكشف الشواغر',
            'hero_primary_cta_url' => null,
            'hero_secondary_cta_text' => 'تواصل معنا',
            'hero_secondary_cta_url' => null,
            'is_hero_active' => true,
            'about_title' => 'رؤية ورسالة وقيم تقودنا',
            'about_subtitle' => 'من نحن',
            'vision_text' => 'أن نكون بيئة العمل المفضلة للمواهب الطموحة في المنطقة.',
            'mission_text' => 'تمكين الفرق بعمليات واضحة وثقافة قائمة على الثقة والجودة.',
            'values_json' => [
                ['title' => 'الشفافية', 'desc' => 'قرارات واضحة وتواصل مفتوح مع الجميع.'],
                ['title' => 'التعاون', 'desc' => 'نجاح مشترك يُبنى على فرق مترابطة.'],
                ['title' => 'الجودة', 'desc' => 'معايير عالية في كل تفصيلة نقدمها.'],
                ['title' => 'الابتكار', 'desc' => 'نطوّر باستمرار ونحتفي بالأفكار الجديدة.'],
            ],
            'is_about_active' => true,
            'services_title' => 'نطاق أعمالنا',
            'services_subtitle' => 'خدماتنا',
            'services_json' => [
                ['title' => 'العمليات التشغيلية', 'description' => 'إدارة يومية مرنة تدعم النمو المستدام.', 'icon' => 'ops'],
                ['title' => 'الحلول التقنية', 'description' => 'منتجات رقمية حديثة تسرّع إنجاز الأعمال.', 'icon' => 'tech'],
                ['title' => 'الاستشارات المالية', 'description' => 'تحليلات دقيقة تدعم قرارات الاستثمار.', 'icon' => 'finance'],
                ['title' => 'تطوير المواهب', 'description' => 'برامج تدريب ومسارات نمو مهني واضحة.', 'icon' => 'talent'],
            ],
            'is_services_active' => true,
            'culture_title' => 'مزايا تمنحك مساحة للتميّز',
            'culture_subtitle' => 'بيئة العمل',
            'culture_perks_json' => [
                ['title' => 'مرونة العمل', 'description' => 'نماذج حضور وهجين وعن بُعد تناسب أسلوب حياتك.'],
                ['title' => 'مسارات النمو', 'description' => 'خطط تطوير فردية وفرص ترقية مبنية على الأثر.'],
                ['title' => 'تأمين صحي شامل', 'description' => 'تغطية صحية لك ولعائلتك ضمن باقة متكاملة.'],
                ['title' => 'ثقافة تقدير', 'description' => 'احتفاء بالإنجازات ومكافآت مرتبطة بالأداء.'],
            ],
            'is_culture_active' => true,
            'stats_title' => 'أرقام تتحدث عنا',
            'stats_json' => [
                ['label' => 'الموظفون', 'value' => 128, 'suffix' => '+'],
                ['label' => 'الأقسام', 'value' => 12, 'suffix' => ''],
                ['label' => 'سنوات الخبرة', 'value' => 9, 'suffix' => '+'],
            ],
            'is_stats_active' => true,
            'careers_badge_text' => 'الوظائف',
            'careers_title' => 'فرص مميزة الآن',
            'careers_subtitle' => 'استكشف الأدوار المفتوحة وقدّم في دقائق.',
            'is_careers_active' => true,
            'faq_title' => 'إجابات سريعة للمرشحين',
            'faq_subtitle' => 'الأسئلة الشائعة',
            'faqs_json' => [
                ['question' => 'كيف أتقدم على وظيفة؟', 'answer' => 'اختر الشاغر المناسب من صفحة الوظائف، ثم عبّئ نموذج التقديم وأرفق سيرتك الذاتية.'],
                ['question' => 'هل يمكن التقديم على أكثر من وظيفة؟', 'answer' => 'نعم، يمكنك التقديم على عدة شواغر إذا كانت مهاراتك تناسب المتطلبات.'],
                ['question' => 'متى أتلقى رداً على طلبي؟', 'answer' => 'عادةً خلال 5–10 أيام عمل بعد إغلاق فترة الاستقبال أو اكتمال المراجعة الأولية.'],
                ['question' => 'هل توجد فرص عمل عن بُعد؟', 'answer' => 'نعم، بعض الأدوار تُعرض بنمط عن بُعد أو هجين حسب طبيعة الوظيفة.'],
            ],
            'is_faq_active' => true,
            'cta_title' => 'هل أنت جاهز للانضمام لقصة نجاحنا؟',
            'cta_subtitle' => 'قدّم اليوم وابدأ رحلة مهنية في بيئة تقدّر الأثر والطموح.',
            'cta_button_text' => 'تصفّح الشواغر',
            'is_cta_active' => true,
            'contact_email' => 'careers@'.$slug.'.test',
            'contact_phone' => '+966 11 000 0000',
            'contact_address' => 'حي العليا، الرياض، المملكة العربية السعودية',
            'office_hours' => 'الأحد — الخميس · 9:00 ص — 5:00 م',
            'map_embed_url' => 'https://maps.google.com/maps?q='.rawurlencode('حي العليا، الرياض، المملكة العربية السعودية').'&z=14&output=embed',
            'is_contact_active' => true,
        ];
    }

    public function isSectionActive(string $section): bool
    {
        return match ($section) {
            'hero' => (bool) $this->is_hero_active,
            'about' => (bool) $this->is_about_active,
            'services' => (bool) $this->is_services_active,
            'culture' => (bool) $this->is_culture_active,
            'stats' => (bool) $this->is_stats_active,
            'careers' => (bool) $this->is_careers_active,
            'faq' => (bool) $this->is_faq_active,
            'cta' => (bool) $this->is_cta_active,
            'contact' => (bool) $this->is_contact_active,
            default => false,
        };
    }

    /**
     * @return list<array{title: string, desc: string}>
     */
    public function values(): array
    {
        return array_values(array_filter(
            $this->values_json ?? [],
            fn (mixed $row): bool => is_array($row) && filled($row['title'] ?? null),
        ));
    }

    /**
     * @return list<array{title: string, description: string, icon: string}>
     */
    public function services(): array
    {
        return array_values(array_filter(
            $this->services_json ?? [],
            fn (mixed $row): bool => is_array($row) && filled($row['title'] ?? null),
        ));
    }

    /**
     * @return list<array{title: string, description: string}>
     */
    public function culturePerks(): array
    {
        return array_values(array_filter(
            $this->culture_perks_json ?? [],
            fn (mixed $row): bool => is_array($row) && filled($row['title'] ?? null),
        ));
    }

    /**
     * @return list<array{label: string, value: int|string, suffix: string}>
     */
    public function stats(): array
    {
        return collect($this->stats_json ?? [])
            ->filter(fn (mixed $row): bool => is_array($row) && filled($row['label'] ?? null))
            ->map(fn (array $row): array => [
                'label' => (string) $row['label'],
                'value' => $row['value'] ?? 0,
                'suffix' => (string) ($row['suffix'] ?? ''),
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array{question: string, answer: string}>
     */
    public function faqs(): array
    {
        return array_values(array_filter(
            $this->faqs_json ?? [],
            fn (mixed $row): bool => is_array($row) && filled($row['question'] ?? null),
        ));
    }
}
