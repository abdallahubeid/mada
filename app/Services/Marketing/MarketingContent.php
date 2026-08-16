<?php

namespace App\Services\Marketing;

use App\Domain\Tenancy\Enums\TenantStatus;
use App\Domain\Tenancy\Models\Tenant;
use App\Models\AiFeature;
use App\Models\Faq;
use App\Models\Feature;
use App\Models\Module;
use App\Models\Offering;
use App\Models\Plan;
use App\Models\Problem;
use App\Models\Setting;
use App\Models\Solution;
use App\Models\Testimonial;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Assembles public marketing page data from DB tables and config/marketing.php
 * (docs/MARKETING.md). Blade section components remain prop-driven.
 */
class MarketingContent
{
    /**
     * Full landing-page payload.
     *
     * @return array<string, mixed>
     */
    public function home(): array
    {
        $payload = Cache::remember(MarketingCache::PAGE_HOME, now()->addMinutes(10), fn (): array => [
            'hero' => $this->hero(),
            'partners' => $this->partners(),
            'plans' => $this->plans(),
            'currency' => $this->currencySymbol(),
            'footer' => $this->footer(),
            'features' => $this->featuresHeading(),
        ]);

        // Fresh Eloquent models (not cached) so CMS edits show immediately after MarketingCache::flush.
        $payload['problems'] = $this->problems();
        $payload['modules'] = $this->modules();
        $payload['solution_sidebar_modules'] = $this->modules(4);
        $payload['solutions'] = $this->solutions();
        $payload['offerings'] = $this->offerings();
        $payload['product_preview_stats'] = $this->productPreviewStats();
        $payload['ai_features'] = $this->aiFeatures();
        $payload['why_us_features'] = $this->whyUsFeatures();
        $payload['testimonials'] = $this->testimonials();
        $payload['faqs'] = $this->faqs(6);

        return $payload;
    }

    /**
     * Published problem / challenge cards for the landing page.
     *
     * @return EloquentCollection<int, Problem>
     */
    public function problems(): EloquentCollection
    {
        return Problem::query()->published()->get();
    }

    /**
     * Published solution bullet points for the landing page.
     *
     * @return EloquentCollection<int, Solution>
     */
    public function solutions(): EloquentCollection
    {
        return Solution::query()->published()->get();
    }

    /**
     * Published modules for the landing page (optionally limited for the Solutions sidebar).
     *
     * @return EloquentCollection<int, Module>
     */
    public function modules(?int $limit = null): EloquentCollection
    {
        $query = Module::query()->published();

        if ($limit !== null) {
            $query->limit($limit);
        }

        return $query->get();
    }

    /**
     * Published offering cards for the landing page.
     *
     * @return EloquentCollection<int, Offering>
     */
    public function offerings(): EloquentCollection
    {
        return Offering::query()->published()->get();
    }

    /**
     * Published AI roadmap cards for the landing page.
     *
     * @return EloquentCollection<int, AiFeature>
     */
    public function aiFeatures(): EloquentCollection
    {
        return AiFeature::query()->published()->get();
    }

    /**
     * Published Why Us / differentiator cards for the landing page.
     *
     * @return EloquentCollection<int, Feature>
     */
    public function whyUsFeatures(): EloquentCollection
    {
        return Feature::query()->published()->get();
    }

    /**
     * Dashboard stat strip for the product previews mock UI.
     *
     * @return array{
     *     tenants: array{value: int|float, decimals: int, prefix: string, suffix: string, separator: bool},
     *     employees: array{value: int|float, decimals: int, prefix: string, suffix: string, separator: bool},
     *     revenue: array{value: int|float, decimals: int, prefix: string, suffix: string, separator: bool},
     *     uptime: array{value: int|float, decimals: int, prefix: string, suffix: string, separator: bool}
     * }
     */
    public function productPreviewStats(): array
    {
        return Cache::remember(MarketingCache::PRODUCT_PREVIEW_STATS, now()->addMinutes(10), function (): array {
            $tenantCount = Tenant::query()->count();
            $employeeCount = User::query()->count();

            return [
                'tenants' => [
                    'value' => $tenantCount > 0 ? $tenantCount : 1284,
                    'decimals' => 0,
                    'prefix' => '',
                    'suffix' => '',
                    'separator' => true,
                ],
                'employees' => [
                    'value' => $employeeCount > 0 ? $employeeCount : 18420,
                    'decimals' => 0,
                    'prefix' => '',
                    'suffix' => '',
                    'separator' => true,
                ],
                'revenue' => [
                    'value' => $this->productPreviewRevenue(),
                    'decimals' => 0,
                    'prefix' => '',
                    'suffix' => 'K',
                    'separator' => false,
                ],
                'uptime' => [
                    'value' => (float) config('marketing.uptime', 99.9),
                    'decimals' => 1,
                    'prefix' => '%',
                    'suffix' => '',
                    'separator' => false,
                ],
            ];
        });
    }

