<?php

namespace App\Domain\Platform;

use App\Models\AiFeature;
use App\Models\Faq;
use App\Models\Feature;
use App\Models\Module;
use App\Models\NewsletterSubscriber;
use App\Models\Offering;
use App\Models\Plan;
use App\Models\PlatformNotification;
use App\Models\Problem;
use App\Models\Solution;
use App\Models\SupportThread;
use App\Models\Testimonial;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use InvalidArgumentException;

/**
 * Registry of soft-deletable admin resources shown in سلة المحذوفات.
 *
 * @phpstan-type TrashResourceConfig array{
 *     label: string,
 *     model: class-string<Model>,
 *     title: callable(Model): string,
 *     subtitle?: callable(Model): ?string,
 *     flush_marketing?: bool,
 *     restore_images?: bool,
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
            'problems' => [
                'label' => 'المشاكل',
                'model' => Problem::class,
                'title' => fn (Model $m): string => (string) $m->getAttribute('title'),
                'flush_marketing' => true,
                'restore_images' => true,
            ],
            'solutions' => [
                'label' => 'الحلول',
                'model' => Solution::class,
                'title' => fn (Model $m): string => (string) $m->getAttribute('title'),
                'flush_marketing' => true,
                'restore_images' => true,
            ],
            'offerings' => [
                'label' => 'ما نقدمه',
                'model' => Offering::class,
                'title' => fn (Model $m): string => (string) $m->getAttribute('title'),
                'flush_marketing' => true,
                'restore_images' => true,
            ],
            'modules' => [
                'label' => 'الموديولات',
                'model' => Module::class,
                'title' => fn (Model $m): string => (string) $m->getAttribute('title'),
                'flush_marketing' => true,
                'restore_images' => true,
            ],
            'ai-features' => [
                'label' => 'ميزات الذكاء الاصطناعي',
                'model' => AiFeature::class,
                'title' => fn (Model $m): string => (string) $m->getAttribute('title'),
                'flush_marketing' => true,
                'restore_images' => true,
            ],
            'features' => [
                'label' => 'الميزات العامة',
                'model' => Feature::class,
                'title' => fn (Model $m): string => (string) $m->getAttribute('title'),
                'flush_marketing' => true,
                'restore_images' => true,
            ],
            'testimonials' => [
                'label' => 'آراء العملاء',
                'model' => Testimonial::class,
                'title' => fn (Model $m): string => (string) $m->getAttribute('client_name'),
                'subtitle' => fn (Model $m): ?string => $m->getAttribute('organization_name'),
                'flush_marketing' => true,
                'restore_images' => true,
            ],
            'faqs' => [
                'label' => 'الأسئلة الشائعة',
                'model' => Faq::class,
                'title' => fn (Model $m): string => (string) $m->getAttribute('question'),
                'flush_marketing' => true,
            ],
            'plans' => [
                'label' => 'الخطط',
                'model' => Plan::class,
                'title' => fn (Model $m): string => (string) $m->getAttribute('name'),
                'subtitle' => fn (Model $m): ?string => $m->getAttribute('slug'),
                'flush_marketing' => true,
            ],
            'admins' => [
                'label' => 'مديرو المنصّة',
                'model' => User::class,
                'title' => fn (Model $m): string => (string) $m->getAttribute('name'),
                'subtitle' => fn (Model $m): ?string => $m->getAttribute('email'),
                'query' => fn (Builder $q): Builder => $q->whereNull('tenant_id'),
            ],
            'support-threads' => [
                'label' => 'محادثات الدعم',
                'model' => SupportThread::class,
                'title' => fn (Model $m): string => (string) $m->getAttribute('subject'),
                'subtitle' => fn (Model $m): ?string => $m->getAttribute('email'),
            ],
            'newsletter-subscribers' => [
                'label' => 'مشتركو النشرة',
                'model' => NewsletterSubscriber::class,
                'title' => fn (Model $m): string => (string) $m->getAttribute('email'),
            ],
            'notifications' => [
                'label' => 'الإشعارات',
                'model' => PlatformNotification::class,
                'title' => fn (Model $m): string => (string) $m->getAttribute('title'),
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
