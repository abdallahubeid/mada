<?php

namespace App\Services\Marketing;

use App\Domain\Tenancy\Enums\TenantStatus;
use App\Domain\Tenancy\Models\Tenant;
use App\Models\Faq;
use App\Models\Plan;
use App\Models\Testimonial;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
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
        return Cache::remember(MarketingCache::PAGE_HOME, now()->addMinutes(10), fn (): array => [
            'hero' => $this->hero(),
            'partners' => $this->partners(),
            'testimonials' => $this->testimonials(),
            'plans' => $this->plans(),
            'currency' => $this->currencySymbol(),
            'faqs' => $this->faqs(6),
            'cta' => $this->cta(),
            'footer' => $this->footer(),
            'features' => $this->featuresHeading(),
        ]);
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
     * @return list<array{category: string, question: string, answer: string}>
     */
    public function faqs(?int $limit = null): array
    {
        $query = Faq::query()->published();

        if ($limit !== null) {
            $query->limit($limit);
        }

        $items = $query->get(['category', 'question', 'answer']);

        if ($items->isEmpty()) {
            $fallback = config('faq.items', []);

            return $limit ? array_slice($fallback, 0, $limit) : $fallback;
        }

        return $items->map(fn (Faq $faq): array => [
            'category' => $faq->category,
            'question' => $faq->question,
            'answer' => $faq->answer,
        ])->all();
    }

    /**
     * @return list<array{quote: string, name: string, role: string|null, org: string|null}>
     */
    public function testimonials(): array
    {
        $items = Testimonial::query()->published()->get();

        if ($items->isEmpty()) {
            return [];
        }

        return $items->map(fn (Testimonial $t): array => [
            'quote' => $t->quote,
            'name' => $t->client_name,
            'role' => $t->client_role,
            'org' => $t->organization_name,
        ])->all();
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
        return config('marketing.cta', [
            'title' => 'جاهز لتحويل مؤسستك؟',
            'subtitle' => 'ابدأ تجربتك المجانية اليوم — دون بطاقة ائتمان، وبإعداد يستغرق دقائق.',
            'primary' => ['label' => 'ابدأ التجربة المجانية', 'url' => '/register'],
            'secondary' => ['label' => 'تواصل مع المبيعات', 'url' => '/contact'],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function footer(): array
    {
        /** @var array<string, mixed> $footer */
        $footer = config('marketing.footer', []);
        $copyright = $footer['copyright'] ?? '© {year} Veyra ERP. جميع الحقوق محفوظة.';
        $footer['copyright'] = Str::replace('{year}', (string) now()->year, $copyright);

        return $footer;
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
            'subtitle' => 'منصة Veyra ERP الشاملة لإدارة الموارد البشرية، المشاريع، والرواتب — أتمتة كاملة لعمليات مؤسستك في نظام واحد أنيق وذكي، بدقة تنظيمية وأمان تام لبياناتك.',
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