    private function productPreviewRevenue(): int|float
    {
        if (Schema::hasTable('invoices')) {
            // Reserved for when billing tables ship; keep fallback until aggregate exists.
        }

        return (int) config('marketing.product_preview.revenue_k', 458);
    }

    /**
     * @return list<array{
     *     name: string,
     *     tagline: string|null,
     *     monthly: float|null,
     *     yearly: float|null,
     *     cta: string,
     *     href: string,
     *     highlighted: bool,
     *     features: list<string>
     * }>
     */
    public function plans(): array
    {
        $plans = Plan::query()->active()->with('features')->get();

        if ($plans->isEmpty()) {
            return config('plans.tiers', []);
        }

        return $plans->map(fn (Plan $plan): array => [
            'name' => $plan->name,
            'tagline' => $plan->tagline,
            'monthly' => $plan->price_monthly !== null ? (float) $plan->price_monthly : null,
            'yearly' => $plan->price_yearly !== null ? (float) $plan->price_yearly : null,
            'cta' => $plan->cta_label,
            'href' => $plan->cta_url,
            'highlighted' => $plan->is_highlighted,
            'features' => $plan->features->pluck('label')->all(),
        ])->all();
    }

    public function currencySymbol(): string
    {
        $plan = Plan::query()->active()->first();

        if ($plan?->currency === 'USD') {
            return '$';
        }

        return (string) config('plans.currency', '$');
    }

    /**
     * Published FAQs for marketing pages (optionally limited for landing preview).
     *
     * @return EloquentCollection<int, Faq>
     */
    public function faqs(?int $limit = null): EloquentCollection
    {
        $query = Faq::query()->published();

        if ($limit !== null) {
            $query->limit($limit);
        }

        $items = $query->get();

        if ($items->isNotEmpty()) {
            return $items;
        }

        $fallback = collect(config('faq.items', []));

        if ($limit !== null) {
            $fallback = $fallback->take($limit);
        }

        return new EloquentCollection($fallback->map(fn (array $item): Faq => new Faq([
            'category' => $item['category'],
            'question' => $item['question'],
            'answer' => $item['answer'],
            'is_published' => true,
        ]))->all());
    }

    /**
     * Published testimonials for the landing page.
     *
     * @return EloquentCollection<int, Testimonial>
     */
    public function testimonials(): EloquentCollection
    {
        return Testimonial::query()->published()->with('images')->get();
    }

    /**
     * @return array{eyebrow: string, names: list<string>}
     */
    public function partners(): array
    {
        /** @var array{eyebrow?: string, names?: list<string>} $fallback */
        $fallback = config('marketing.partners_fallback', [
            'eyebrow' => 'موثوق من قبل مؤسسات رائدة',
            'names' => [],
        ]);

        $fromTenants = Tenant::query()
            ->where('status', TenantStatus::Active)
            ->where('show_on_marketing', true)
            ->orderBy('created_at')
            ->limit(7)
            ->pluck('name')
            ->all();

        $names = $fromTenants !== []
            ? array_values(array_unique([...$fromTenants, ...($fallback['names'] ?? [])]))
            : ($fallback['names'] ?? []);

        return [
            'eyebrow' => $fallback['eyebrow'] ?? 'موثوق من قبل مؤسسات رائدة',
            'names' => array_slice($names, 0, 7),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function hero(): array
    {
        /** @var array<string, mixed> $hero */
        $hero = config('marketing.hero', $this->defaultHero());
        $hero['resolved_metrics'] = $this->resolveMetrics($hero['metrics'] ?? []);

        return $hero;
    }

    /**
     * @return array<string, mixed>
     */
    public function cta(): array
    {
        $settings = Setting::map();

        return [
            'title' => $settings->get('cta_title') ?? 'جاهز لتحويل مؤسستك؟',
            'subtitle' => $settings->get('cta_sub_title') ?? 'ابدأ تجربتك المجانية اليوم — دون بطاقة ائتمان، وبإعداد يستغرق دقائق.',
            'primary' => [
                'label' => $settings->get('cta_btn1_text') ?? 'ابدأ التجربة المجانية',
                'url' => $settings->get('cta_btn1_link') ?? '/register',
            ],
            'secondary' => [
                'label' => $settings->get('cta_btn2_text') ?? 'تواصل مع المبيعات',
                'url' => $settings->get('cta_btn2_link') ?? '/contact',
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function footer(): array
    {
        $settings = Setting::map();

        $link = static function (string $textKey, string $urlKey) use ($settings): ?array {
            $label = $settings->get($textKey);
            $url = $settings->get($urlKey);

            if (! filled($label) || ! filled($url)) {
                return null;
            }

            return ['label' => $label, 'url' => $url];
        };

        $columns = array_values(array_filter([
            [
                'title' => $settings->get('footer_title1'),
                'links' => array_values(array_filter([
                    $link('footer_btn1_text', 'footer_btn1_link'),
                    $link('footer_btn2_text', 'footer_btn2_link'),
                    $link('footer_btn3_text', 'footer_btn3_link'),
                    $link('footer_btn4_text', 'footer_btn4_link'),
                ])),
            ],
            [
                'title' => $settings->get('footer_title2'),
                'links' => array_values(array_filter([
                    $link('footer_btn5_text', 'footer_btn5_link'),
                    $link('footer_btn6_text', 'footer_btn6_link'),
                    $link('footer_btn7_text', 'footer_btn7_link'),
                ])),
            ],
            [
                'title' => $settings->get('footer_title3'),
                'links' => array_values(array_filter([
                    $link('footer_btn8_text', 'footer_btn8_link'),
                    $link('footer_btn9_text', 'footer_btn9_link'),
                ])),
            ],
        ], static fn (array $column): bool => filled($column['title']) && $column['links'] !== []));

        $social = [];

        for ($i = 1; $i <= 5; $i++) {
            $url = $settings->get("social_btn{$i}_link");

            if (! filled($url)) {
                continue;
            }

            $social[] = [
                'label' => $settings->get("social_btn{$i}_text") ?? '',
                'url' => $url,
                'platform' => match ($i) {
                    1 => 'x',
                    2 => 'linkedin',
                    3 => 'facebook',
                    4 => 'github',
                    5 => 'youtube',
                    default => 'x',
                },
            ];
        }

        /** @var array<string, mixed> $footer */
        $footer = config('marketing.footer', []);
        $copyright = $footer['copyright'] ?? '© {year} مدى. جميع الحقوق محفوظة.';

        return [
            'blurb' => $settings->get('footer_description') ?? $footer['blurb'] ?? '',
            'newsletter_title' => $settings->get('footer_newsletter_title') ?? 'البريد الإلكتروني',
            'newsletter_btn_text' => $settings->get('footer_newsletter_btn_text') ?? 'اشتراك',
            'copyright' => Str::replace('{year}', (string) now()->year, $copyright),
            'columns' => $columns !== [] ? $columns : ($footer['columns'] ?? []),
            'social' => $social !== [] ? $social : ($footer['social'] ?? []),
        ];
    }

    /**
     * @return array{title: string, subtitle: string}
     */
    public function featuresHeading(): array
    {
        return config('marketing.features', [
            'title' => 'قوة تتناسب مع طموحاتك',
            'subtitle' => 'كل ما تحتاجه مؤسستك من أدوات إدارية وتشغيلية في نظام واحد متكامل.',
        ]);
    }

    /**
     * @param  list<array<string, mixed>>  $metrics
     * @return list<array{value: float|int, prefix: string, suffix: string, decimals: int, label: string}>
     */
    private function resolveMetrics(array $metrics): array
    {
        return array_map(function (array $metric): array {
            $source = $metric['source'] ?? 'cms';
            $value = $metric['value'] ?? $metric['fallback'] ?? 0;

            if ($source === 'live') {
                $value = $this->liveMetric((string) ($metric['key'] ?? ''), $metric['fallback'] ?? 0);
            }

            return [
                'value' => $value,
                'prefix' => (string) ($metric['prefix'] ?? ''),
                'suffix' => (string) ($metric['suffix'] ?? ''),
                'decimals' => (int) ($metric['decimals'] ?? 0),
                'label' => (string) ($metric['label'] ?? ''),
            ];
        }, $metrics);
    }

    private function liveMetric(string $key, mixed $fallback): int|float
    {
        return Cache::remember("marketing.metrics.{$key}", now()->addMinutes(10), function () use ($key, $fallback): int|float {
            return match ($key) {
                'active_tenants' => Tenant::query()->where('status', TenantStatus::Active)->count() ?: (int) $fallback,
                'active_users' => User::query()->whereNotNull('tenant_id')->count() ?: (int) $fallback,
                default => is_numeric($fallback) ? $fallback + 0 : 0,
            };
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function defaultHero(): array
    {
        return [
            'eyebrow' => 'منصة SaaS متكاملة لإدارة المؤسسات',
            'title_line_1' => 'مستقبل إدارة',
            'title_accent' => 'المؤسسات',
            'title_line_2' => 'بذكاء وفخامة',
            'subtitle' => 'منصة مدى الشاملة لإدارة الموارد البشرية، المشاريع، والرواتب — أتمتة كاملة لعمليات مؤسستك في نظام واحد أنيق وذكي، بدقة تنظيمية وأمان تام لبياناتك.',
            'primary_cta' => ['label' => 'ابدأ التجربة المجانية', 'url' => '/register'],
            'secondary_cta' => ['label' => 'احجز عرضًا توضيحيًا', 'url' => '/contact'],
            'metrics' => [
                ['key' => 'active_users', 'source' => 'live', 'prefix' => '+', 'fallback' => 8500, 'label' => 'مستخدم نشط'],
                ['key' => 'uptime', 'source' => 'cms', 'prefix' => '%', 'value' => 99.9, 'decimals' => 1, 'label' => 'نسبة الجاهزية'],
                ['key' => 'active_tenants', 'source' => 'live', 'prefix' => '+', 'fallback' => 1200, 'label' => 'مؤسسة تثق بنا'],
            ],
        ];
    }
}
